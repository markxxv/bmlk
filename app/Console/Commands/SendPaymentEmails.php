<?php

namespace App\Console\Commands;

use App\Mail\PaymentStatusMail;
use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendPaymentEmails extends Command
{
    protected $signature = 'orders:payment-emails';

    protected $description = 'Send payment confirmations and a single next-day payment reminder';

    public function handle(): int
    {
        if (app()->isProduction() && in_array(config('mail.default'), ['log', 'array'], true)) {
            $this->warn('Configure a real MAIL_MAILER before sending payment emails.');
            return self::FAILURE;
        }

        Order::query()
            ->where('payment_status', 'paid')
            ->whereNotNull('email')
            ->whereNull('paid_email_sent_at')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    $this->send($order, 'paid', 'paid_email_sent_at');
                }
            });

        Order::query()
            ->whereIn('payment_status', ['failed', 'unpaid'])
            ->whereNotNull('email')
            ->whereNotNull('payment_failed_at')
            ->whereNull('failed_email_sent_at')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    $this->send($order, 'failed', 'failed_email_sent_at');
                }
            });

        Order::query()
            ->whereIn('payment_status', ['failed', 'unpaid'])
            ->whereNotNull('email')
            ->whereNotNull('payment_failed_at')
            ->whereNotNull('failed_email_sent_at')
            ->whereNull('failed_reminder_sent_at')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    // Next calendar day at 10:00 in France, never on the same
                    // day as the first failure email.
                    $due = max(
                        $this->nextMorning($order->payment_failed_at)->timestamp,
                        $this->nextMorning($order->failed_email_sent_at)->timestamp,
                    );

                    if (now('Europe/Paris')->timestamp >= $due) {
                        $this->send($order, 'reminder', 'failed_reminder_sent_at');
                    }
                }
            });

        return self::SUCCESS;
    }

    private function nextMorning(CarbonInterface $date): CarbonInterface
    {
        return $date->copy()
            ->timezone('Europe/Paris')
            ->addDay()
            ->startOfDay()
            ->setTime(10, 0);
    }

    private function send(Order $order, string $kind, string $column): void
    {
        // Claim the message before sending so two cron executions cannot
        // send the same notification at once.
        $claim = now();
        $query = Order::query()
            ->whereKey($order->id)
            ->whereNotNull('email')
            ->whereNull($column);

        if ($kind === 'paid') {
            $query->where('payment_status', 'paid');
        } else {
            $query->whereIn('payment_status', ['failed', 'unpaid'])
                ->whereNotNull('payment_failed_at');
        }

        if ($query->update([$column => $claim]) !== 1) {
            return;
        }

        try {
            Mail::to($order->email)->send(new PaymentStatusMail($order, $kind));
            $this->info("{$kind}: {$order->order_number}");
        } catch (Throwable $e) {
            Order::query()->whereKey($order->id)->where($column, $claim)
                ->update([$column => null]);

            Log::error('Payment email failed.', [
                'order_id' => $order->id,
                'kind' => $kind,
                'error' => $e->getMessage(),
            ]);

            $this->error("Email failed for {$order->order_number}");
        }
    }
}
