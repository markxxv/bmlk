<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\SendcloudTrackingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncSendcloudTracking extends Command
{
    protected $signature = 'shipping:sync-tracking
        {--all : Backfill all existing orders with a tracking number, including delivered orders}
        {--order= : Sync a specific order number}
        {--limit= : Maximum number of orders to process}
        {--delay=250 : Delay between Sendcloud API calls, in milliseconds}';

    protected $description = 'Import Sendcloud v3 shipment tracking events, with a progress bar and safe repeatable updates';

    public function handle(SendcloudTrackingService $service): int
    {
        $query = Order::query()
            ->whereNotNull('tracking_number')
            ->where('tracking_number', '!=', '')
            ->orderBy('id');

        if ($number = $this->option('order')) {
            $query->where('order_number', $number);
        } elseif (! $this->option('all')) {
            $query->where('payment_status', 'paid')
                ->whereNotIn('status', ['completed', 'cancelled', 'archived'])
                ->where(fn ($q) => $q->whereNull('sendcloud_tracking_synced_at')
                    ->orWhere('sendcloud_tracking_synced_at', '<=', now()->subMinutes(50)));
        }

        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        if ($limit !== null && $limit < 1) {
            $this->error('--limit must be greater than zero.');
            return self::FAILURE;
        }

        $delay = (int) $this->option('delay');
        if ($delay < 0 || $delay > 60000) {
            $this->error('--delay must be between 0 and 60000 milliseconds.');
            return self::FAILURE;
        }

        $total = $limit ? min((clone $query)->count(), $limit) : (clone $query)->count();
        $this->info("Sendcloud tracking: {$total} shipment(s) queued.");

        if ($total === 0) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $synced = 0;
        $events = 0;
        $failed = 0;
        $attempted = 0;
        $errors = [];

        // Chunk by primary key: safely update orders while iterating, on SQLite and MySQL alike.
        foreach ($query->lazyById(50) as $order) {
            if ($attempted >= $total) {
                break;
            }

            try {
                $events += $service->sync($order);
                $synced++;
            } catch (Throwable $exception) {
                $failed++;
                $errors[] = $order->order_number.': '.$exception->getMessage();
                Log::warning('Sendcloud tracking import failed.', [
                    'order_id' => $order->id,
                    'message' => $exception->getMessage(),
                ]);

                // Stop immediately if Sendcloud starts rate limiting requests.
                if (str_contains($exception->getMessage(), 'HTTP 429')) {
                    $attempted++;
                    $bar->advance();
                    break;
                }
            }

            $attempted++;
            $bar->advance();
            if ($attempted < $total && $delay > 0) {
                usleep($delay * 1000);
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->components->twoColumnDetail('Shipments synchronized', (string) $synced);
        $this->components->twoColumnDetail('Events imported or updated', (string) $events);
        $this->components->twoColumnDetail('Failures', (string) $failed);

        foreach (array_slice($errors, 0, 15) as $error) {
            $this->warn($error);
        }

        if ($failed > 0) {
            $this->warn('Retry the command later; existing tracking events are not duplicated.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
