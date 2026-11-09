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
        $hasWholeOrderRefund = $reserved->contains('type', 'order');

        $items = [];
        foreach (($order->items ?? []) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $quantity = max(0, (int) ($item['quantity'] ?? 0));
            // This is exactly the unit amount passed to Stripe Checkout.
            $unitCents = (int) round((float) ($item['discounted_unit_price'] ?? $item['unit_price'] ?? 0) * 100);
            $refundedQuantity = (int) $reserved
                ->where('type', 'item')
                ->where('item_index', $index)
                ->sum('quantity');

            $items[$index] = [
                'index' => $index,
                'name' => (string) ($item['name'] ?? 'Product'),
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
    ): OrderRefund {
        if (! config('services.stripe.secret')) {
            throw ValidationException::withMessages(['refund' => 'Stripe refunds are not configured.']);
        }

        $refund = DB::transaction(function () use ($order, $staff, $requestKey, $type, $itemIndex, $quantity): OrderRefund {
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

            if ($type === 'order') {
                // Refunding the entire remaining balance also includes shipping.
                $amountCents = $summary['remaining_cents'];
                $itemIndex = null;
                $quantity = null;
            } elseif ($type === 'item') {
                $item = $summary['items'][$itemIndex ?? -1] ?? null;
                if (! $item || ! $quantity || $quantity < 1 || $quantity > $item['available_quantity']
                    || $item['unit_cents'] < 1) {
                    throw ValidationException::withMessages(['refund' => 'Select an available item and quantity.']);
                }

                $amountCents = $item['unit_cents'] * $quantity;
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
