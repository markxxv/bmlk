<?php

namespace App\Jobs;

use App\Mail\OrderInvoiceMail;
use App\Models\Order;
use App\Services\Invoices\OrderInvoiceService;
use App\Support\OrderLocale;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOrderInvoiceEmail implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public array $backoff = [60, 300];

    public function __construct(public int $orderId)
    {
        $this->onConnection('database');
        $this->onQueue('invoices');
    }

    public function uniqueId(): string
    {
        return (string) $this->orderId;
    }

    public function handle(OrderInvoiceService $invoices): void
    {
        if (app()->isProduction() && in_array(config('mail.default'), ['log', 'array'], true)) {
            return;
        }

        $order = Order::query()->awaitingInvoiceEmail()->find($this->orderId);

        if (! $order || ! filter_var(trim((string) $order->email), FILTER_VALIDATE_EMAIL)) {
            return;
        }

        // Persist the retry timestamp even when rendering/storage or SMTP fails.
        // The scheduler can retry a failed job after its cooldown.
        Order::query()->whereKey($order->id)->whereNull('invoice_email_sent_at')
            ->update(['invoice_email_attempted_at' => now()]);

        // Save the same private PDF used by the account and Filament.
        // A storage failure must be retried, not mailed as an unsaved invoice.
        $path = $invoices->issue($order);

        if (! $path) {
            return;
        }

        // Serialize actual sends even if two jobs ever reach the worker.
        // Record success only after the mail transport accepts the message.
        DB::transaction(function () use ($path): void {
            $locked = Order::query()->awaitingInvoiceEmail()
                ->whereKey($this->orderId)->lockForUpdate()->first();

            if (! $locked || ! filter_var(trim((string) $locked->email), FILTER_VALIDATE_EMAIL)) {
                return;
            }

            OrderLocale::run($locked, fn () => Mail::to(trim($locked->email))
                ->locale(OrderLocale::for($locked))
                ->send(new OrderInvoiceMail($locked, $path)));

            $locked->forceFill(['invoice_email_sent_at' => now()])->save();
        });
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('Order invoice email failed; scheduled retry remains eligible.', [
            'order_id' => $this->orderId,
            'exception' => $exception ? $exception::class : null,
        ]);
    }
}
