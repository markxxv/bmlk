<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderTelegramNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendOrderTelegramNotifications extends Command
{
    protected $signature = 'orders:telegram-notifications';

    protected $description = 'Notify Telegram of new paid orders, then remind daily while pending';

    public function handle(): int
    {
        $token = trim((string) config('services.telegram.bot_token'));
        $chatId = trim((string) config('services.telegram.chat_id'));

        if ($token === '' || $chatId === '') {
            $this->warn('Set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID to enable notifications.');

            return self::FAILURE;
        }

        $cutoff = now()->subDay();

        // Only orders registered at the moment Stripe confirmed payment are
        // eligible. Older paid orders are not unexpectedly sent on deployment.
        OrderTelegramNotification::query()
            ->with('order')
            ->whereHas('order', fn ($query) => $query->where('payment_status', 'paid'))
            ->where(function ($query) use ($cutoff): void {
                $query->whereNull('first_sent_at')
                    ->orWhere(function ($query) use ($cutoff): void {
                        $query->whereNotNull('first_sent_at')
                            ->where('last_sent_at', '<=', $cutoff)
                            ->whereHas('order', fn ($order) => $order->where('status', 'pending'));
                    });
            })
            ->chunkById(100, function ($notifications) use ($token, $chatId, $cutoff): void {
                foreach ($notifications as $notification) {
                    $this->notify($notification, $token, $chatId, $cutoff);
                }
            });

        return self::SUCCESS;
    }

    private function notify(OrderTelegramNotification $notification, string $token, string $chatId, mixed $cutoff): void
    {
        $first = $notification->first_sent_at === null;
        $claim = now();

        // Atomic per-order claim prevents overlapping cron runs from sending
        // duplicates. A crashed sender becomes eligible again after 5 minutes.
        $claimed = OrderTelegramNotification::query()
            ->whereKey($notification->id)
            ->where(function ($query): void {
                $query->whereNull('claimed_at')
                    ->orWhere('claimed_at', '<=', now()->subMinutes(5));
            })
            ->whereHas('order', function ($query) use ($first): void {
                $query->where('payment_status', 'paid');
                if (! $first) {
                    $query->where('status', 'pending');
                }
            })
            ->when(
                $first,
                fn ($query) => $query->whereNull('first_sent_at'),
                fn ($query) => $query->whereNotNull('first_sent_at')->where('last_sent_at', '<=', $cutoff),
            )
            ->update(['claimed_at' => $claim]);

        if ($claimed !== 1) {
            return;
        }

        try {
            $order = Order::query()->findOrFail($notification->order_id);

            $response = Http::acceptJson()->timeout(12)
                ->post('https://api.telegram.org/bot'.$token.'/sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $this->message($order, $first),
                    'parse_mode' => 'HTML',
                    'link_preview_options' => ['is_disabled' => true],
                ]);

            if (! $response->successful() || $response->json('ok') !== true) {
                throw new RuntimeException('Telegram HTTP '.$response->status().': '.(string) $response->json('description', 'sendMessage failed'));
            }

            OrderTelegramNotification::query()
                ->whereKey($notification->id)->where('claimed_at', $claim)
                ->update([
                    'first_sent_at' => $notification->first_sent_at ?? $claim,
                    'last_sent_at' => $claim,
                    'claimed_at' => null,
                ]);

            $this->info(($first ? 'New order: ' : 'Reminder: ').$order->order_number);
        } catch (Throwable $exception) {
            OrderTelegramNotification::query()
                ->whereKey($notification->id)->where('claimed_at', $claim)
                ->update(['claimed_at' => null]);

            Log::error('Telegram order notification failed.', [
                'order_id' => $notification->order_id,
                'error' => str_replace($token, '[REDACTED]', $exception->getMessage()),
            ]);

            $this->error('Telegram notification failed for order #'.$notification->order_id);
        }
    }

    private function message(Order $order, bool $first): string
    {
        $title = $first ? '🛍 Nouvelle commande payée' : '⏰ Rappel : commande toujours en attente';
        $customer = trim($order->first_name.' '.($order->last_name ?? ''));
        $quantity = collect($order->items ?? [])
            ->sum(fn (array $item): int => (int) ($item['quantity'] ?? 0));

        return '<b>'.$title.'</b>'."\n\n"
            .'Commande : <b>'.e($order->order_number).'</b>'."\n"
            .'Client : '.e($customer)."\n"
            .'Articles : '.$quantity."\n"
            .'Total : <b>'.number_format((float) $order->total_amount, 2, ',', ' ').' €</b>'."\n"
            .'Statut : '.e($order->status)."\n\n"
            .'<a href="'.e(url('/admin/orders/'.$order->id.'/edit')).'">Ouvrir dans Filament</a>';
    }
}
