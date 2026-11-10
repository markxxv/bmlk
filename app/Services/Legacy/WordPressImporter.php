<?php

namespace App\Services\Legacy;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * One-off import of customers and past orders from the old WordPress /
 * WooCommerce (HPOS) database. History only: no passwords, no payment data.
 *
 * - Only wc-completed and wc-refunded orders are imported.
 * - Only customers that have at least one such order are imported
 *   (administrators are skipped). Passwords are NOT migrated: users must
 *   set one through the normal password-reset flow.
 * - Rows are inserted without explicit ids, so primary-key sequences stay
 *   consistent. Eloquent events are bypassed on purpose (Order::created
 *   would overwrite order_number).
 * - Safe to run again: existing users (by email) and orders (by order_number)
 *   are never touched.
 */
class WordPressImporter
{
    public const STATUSES = ['wc-completed', 'wc-refunded'];

    /** @var array<string, int> */
    public array $stats = [
        'users_created' => 0,
        'users_existing' => 0,
        'orders_created' => 0,
        'orders_existing' => 0,
        'orders_no_email' => 0,
        'orders_linked_to_user' => 0,
        'orders_without_user' => 0,
        'refunds_created' => 0,
    ];

    /** @var list<array{wp_id:int, expected:float, actual:float}> */
    public array $mismatches = [];

    /** @var list<string> */
    private array $importedNumbers = [];

    /** @var array<string, int>|null lower(email) => users.id */
    private ?array $emailMap = null;

    /** @var array<int, int> wp user id => users.id */
    private array $userMap = [];

    /** @var array<string, string>|null */
    private ?array $countries = null;

    private ?Collection $customerIds = null;

    private int $fakeId = 0;

    public function __construct(
        private readonly ConnectionInterface $legacy,
        private readonly ConnectionInterface $db,
        private readonly bool $dryRun = false,
    ) {
    }

    public function countUsers(): int
    {
        return $this->customerIds()->count();
    }

    public function countOrders(): int
    {
        return $this->eligibleOrders()->count();
    }

    /** @param callable():void $tick */
    public function importUsers(callable $tick): void
    {
        $this->loadEmailMap();

        foreach ($this->customerIds()->chunk(100) as $chunk) {
            $ids = $chunk->values()->all();
            $users = $this->legacy->table('users')->whereIn('ID', $ids)->orderBy('ID')->get();
            $meta = $this->legacy->table('usermeta')
                ->whereIn('user_id', $ids)
                ->whereIn('meta_key', ['first_name', 'last_name', 'billing_first_name', 'billing_last_name'])
                ->get()->groupBy('user_id');

            $this->inTransaction(function () use ($users, $meta, $tick): void {
                foreach ($users as $user) {
                    $this->importUser($user, ($meta->get($user->ID) ?? collect())->pluck('meta_value', 'meta_key'));
                    $tick();
                }
            });
        }
    }

    /** @param callable():void $tick */
    public function importOrders(callable $tick): void
    {
        $this->loadEmailMap();

        $this->eligibleOrders()->chunkById(100, function (Collection $orders) use ($tick): void {
            $this->inTransaction(fn () => $this->importOrderChunk($orders, $tick));
        });
    }

    /** Imported orders that wrongly allow ratings. Must be 0. */
    public function verifyRatingsDisabled(): int
    {
        if ($this->dryRun || $this->importedNumbers === []) {
            return 0;
        }

        $wrong = 0;
        foreach (array_chunk($this->importedNumbers, 500) as $chunk) {
            $wrong += $this->db->table('orders')
                ->whereIn('order_number', $chunk)
                ->where('ratings_enabled', true)
                ->count();
        }

        return $wrong;
    }

    // ------------------------------------------------------------------ users

    private function importUser(object $user, Collection $meta): void
    {
        $email = Str::lower(trim((string) $user->user_email));
        if ($email === '') {
            return;
        }

        if (isset($this->emailMap[$email])) {
            $this->userMap[(int) $user->ID] = $this->emailMap[$email];
            $this->stats['users_existing']++;

            return;
        }

        $name = trim($meta->get('first_name').' '.$meta->get('last_name'));
        if ($name === '') {
            $name = trim($meta->get('billing_first_name').' '.$meta->get('billing_last_name'));
        }
        if ($name === '') {
            $name = trim((string) $user->display_name) ?: Str::before($email, '@');
        }

        $id = $this->insert('users', [
            'name' => Str::limit($name, 250, ''),
            'email' => $email,
            'email_verified_at' => null,
            'password' => null,
            'created_at' => $this->date($user->user_registered),
            'updated_at' => now(),
        ]);

        $this->emailMap[$email] = $id;
        $this->userMap[(int) $user->ID] = $id;
        $this->stats['users_created']++;
    }

    /** WP user ids of customers with at least one importable order (admins excluded). */
    private function customerIds(): Collection
    {
        if ($this->customerIds) {
            return $this->customerIds;
        }

        $ids = $this->eligibleOrders()->where('customer_id', '>', 0)->distinct()->pluck('customer_id')
            ->map(fn ($id) => (int) $id)->sort()->values();

        $capKey = $this->legacy->getTablePrefix().'capabilities';
        $admins = collect();
        foreach ($ids->chunk(500) as $chunk) {
            $admins = $admins->merge(
                $this->legacy->table('usermeta')
                    ->whereIn('user_id', $chunk->all())
                    ->where('meta_key', $capKey)
                    ->where('meta_value', 'like', '%administrator%')
                    ->pluck('user_id')
            );
        }

        return $this->customerIds = $ids->diff($admins->map(fn ($id) => (int) $id))->values();
    }

    // ----------------------------------------------------------------- orders

    private function eligibleOrders()
    {
        return $this->legacy->table('wc_orders')
            ->where('type', 'shop_order')
            ->whereIn('status', self::STATUSES);
    }

    /** @param callable():void $tick */
    private function importOrderChunk(Collection $orders, callable $tick): void
    {
        $ids = $orders->pluck('id')->all();

        $addresses = $this->legacy->table('wc_order_addresses')->whereIn('order_id', $ids)->get()->groupBy('order_id');
        $ops = $this->legacy->table('wc_order_operational_data')->whereIn('order_id', $ids)->get()->keyBy('order_id');
        $items = $this->legacy->table('woocommerce_order_items')->whereIn('order_id', $ids)
            ->orderBy('order_item_id')->get()->groupBy('order_id');
        $itemIds = $items->map(fn ($rows) => $rows->pluck('order_item_id'))->flatten()->all();
        $meta = $itemIds === [] ? collect() : $this->legacy->table('woocommerce_order_itemmeta')
            ->whereIn('order_item_id', $itemIds)->get()->groupBy('order_item_id');
        $refunds = $this->legacy->table('wc_orders')->where('type', 'shop_order_refund')
            ->whereIn('parent_order_id', $ids)->get()->groupBy('parent_order_id');

        $needEmail = $orders->filter(fn ($o) => trim((string) $o->billing_email) === '' && (int) $o->customer_id > 0)
            ->pluck('customer_id')->unique()->values()->all();
        $wpEmails = $needEmail === [] ? collect()
            : $this->legacy->table('users')->whereIn('ID', $needEmail)->pluck('user_email', 'ID');

        $numbers = $orders->mapWithKeys(fn ($o) => [$o->id => $this->orderNumber($o)]);
        $existing = $this->db->table('orders')->whereIn('order_number', $numbers->values()->all())
            ->pluck('order_number')->flip();

        foreach ($orders as $order) {
            $tick();

            $email = Str::lower(trim((string) $order->billing_email));
            if ($email === '' && (int) $order->customer_id > 0) {
                $email = Str::lower(trim((string) $wpEmails->get($order->customer_id)));
            }
            if ($email === '') {
                $this->stats['orders_no_email']++;

                continue;
            }

            $number = $numbers[$order->id];
            if ($existing->has($number)) {
                $this->stats['orders_existing']++;

                continue;
            }

            $orderItems = $items->get($order->id) ?? collect();
            [$lines, $promo, $shippingName] = $this->lines($orderItems, $meta);
            $op = $ops->get($order->id);

            $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);
            $discount = round((float) ($op->discount_total_amount ?? 0) + (float) ($op->discount_tax_amount ?? 0), 2);
            $delivery = round((float) ($op->shipping_total_amount ?? 0) + (float) ($op->shipping_tax_amount ?? 0), 2);
            $total = round((float) $order->total_amount, 2);
            $computed = round($subtotal - $discount + $delivery, 2);
            if (abs($computed - $total) > 0.02) {
                $this->mismatches[] = ['wp_id' => (int) $order->id, 'expected' => $total, 'actual' => $computed];
            }

            $addr = $addresses->get($order->id) ?? collect();
            $billing = $addr->firstWhere('address_type', 'billing');
            $shipping = $addr->firstWhere('address_type', 'shipping');
            $to = ($shipping && trim((string) $shipping->address_1) !== '') ? $shipping : $billing;
            $countryCode = Str::upper(trim((string) ($to->country ?? $billing->country ?? '')));

            $createdAt = $this->date($order->date_created_gmt);
            $paidAt = ! empty($op->date_paid_gmt) && ! str_starts_with((string) $op->date_paid_gmt, '0000')
                ? $this->date($op->date_paid_gmt) : $createdAt;

            $userId = $this->userMap[(int) $order->customer_id] ?? $this->emailMap[$email] ?? null;

            $orderId = $this->insert('orders', [
                'order_number' => $number,
                'first_name' => Str::limit(trim((string) ($to->first_name ?? '')), 250, ''),
                'last_name' => Str::limit(trim((string) ($to->last_name ?? '')), 250, '') ?: null,
                'phone' => Str::limit(trim((string) ($billing->phone ?? $to->phone ?? '')), 50, ''),
                'email' => $email,
                'country' => $this->countryName($countryCode),
                'zip' => trim((string) ($to->postcode ?? '')) ?: null,
                'city' => trim((string) ($to->city ?? '')),
                'address' => trim(implode(', ', array_filter([
                    trim((string) ($to->address_1 ?? '')), trim((string) ($to->address_2 ?? '')),
                ]))),
                'items' => json_encode($lines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'subtotal' => $subtotal,
                'delivery_cost' => $delivery,
                'total_amount' => $total,
                'status' => 'completed',
                'created_at' => $createdAt,
                'updated_at' => $this->date($order->date_updated_gmt) ?: $createdAt,
                'user_id' => $userId,
                'promo_code' => $promo,
                'discount_amount' => $discount,
                'payment_status' => 'paid',
                'payment_attempts' => 1,
                'paid_at' => $paidAt,
                // Marks the "order paid" email as already sent: orders:payment-emails
                // must never mail historical customers.
                'paid_email_sent_at' => $paidAt,
                'shipping_country_code' => strlen($countryCode) === 2 ? $countryCode : null,
                'shipping_option_name' => $shippingName,
                // Historical orders never ask for product ratings.
                'ratings_enabled' => false,
            ]);

            $this->importedNumbers[] = $number;
            $this->stats['orders_created']++;
            $userId ? $this->stats['orders_linked_to_user']++ : $this->stats['orders_without_user']++;

            $this->importRefunds($orderId, $order, $refunds->get($order->id) ?? collect(), $total);
        }
    }

    /** @return array{0: list<array<string,mixed>>, 1: ?string, 2: ?string} */
    private function lines(Collection $orderItems, Collection $meta): array
    {
        $lines = [];
        $promo = null;
        $shippingName = null;

        foreach ($orderItems as $item) {
            $m = ($meta->get($item->order_item_id) ?? collect())->pluck('meta_value', 'meta_key');
            $name = trim(html_entity_decode((string) $item->order_item_name, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if ($item->order_item_type === 'line_item') {
                $qty = max(1, (int) ($m['_qty'] ?? 1));
                // Prices include VAT: gross before discount = subtotal + its tax.
                $gross = round((float) ($m['_line_subtotal'] ?? 0) + (float) ($m['_line_subtotal_tax'] ?? 0), 2);
                $lines[] = $this->line($name, $qty, $gross);
            } elseif ($item->order_item_type === 'fee') {
                $gross = round((float) ($m['_line_total'] ?? 0) + (float) ($m['_line_tax'] ?? 0), 2);
                $lines[] = $this->line($name, 1, $gross);
            } elseif ($item->order_item_type === 'coupon' && $promo === null) {
                $promo = Str::limit(Str::upper($name), 32, '');
            } elseif ($item->order_item_type === 'shipping' && $shippingName === null) {
                $shippingName = Str::limit($name, 190, '');
            }
        }

        return [$lines, $promo, $shippingName];
    }

    /** Same shape as checkout items, minus product_id (never rateable). */
    private function line(string $name, int $qty, float $gross): array
    {
        return [
            'name' => $name,
            'options' => [],
            'quantity' => $qty,
            'unit_price' => round($gross / $qty, 2),
            'line_total' => $gross,
        ];
    }

    private function importRefunds(int $orderId, object $order, Collection $refunds, float $total): void
    {
        $rows = [];
        foreach ($refunds as $refund) {
            $cents = (int) round(abs((float) $refund->total_amount) * 100);
            if ($cents > 0) {
                $rows[] = [$cents, $this->date($refund->date_created_gmt)];
            }
        }
        if ($rows === [] && $order->status === 'wc-refunded' && $total > 0) {
            $rows[] = [(int) round($total * 100), $this->date($order->date_updated_gmt)];
        }

        foreach ($rows as [$cents, $at]) {
            $this->insert('order_refunds', [
                'order_id' => $orderId,
                'staff_user_id' => null,
                'request_key' => (string) Str::uuid(),
                'stripe_refund_id' => null,
                'type' => 'order',
                'amount_cents' => $cents,
                'status' => 'succeeded',
                'created_at' => $at,
                'updated_at' => $at,
            ]);
            $this->stats['refunds_created']++;
        }
    }

    // ---------------------------------------------------------------- helpers

    private function orderNumber(object $order): string
    {
        return sprintf('BM-%s-%06d', $this->date($order->date_created_gmt)->format('Ymd'), $order->id);
    }

    private function countryName(string $code): string
    {
        if ($this->countries === null) {
            $this->countries = $this->db->table('countries')->pluck('name', 'code')
                ->mapWithKeys(fn ($name, $c) => [Str::upper((string) $c) => $name])->all();
        }

        return $this->countries[$code] ?? ($code !== '' ? $code : '');
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        return $value ? CarbonImmutable::parse((string) $value, 'UTC') : null;
    }

    private function loadEmailMap(): void
    {
        $this->emailMap ??= $this->db->table('users')->pluck('id', 'email')
            ->mapWithKeys(fn ($id, $email) => [Str::lower((string) $email) => (int) $id])->all();
    }

    /** Insert without explicit id: the database sequence stays authoritative. */
    private function insert(string $table, array $row): int
    {
        if ($this->dryRun) {
            return --$this->fakeId;
        }

        return (int) $this->db->table($table)->insertGetId($row);
    }

    private function inTransaction(callable $callback): void
    {
        $this->dryRun ? $callback() : $this->db->transaction($callback);
    }
}
