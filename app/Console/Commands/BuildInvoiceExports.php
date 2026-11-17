<?php

namespace App\Console\Commands;

use App\Models\InvoiceExport;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Services\Invoices\InvoiceData;
use App\Services\Invoices\InvoicePdf;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use ZipArchive;

class BuildInvoiceExports extends Command
{
    protected $signature = 'invoices:build-exports';

    protected $description = 'Build one pending invoice ZIP requested from the admin panel and announce it in Telegram';

    private const STALE_AFTER_MINUTES = 20;

    private const CHUNK = 100;

    public function handle(): int
    {
        // A crashed run (killed PHP, server reboot) must not spin forever in the UI.
        InvoiceExport::query()
            ->where('status', InvoiceExport::PROCESSING)
            ->where('started_at', '<', now()->subMinutes(self::STALE_AFTER_MINUTES))
            ->update([
                'status' => InvoiceExport::FAILED,
                'error' => 'The build did not finish in time. Please request the archive again.',
                'finished_at' => now(),
            ]);

        $export = $this->claim();
        if (! $export) {
            return self::SUCCESS;
        }

        $this->info("Building invoice export #{$export->id} ({$export->date_from->toDateString()} – {$export->date_to->toDateString()})");

        $tmpDir = storage_path('app/private/'.InvoiceExport::DIRECTORY.'/tmp-'.$export->id);
        File::ensureDirectoryExists($tmpDir);
        $zipPath = $tmpDir.'/archive.zip';

        try {
            $count = $this->buildZip($export, $zipPath, $tmpDir);

            if ($count === 0) {
                throw new RuntimeException('No paid orders or refunds found for this period.');
            }

            $relative = InvoiceExport::DIRECTORY.'/invoices_'.$export->id.'_'.bin2hex(random_bytes(6)).'.zip';
            Storage::disk(InvoiceExport::DISK)->makeDirectory(InvoiceExport::DIRECTORY);
            File::move($zipPath, Storage::disk(InvoiceExport::DISK)->path($relative));

            $export->forceFill([
                'status' => InvoiceExport::READY,
                'file_path' => $relative,
                'file_size' => Storage::disk(InvoiceExport::DISK)->size($relative),
                'invoices_count' => $count,
                'error' => null,
                'finished_at' => now(),
            ])->save();
        } catch (Throwable $e) {
            report($e);
            $export->forceFill([
                'status' => InvoiceExport::FAILED,
                'error' => mb_substr($e instanceof RuntimeException ? $e->getMessage() : 'Unexpected error: '.$e->getMessage(), 0, 500),
                'finished_at' => now(),
            ])->save();
            File::deleteDirectory($tmpDir);

            return self::FAILURE;
        }

        File::deleteDirectory($tmpDir);
        $this->notify($export->fresh());

        return self::SUCCESS;
    }

    /** Atomically take the oldest pending request; a parallel run gets 0 affected rows and backs off. */
    private function claim(): ?InvoiceExport
    {
        $id = InvoiceExport::query()->where('status', InvoiceExport::PENDING)->orderBy('id')->value('id');
        if (! $id) {
            return null;
        }

        $claimed = InvoiceExport::query()
            ->whereKey($id)
            ->where('status', InvoiceExport::PENDING)
            ->update(['status' => InvoiceExport::PROCESSING, 'started_at' => now(), 'error' => null]);

        return $claimed === 1 ? InvoiceExport::query()->find($id) : null;
    }

    /** @return int number of PDFs in the archive */
    private function buildZip(InvoiceExport $export, string $zipPath, string $tmpDir): int
    {
        $tz = InvoiceData::TIMEZONE;
        $from = CarbonImmutable::parse($export->date_from->toDateString(), $tz)->startOfDay()->utc();
        $to = CarbonImmutable::parse($export->date_to->toDateString(), $tz)->endOfDay()->utc();
        $count = 0;

        $orders = Order::query()
            ->where('payment_status', 'paid')
            ->whereRaw('COALESCE(paid_at, created_at) BETWEEN ? AND ?', [$from, $to]);

        $orders->clone()->chunkById(self::CHUNK, function ($chunk) use ($zipPath, $tmpDir, &$count): void {
            $files = [];
            foreach ($chunk as $order) {
                $files['invoice-'.$this->safe(InvoiceData::number($order)).'.pdf'] = InvoicePdf::render(InvoiceData::forOrder($order));
            }
            $this->flush($zipPath, $tmpDir, $files);
            $count += count($files);
        });

        OrderRefund::query()
            ->with('order')
            ->where('status', 'succeeded')
            ->whereBetween('updated_at', [$from, $to])
            ->chunkById(self::CHUNK, function ($chunk) use ($zipPath, $tmpDir, &$count): void {
                $files = [];
                foreach ($chunk as $refund) {
                    if (! $refund->order) {
                        continue;
                    }
                    $data = InvoiceData::forRefund($refund, $refund->order);
                    $files['credit-note-'.$this->safe($data['number']).'.pdf'] = InvoicePdf::render($data);
                }
                $this->flush($zipPath, $tmpDir, $files);
                $count += count($files);
            });

        return $count;
    }

    /**
     * Add a batch to the ZIP and close it right away, so memory stays flat however large the period is.
     *
     * @param  array<string, string>  $files  name inside the archive => PDF bytes
     */
    private function flush(string $zipPath, string $tmpDir, array $files): void
    {
        if ($files === []) {
            return;
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Cannot create the ZIP archive.');
        }

        $temp = [];
        foreach ($files as $name => $bytes) {
            $path = $tmpDir.'/'.bin2hex(random_bytes(6)).'.pdf';
            file_put_contents($path, $bytes);
            $temp[] = $path;
            $zip->addFile($path, $name);
            $zip->setCompressionName($name, ZipArchive::CM_STORE); // PDFs are already compressed
        }

        if (! $zip->close()) {
            throw new RuntimeException('Cannot write the ZIP archive (disk full?).');
        }
        File::delete($temp);
    }

    private function safe(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '_', $value);
    }

    private function notify(InvoiceExport $export): void
    {
        $token = trim((string) config('services.telegram.bot_token'));
        $chatId = trim((string) config('services.telegram.chat_id'));
        if ($token === '' || $chatId === '') {
            return;
        }

        $mb = number_format(($export->file_size ?? 0) / 1048576, 1);
        $text = "🧾 Invoices ready\n"
            ."{$export->date_from->format('d.m.Y')} – {$export->date_to->format('d.m.Y')}\n"
            ."{$export->invoices_count} PDF · {$mb} MB\n\n"
            .$export->downloadUrl();

        try {
            $response = Http::timeout(15)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
            ]);
            if (! $response->successful()) {
                Log::warning('Invoice export Telegram notification failed', ['status' => $response->status()]);
            }
        } catch (Throwable $e) {
            Log::warning('Invoice export Telegram notification failed', ['error' => $e->getMessage()]);
        }
    }
}
