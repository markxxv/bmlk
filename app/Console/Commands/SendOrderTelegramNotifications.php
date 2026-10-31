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
    protected $signature = 'orders:telegram-notifications {--order= : Immediately send the first notification for one paid order}';

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
        $query = OrderTelegramNotification::query()
            ->whereHas('order', fn ($order) => $order->where('payment_status', 'paid'));

        if ($this->option('order') !== null) {
            // Stripe calls this once after its HTTP response; the hourly cron
            // is only a fallback for failed initial sends and pending reminders.
            $query->where('order_id', (int) $this->option('order'))
                ->whereNull('first_sent_at');
        } else {
            $query->where(function ($query) use ($cutoff): void {
                $query->whereNull('first_sent_at')
                    ->orWhere(function ($query) use ($cutoff): void {
                        $query->whereNotNull('first_sent_at')
                            ->where('last_sent_at', '<=', $cutoff)
                            ->whereHas('order', fn ($order) => $order->where('status', 'pending'));
                    });
            });
        }

        $query->chunkById(100, function ($notifications) use ($token, $chatId, $cutoff): void {
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

            foreach ($this->messages($order, $first) as $message) {
                $response = Http::acceptJson()->timeout(12)
                    ->post('https://api.telegram.org/bot'.$token.'/sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $message,
                        'parse_mode' => 'HTML',
                        'link_preview_options' => ['is_disabled' => true],
                    ]);

                if (! $response->successful() || $response->json('ok') !== true) {
                    throw new RuntimeException('Telegram HTTP '.$response->status().': '.(string) $response->json('description', 'sendMessage failed'));
                }
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

    /**
     * @return list<string>
     */
    private function messages(Order $order, bool $first): array
    {
        $title = $first ? '🛍 Nouvelle commande payée' : '⏰ RAPPEL — Commande oubliée !';
        $customer = trim((string) $order->first_name.' '.(string) ($order->last_name ?? ''));
        $items = collect($order->items ?? [])
            ->filter(fn (mixed $item): bool => is_array($item));
        $quantity = $items->sum(fn (array $item): int => max(1, (int) ($item['quantity'] ?? 1)));

        $status = match ($order->status) {
            'pending' => 'En attente de préparation',
            'processing' => 'En préparation',
            'shipped' => 'Expédiée',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée',
            default => (string) $order->status,
        };

        $lines = [
            '<b>'.$title.'</b>',
            '',
            'Commande : <b>'.$this->escape($order->order_number).'</b>',
            'Statut : '.$this->escape($status),
        ];

        if (! $first) {
            $lines[] = '⚠️ Cette commande a été payée, mais sa préparation n’a toujours pas commencé.';
        }

        $lines[] = '';
        $lines[] = '👤 <b>Client</b>';
        $this->appendField($lines, '', $customer);
        $this->appendField($lines, 'Téléphone : ', $order->phone);
        $this->appendField($lines, 'E-mail : ', $order->email);

        $lines[] = '';
        $lines[] = '📦 <b>Adresse de livraison</b>';
        $this->appendField($lines, '', $order->address);
        $this->appendField($lines, '', trim((string) $order->zip.' '.(string) $order->city));
        $this->appendField($lines, '', $order->country);
        $this->appendField($lines, 'Transporteur : ', $order->shipping_option_name);

        if ($order->service_point_id) {
            $this->appendField($lines, 'Point relais : ', $order->service_point_name);
            $this->appendField($lines, '', $order->service_point_address);
        }

        $lines[] = '';
        $lines[] = '🛍 <b>Articles commandés ('.$quantity.')</b>';

        foreach ($items as $item) {
            $count = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = (float) data_get($item, 'unit_price', 0);
            $lineTotal = (float) data_get($item, 'line_total', $unitPrice * $count);

            $this->appendField($lines, '• '.$count.' × ', data_get($item, 'name', 'Produit'));

            foreach (data_get($item, 'options', []) as $option) {
                if (! is_array($option)) {
                    continue;
                }

                $label = trim((string) data_get($option, 'label', ''));
                $value = trim((string) data_get($option, 'display', ''));

                if ($label !== '' || $value !== '') {
                    $this->appendField($lines, '  ↳ ', $label !== '' && $value !== ''
                        ? $label.' : '.$value : $label.$value);
                }
            }

            $lines[] = '  '.$count.' × '.$this->money($unitPrice).' = '.$this->money($lineTotal);
        }

        $lines[] = '';
        $lines[] = '💶 <b>Récapitulatif</b>';
        $lines[] = 'Sous-total : '.$this->money((float) $order->subtotal);

        if ((float) $order->discount_amount > 0) {
            $promo = filled($order->promo_code) ? ' ('.$this->escape($order->promo_code).')' : '';
            $lines[] = 'Réduction'.$promo.' : − '.$this->money((float) $order->discount_amount);
        }

        $lines[] = 'Livraison : '.((float) $order->delivery_cost === 0.0
            ? 'Offerte' : $this->money((float) $order->delivery_cost));
        $lines[] = '<b>Total payé : '.$this->money((float) $order->total_amount).'</b>';

        if (filled($order->message)) {
            $lines[] = '';
            $lines[] = '📝 <b>Message du client</b>';
            $this->appendField($lines, '', $order->message);
        }

        $lines[] = '';
        // An authenticated staff page on the shop domain. Never expose the
        // private customer order access token in a Telegram group.
        $lines[] = '<a href="'.$this->escape(url('/admin/orders/'.$order->id.'/edit')).'">Voir la commande sur le site</a>';

        return $this->splitMessages($lines, $order->order_number);
    }

    /**
     * Split raw values BEFORE HTML escaping so even special characters in
     * an address or customer note cannot exceed Telegram's 4096-char limit.
     *
     * @param list<string> $lines
     */
    private function appendField(array &$lines, string $prefix, mixed $value): void
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return;
        }

        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        foreach (mb_str_split($text, 350, 'UTF-8') as $index => $part) {
            $lines[] = ($index === 0 ? $prefix : '    ').$this->escape($part);
        }
    }

    /**
     * @param list<string> $lines
     * @return list<string>
     */
    private function splitMessages(array $lines, string $number): array
    {
        $messages = [];
        $current = '';

        foreach ($lines as $line) {
            $next = $current === '' ? $line : $current."\n".$line;

            if (mb_strlen($next, 'UTF-8') > 3500 && $current !== '') {
                $messages[] = $current;
                $current = '<b>Commande '.$this->escape($number).' — suite</b>'."\n".$line;
            } else {
                $current = $next;
            }
        }

        if ($current !== '') {
            $messages[] = $current;
        }

        return $messages;
    }

    private function escape(mixed $value): string
    {
        return e((string) ($value ?? ''));
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, ',', ' ').' €';
    }
}
