<?php

namespace App\Services\Invoices;

use App\Models\Order;
use App\Models\OrderRefund;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/** Turns an order (or one of its refunds) into the plain array the PDF renderer draws. */
class InvoiceData
{
    public const TIMEZONE = 'Europe/Paris';

    public const PRODUCT_FALLBACK = 'COMMANDE EN LIGNE';

    public static function invoiceDate(Order $order): CarbonInterface
    {
        return ($order->paid_at ?? $order->created_at)->copy()->setTimezone(self::TIMEZONE);
    }

    public static function number(Order $order): string
    {
        return $order->order_number ?: 'BM-'.$order->id;
    }

    public static function forOrder(Order $order): array
    {
        $date = ($order->invoice_issued_at ?? self::invoiceDate($order))
            ->copy()->setTimezone(self::TIMEZONE)->format('Y-m-d');
        $orderDate = self::invoiceDate($order)->format('Y-m-d');
        $lines = self::orderLines($order);

        $total = self::cents($order->total_amount);
        $shipping = self::cents($order->delivery_cost);
        $subtotal = $total - $shipping;

        return [
            'title' => 'INVOICE',
            'label' => 'Invoice',
            'number' => self::number($order),
            'date' => $date,
            'order_number' => self::number($order),
            'order_date' => $orderDate,
            'payment' => 'Carte de crédit/débit',
            'address' => self::address($order),
            'items' => self::reconcile($lines, $subtotal, $subtotal),
            'subtotal' => $subtotal,
            'shipping' => $shipping !== 0 ? $shipping : null,
            'ship_label' => self::shippingLabel($order),
            'total' => $total,
        ];
    }

    public static function forRefund(OrderRefund $refund, Order $order): array
    {
        $data = self::forOrder($order);
        $total = (int) $refund->amount_cents;
        $lines = [];

        foreach ((array) $refund->items as $line) {
            $index = $line['item_index'] ?? null;
            $item = $index !== null ? ($order->items[$index] ?? null) : null;
            $qty = (int) ($line['quantity'] ?? 0);
            if (! $item || $qty < 1) {
                continue;
            }
            $unit = self::cents($item['discounted_unit_price'] ?? $item['unit_price'] ?? 0);
            $lines[] = [self::itemName($item), $qty, $unit * $qty];
        }

        if ($refund->type === 'item' && $lines === [] && $refund->item_index !== null) {
            $item = $order->items[$refund->item_index] ?? null;
            if ($item) {
                $unit = self::cents($item['discounted_unit_price'] ?? $item['unit_price'] ?? 0);
                $lines[] = [self::itemName($item), (int) $refund->quantity, $unit * (int) $refund->quantity];
            }
        }

        $sum = array_sum(array_column($lines, 2));
        if ($lines === [] || abs($sum - $total) > 5 * count($lines)) {
            $lines = [['REMBOURSEMENT', 1, $total]];
        } else {
            $lines[count($lines) - 1][2] += $total - $sum;
        }

        $date = $refund->updated_at->copy()->setTimezone(self::TIMEZONE)->format('Y-m-d');

        return array_merge($data, [
            'title' => 'CREDIT NOTE',
            'label' => 'Credit Note',
            'number' => self::number($order).'-R'.$refund->id,
            'date' => $date,
            'items' => $lines,
            'subtotal' => $total,
            'shipping' => null,
            'total' => $total,
        ]);
    }

    /** @return list<array{0:string,1:int,2:int}> name, quantity, gross line total in cents */
    private static function orderLines(Order $order): array
    {
        $lines = [];
        foreach ((array) $order->items as $item) {
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $unit = isset($item['discounted_unit_price'])
                ? self::cents($item['discounted_unit_price']) * $qty
                : self::cents($item['line_total'] ?? ($item['unit_price'] ?? 0) * $qty);
            $lines[] = [self::itemName($item), $qty, $unit];
        }

        return $lines;
    }

    private static function reconcile(array $lines, int $subtotal, int $expected): array
    {
        $diff = $subtotal - array_sum(array_column($lines, 2));
        if ($lines === [] || abs($diff) > 5 + count($lines)) {
            return [[self::PRODUCT_FALLBACK, 1, $expected]];
        }
        $lines[count($lines) - 1][2] += $diff;

        return $lines;
    }

    private static function itemName(array $item): string
    {
        $name = trim(html_entity_decode(strip_tags((string) ($item['name'] ?? 'Product')), ENT_QUOTES | ENT_HTML5));
        $options = collect($item['options'] ?? [])
            ->map(fn ($o) => is_array($o) ? ($o['display'] ?? null) : (string) $o)
            ->filter()->implode(' · ');

        return preg_replace('/\s+/u', ' ', $options !== '' ? "{$name} – {$options}" : $name);
    }

    private static function address(Order $order): array
    {
        $isFrance = Str::upper((string) $order->shipping_country_code) === 'FR';
        $name = trim(preg_replace('/\s+/u', ' ', trim($order->first_name.' '.$order->last_name)));
        if ($order->billing_type === 'business' && filled($order->billing_company)) {
            $billingIsFrance = Str::upper((string) $order->billing_country_code) === 'FR';
            $lines = [
                trim((string) $order->billing_company),
                filled($order->billing_vat) ? 'TVA / VAT: '.trim((string) $order->billing_vat) : '',
                trim((string) ($order->billing_address ?: $order->address)),
                trim((string) ($order->billing_zip ?: $order->zip).' '.($order->billing_city ?: $order->city)),
                $billingIsFrance ? '' : trim((string) ($order->billing_country ?: $order->country)),
            ];
        } else {
            $lines = [
                $name,
                trim((string) $order->address),
                trim($order->zip.' '.$order->city),
                $isFrance ? '' : trim((string) $order->country),
            ];
        }

        return array_values(array_filter(array_map(fn ($l) => trim((string) $l, ' ,'), $lines), fn ($l) => $l !== ''));
    }

    private static function shippingLabel(Order $order): string
    {
        $code = Str::upper((string) $order->shipping_country_code);
        if ($code === 'FR') {
            return 'Courrier';
        }

        return in_array($code, self::EUROPE, true) ? 'Courrier Europe' : 'Courrier International';
    }

    private static function cents(mixed $value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private const EUROPE = [
        'AD', 'AL', 'AT', 'BA', 'BE', 'BG', 'BY', 'CH', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'GB', 'GG', 'GI', 'GR',
        'HR', 'HU', 'IE', 'IM', 'IS', 'IT', 'JE', 'LI', 'LT', 'LU', 'LV', 'MC', 'MD', 'ME', 'MK', 'MT', 'NL', 'NO', 'PL',
        'PT', 'RO', 'RS', 'SE', 'SI', 'SK', 'SM', 'UA', 'VA', 'XK',
    ];
}
