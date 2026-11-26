<?php

namespace App\Console\Commands;

use App\Jobs\SendOrderInvoiceEmail;
use App\Models\Order;
use Illuminate\Console\Command;

class QueueOrderInvoiceEmails extends Command
{
    protected $signature = 'orders:queue-invoice-emails';

    protected $description = 'Queue shipment invoice emails for eligible new orders';

    public function handle(): int
    {
        if (app()->isProduction() && in_array(config('mail.default'), ['log', 'array'], true)) {
            $this->warn('Configure a real MAIL_MAILER before sending invoice emails.');

            return self::FAILURE;
        }

        // Poll persisted state rather than model events: warehouse status
        // updates use a query builder, while Filament and Sendcloud use saves.
        Order::query()->awaitingInvoiceEmail()
            ->where(fn ($query) => $query->whereNull('invoice_email_attempted_at')
                ->orWhere('invoice_email_attempted_at', '<=', now()->subMinutes(30)))
            ->select(['id'])
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    SendOrderInvoiceEmail::dispatch($order->id);
                }
            });

        return self::SUCCESS;
    }
}
