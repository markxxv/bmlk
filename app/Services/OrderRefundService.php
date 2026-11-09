<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrderRefundService
{
    private const EXCLUDED_STATUSES = ['failed', 'canceled'];

    public function summary(Order $order): array
    {
        $refunds = $order->refunds()->latest('id')->get();
        $reserved = $refunds->reject(fn (OrderRefund $refund) => in_array($refund->status, self::EXCLUDED_STATUSES, true));
        $totalCents = (int) round((float) $order->total_amount * 100);
        $reservedCents = (int) $reserved->sum('amount_cents');
        $hasWholeOrderRefund = $reserved->contains('type', 'order') || ($totalCents > 0 && $reservedCents >= $totalCents);

        // Legacy single-item refunds and new multi-item refunds reserve quantities
        // in exactly the same way, including pending or uncertain Stripe outcomes.
        $reservedQuantities = [];
        foreach ($reserved as $refund) {
            if ($refund->type === 'item' && $refund->item_index !== null) {
                $index = (int) $refund->item_index;
                $reservedQuantities[$index] = ($reservedQuantities[$index] ?? 0) + (int) $refund->quantity;
            } elseif ($refund->type === 'items') {
                foreach (($refund->items ?? []) as $line) {
                    if (! is_array($line) || ! isset($line['item_index'], $line['quantity'])) {
                        continue;
                    }
                    $index = (int) $line['item_index'];
                    $reservedQuantities[$index] = ($reservedQuantities[$index] ?? 0) + (int) $line['quantity'];
                }
            }
        }

        $items = [];
        foreach (($order->items ?? []) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $quantity = max(0, (int) ($item['quantity'] ?? 0));
            // This is exactly the unit amount passed to Stripe Checkout.
            $unitCents = (int) round((float) ($item['discounted_unit_price'] ?? $item['unit_price'] ?? 0) * 100);
            $refundedQuantity = $reservedQuantities[$index] ?? 0;

            $items[$index] = [
                'index' => $index,
                'name' => (string) ($item['name'] ?? 'Product'),
                'image' => (string) ($item['image'] ?? ''),
                'options' => collect($item['options'] ?? [])
                    ->pluck('display')->filter()->implode(' · '),
                'quantity' => $quantity,
                'unit_cents' => max(0, $unitCents),
                'refunded_quantity' => $refundedQuantity,
                'available_quantity' => $hasWholeOrderRefund ? 0 : max(0, $quantity - $refundedQuantity),
            ];
        }

        return [
            'total_cents' => $totalCents,
            'reserved_cents' => $reservedCents,
            'remaining_cents' => max(0, $totalCents - $reservedCents),
            'has_whole_refund' => $hasWholeOrderRefund,
            'can_refund' => $order->payment_status === 'paid'
                && str_starts_with((string) $order->stripe_payment_intent_id, 'pi_')
                && $reservedCents < $totalCents
                && ! $hasWholeOrderRefund,
            'items' => $items,
            'history' => $refunds,
        ];
    }

    public function create(
        Order $order,
        User $staff,
        string $requestKey,
        string $type,
        ?int $itemIndex,
        ?int $quantity,
        ?array $selectedItems = null,
    ): OrderRefund {
        if (! config('services.stripe.secret')) {
            throw ValidationException::withMessages(['refund' => 'Stripe refunds are not configured.']);
        }

        $refund = DB::transaction(function () use ($order, $staff, $requestKey, $type, $itemIndex, $quantity, $selectedItems): OrderRefund {
            // Lock the parent order so simultaneous staff requests cannot over-refund.
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $existing = OrderRefund::query()->where('request_key', $requestKey)->first();

            if ($existing) {
                if ($existing->order_id !== $lockedOrder->id || $existing->staff_user_id !== $staff->id) {
                    throw ValidationException::withMessages(['refund' => 'This refund request is invalid.']);
                }

                return $existing;
            }

            $summary = $this->summary($lockedOrder);
            if (! $summary['can_refund']) {
                throw ValidationException::withMessages(['refund' => 'This order is not available for a refund.']);
            }

            $itemLines = null;

            if ($type === 'order') {
                // Refunding the entire remaining balance also includes shipping.
                $amountCents = $summary['remaining_cents'];
                $itemIndex = null;
                $quantity = null;
            } elseif ($type === 'item' || $type === 'items') {
                // Keep accepting legacy single-item requests. New mobile UI sends
                // the whole selection as one atomic Stripe refund.
                $selections = $type === 'item'
                    ? [['item_index' => $itemIndex, 'quantity' => $quantity]]
                    : $selectedItems;

                if (! is_array($selections) || count($selections) < 1 || count($selections) > 50) {
                    throw ValidationException::withMessages(['refund' => 'Select at least one product.']);
                }

                $seen = [];
                $itemLines = [];
                $amountCents = 0;
                foreach ($selections as $selection) {
                    if (! is_array($selection)
                        || ! isset($selection['item_index'], $selection['quantity'])
                        || filter_var($selection['item_index'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
                        throw ValidationException::withMessages(['refund' => 'Invalid product selection.']);
                    }

                    $index = (int) $selection['item_index'];
                    $lineQty = filter_var($selection['quantity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                    $item = $summary['items'][$index] ?? null;

                    if ($lineQty === false || isset($seen[$index]) || ! $item
                        || $lineQty > $item['available_quantity'] || $item['unit_cents'] < 1) {
                        throw ValidationException::withMessages(['refund' => 'Check the selected products and quantities.']);
                    }

                    $seen[$index] = true;
                    $itemLines[] = [
                        'item_index' => $index,
                        'quantity' => $lineQty,
                        'unit_cents' => $item['unit_cents'],
                        'name' => $item['name'],
                    ];
                    $amountCents += $item['unit_cents'] * $lineQty;
                }

                // Legacy refund rows retain their original fields for compatibility.
                if ($type === 'items') {
                    $itemIndex = null;
                    $quantity = null;
                }
            } else {
                throw ValidationException::withMessages(['refund' => 'Invalid refund type.']);
            }

            if ($amountCents < 1 || $amountCents > $summary['remaining_cents']) {
                throw ValidationException::withMessages(['refund' => 'Refund amount exceeds the remaining balance.']);
            }

            return $lockedOrder->refunds()->create([
                'staff_user_id' => $staff->id,
                'request_key' => $requestKey,
                'type' => $type,
                'item_index' => $itemIndex,
                'quantity' => $quantity,
                'items' => $type === 'items' ? $itemLines : null,
                'amount_cents' => $amountCents,
                'status' => 'initiated',
            ]);
        });

        if ($refund->status !== 'initiated') {
            return $refund;
        }

        try {
            $response = Http::asForm()
                ->withToken(config('services.stripe.secret'))
                ->withHeaders(['Idempotency-Key' => 'bmlk-refund-'.$refund->request_key])
                ->timeout(20)
                ->post('https://api.stripe.com/v1/refunds', [
                    'payment_intent' => $order->stripe_payment_intent_id,
                    'amount' => $refund->amount_cents,
                    'reason' => 'requested_by_customer',
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'order_number' => $order->order_number,
                        'request_key' => $refund->request_key,
                        'type' => $refund->type,
                        'item_index' => (string) ($refund->item_index ?? ''),
                        'quantity' => (string) ($refund->quantity ?? ''),
                        'item_count' => (string) count($refund->items ?? []),
                    ],
                ]);

            if ($response->successful() && str_starts_with((string) $response->json('id'), 're_')) {
                $status = (string) $response->json('status', 'pending');
                $refund->refresh();
                $refund->forceFill([
                    'stripe_refund_id' => $response->json('id'),
                    // An earlier webhook might already have marked this succeeded.
                    'status' => $refund->status === 'succeeded' ? 'succeeded' : $status,
                ])->save();

                return $refund->fresh();
            }

            // Timeout, rate limit and 5xx responses may have created a refund remotely.
            // Keep their amount reserved; a second refund with a new key is unsafe.
            $uncertain = $response->successful() || $response->status() === 409
                || $response->status() === 429 || $response->serverError();
            $refund->refresh();
            if ($refund->status !== 'succeeded') {
                $refund->forceFill(['status' => $uncertain ? 'needs_review' : 'failed'])->save();
            }
            Log::warning('Warehouse Stripe refund rejected.', [
                'order_id' => $order->id,
                'refund_id' => $refund->id,
                'http_status' => $response->status(),
            ]);
        } catch (Throwable $exception) {
            $refund->refresh();
            if ($refund->status !== 'succeeded') {
                $refund->forceFill(['status' => 'needs_review'])->save();
            }
            Log::warning('Warehouse Stripe refund result unknown.', [
                'order_id' => $order->id,
                'refund_id' => $refund->id,
                'exception' => get_class($exception),
            ]);
        }

        return $refund->fresh();
    }

    public function refreshStatus(OrderRefund $refund): OrderRefund
    {
        if (! config('services.stripe.secret')) {
            return $refund;
        }

        $order = $refund->order;

        try {
            if ($refund->stripe_refund_id) {
                $response = Http::withToken(config('services.stripe.secret'))
                    ->timeout(12)
                    ->get('https://api.stripe.com/v1/refunds/'.rawurlencode($refund->stripe_refund_id));

                if ($response->successful() && is_array($response->json())) {
                    $this->syncWebhook($response->json());
                }
            } elseif ($order->stripe_payment_intent_id) {
                // Reconcile an ambiguous POST by inspecting Stripe, never by sending a new refund.
                $response = Http::withToken(config('services.stripe.secret'))
                    ->timeout(12)
                    ->get('https://api.stripe.com/v1/refunds', [
                        'payment_intent' => $order->stripe_payment_intent_id,
                        'limit' => 100,
                    ]);

                if ($response->successful()) {
                    foreach ($response->json('data', []) as $data) {
                        if (is_array($data) && data_get($data, 'metadata.request_key') === $refund->request_key) {
                            $this->syncWebhook($data);
                            break;
                        }
                    }
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Warehouse Stripe refund status check failed.', [
                'order_id' => $order->id,
                'refund_id' => $refund->id,
                'exception' => get_class($exception),
            ]);
        }

        return $refund->fresh();
    }

    public function syncWebhook(array $data): void
    {
        $requestKey = data_get($data, 'metadata.request_key');
        $stripeId = data_get($data, 'id');
        $status = data_get($data, 'status');

        if (! is_string($requestKey) || ! is_string($stripeId) || ! str_starts_with($stripeId, 're_')
            || ! in_array($status, ['pending', 'requires_action', 'succeeded', 'failed', 'canceled'], true)) {
            return;
        }

        $refund = OrderRefund::query()->where('request_key', $requestKey)->first();
        if (! $refund || $refund->amount_cents !== (int) ($data['amount'] ?? -1)
            || (string) $refund->order_id !== (string) data_get($data, 'metadata.order_id')
            || (string) $refund->order->stripe_payment_intent_id !== (string) ($data['payment_intent'] ?? '')) {
            return;
        }

        if ($refund->stripe_refund_id && $refund->stripe_refund_id !== $stripeId) {
            return;
        }

        // An older pending event must not revert a terminal status.
        if (in_array($refund->status, ['succeeded', 'failed', 'canceled'], true)
            && ! in_array($status, ['succeeded', 'failed', 'canceled'], true)) {
            return;
        }

        $refund->forceFill([
            'stripe_refund_id' => $stripeId,
            'status' => $status,
        ])->save();
    }
}
