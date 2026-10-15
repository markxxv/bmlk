<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class StripeController extends Controller
{
    // All Stripe API, checkout, retry and webhook logic stays in this controller.
    public function checkoutUrl(Order $order): ?string
    {
        if ($order->payment_status === 'paid') {
            return $this->thanksUrl($order);
        }

        if (! config('services.stripe.secret')) {
            Log::warning('Stripe secret key is not configured.');
            return null;
        }

        try {
            if ($order->stripe_session_id) {
                $session = $this->retrieve($order->stripe_session_id);
                if (! $session) {
                    return null; // Avoid another charge while Stripe cannot be reached.
                }

                if (($session['status'] ?? null) === 'complete') {
                    $this->sync($session, 'checkout.session.completed');
                    $order->refresh();
                }

                if ($order->payment_status === 'paid') {
                    return $this->thanksUrl($order);
                }

                if (($session['status'] ?? null) === 'open') {
                    return $session['url'] ?? null;
                }

                if (($session['status'] ?? null) === 'complete') {
                    return null; // An asynchronous payment is being processed.
                }
            }

            $order->increment('payment_attempts');

            $items = collect($order->items)->map(function (array $item): array {
                $options = collect($item['options'] ?? [])->pluck('display')->filter()->implode(', ');
                return [
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => (int) round((float) $item['unit_price'] * 100),
                        'product_data' => ['name' => Str::limit($item['name'].($options ? ' — '.$options : ''), 150)],
                    ],
                    'quantity' => (int) $item['quantity'],
                ];
            })->values()->all();

            if ((float) $order->delivery_cost > 0) {
                $items[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => (int) round((float) $order->delivery_cost * 100),
                        'product_data' => ['name' => 'Livraison'],
                    ],
                    'quantity' => 1,
                ];
            }

            $params = [
                'mode' => 'payment',
                'client_reference_id' => (string) $order->id,
                'metadata' => ['order_id' => (string) $order->id, 'order_number' => $order->order_number],
                'payment_intent_data' => ['metadata' => ['order_number' => $order->order_number]],
                'line_items' => $items,
                'success_url' => $this->thanksUrl($order).'&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $this->statusUrl($order).'&cancelled=1',
            ];
            if ($order->email) {
                $params['customer_email'] = $order->email;
            }

            $response = Http::asForm()
                ->withToken(config('services.stripe.secret'))
                ->withHeaders(['Idempotency-Key' => "bmlk-order-{$order->id}-attempt-{$order->payment_attempts}"])
                ->timeout(15)
                ->post('https://api.stripe.com/v1/checkout/sessions', $params);

            if (! $response->successful() || ! $response->json('id') || ! $response->json('url')) {
                Log::warning('Stripe session creation failed.', [
                    'order_id' => $order->id,
                    'http_status' => $response->status(),
                    'error' => $response->json('error.message'),
                ]);
                return null;
            }

            $changed = Order::query()
                ->whereKey($order->id)
                ->where('payment_status', '!=', 'paid')
                ->update([
                    'stripe_session_id' => $response->json('id'),
                    'payment_status' => 'unpaid',
                    'updated_at' => now(),
                ]);

            return $changed ? $response->json('url') : $this->thanksUrl($order);
        } catch (Throwable $e) {
            Log::warning('Stripe Checkout unavailable.', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function retry(Request $request, string $orderNumber): RedirectResponse
    {
        $order = $this->authorizedOrder($request, $orderNumber);
        return redirect()->away($this->checkoutUrl($order) ?: $this->statusUrl($order));
    }

    public function thanks(Request $request, string $orderNumber): View|RedirectResponse
    {
        $order = $this->authorizedOrder($request, $orderNumber);

        // A browser redirect may precede Stripe's webhook.
        if ($order->payment_status !== 'paid' && $order->stripe_session_id
            && $request->query('session_id') === $order->stripe_session_id) {
            try {
                if ($session = $this->retrieve($order->stripe_session_id)) {
                    $this->sync($session, 'checkout.session.completed');
                    $order->refresh();
                }
            } catch (Throwable $e) {
                Log::warning('Stripe return verification failed.', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        if ($order->payment_status !== 'paid') {
            return redirect()->to($this->statusUrl($order));
        }

        return view('shop.thanks', [
            'order' => $order,
            'metaTitle' => __('Merci pour votre commande | BLACK MILK'),
        ]);
    }

    public function webhook(Request $request): Response
    {
        $secret = config('services.stripe.webhook_secret');
        $timestamp = null;
        $signatures = [];

        foreach (explode(',', (string) $request->header('Stripe-Signature')) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key === 't') {
                $timestamp = $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if (! $secret || ! $timestamp || ! ctype_digit($timestamp)
            || abs(time() - (int) $timestamp) > 300 || ! $signatures) {
            return response('Invalid signature', 400);
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        if (! collect($signatures)->contains(fn ($v): bool => is_string($v) && hash_equals($expected, $v))) {
            return response('Invalid signature', 400);
        }

        $event = json_decode($request->getContent(), true);

        if (! is_array($event)) {
            return response('Invalid event', 400);
        }

        $type = $event['type'] ?? null;
        if (in_array($type, [
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded',
            'checkout.session.async_payment_failed',
            'checkout.session.expired',
        ], true) && is_array(data_get($event, 'data.object'))) {
            $this->sync(data_get($event, 'data.object'), $type);
        }

        return response('OK', 200);
    }

    private function sync(array $session, string $event): void
    {
        $order = Order::query()->find(data_get($session, 'metadata.order_id'));
        $sessionId = (string) ($session['id'] ?? '');

        if (! $order
            || $order->order_number !== data_get($session, 'metadata.order_number')
            || ($session['mode'] ?? null) !== 'payment'
            || strtolower((string) ($session['currency'] ?? '')) !== 'eur'
            || (int) ($session['amount_total'] ?? -1) !== (int) round((float) $order->total_amount * 100)
            || ! Str::startsWith($sessionId, 'cs_')) {
            return;
        }

        $paid = ($session['payment_status'] ?? null) === 'paid'
            && in_array($event, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true);

        // Stale failure webhooks must not overwrite newer attempts.
        if (! $paid && $order->stripe_session_id !== $sessionId) {
            return;
        }

        if ($order->payment_status === 'paid') {
            return; // Idempotent for repeated and out-of-order webhooks.
        }

        if ($paid) {
            Order::query()->whereKey($order->id)->where('payment_status', '!=', 'paid')->update([
                'payment_status' => 'paid',
                'stripe_session_id' => $sessionId,
                'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
                'paid_at' => now(),
                'updated_at' => now(),
            ]);
        } elseif ($event === 'checkout.session.completed') {
            Order::query()->whereKey($order->id)->where('payment_status', '!=', 'paid')
                ->update(['payment_status' => 'processing', 'updated_at' => now()]);
        } elseif (in_array($event, ['checkout.session.async_payment_failed', 'checkout.session.expired'], true)) {
            Order::query()->whereKey($order->id)->where('payment_status', '!=', 'paid')
                ->update(['payment_status' => 'failed', 'updated_at' => now()]);
        }
    }

    private function retrieve(string $sessionId): ?array
    {
        $response = Http::withToken(config('services.stripe.secret'))
            ->timeout(12)
            ->get('https://api.stripe.com/v1/checkout/sessions/'.rawurlencode($sessionId));

        return $response->successful() ? $response->json() : null;
    }

    private function authorizedOrder(Request $request, string $number): Order
    {
        $order = Order::query()->where('order_number', $number)->firstOrFail();
        abort_unless($order->access_token && hash_equals($order->access_token, (string) $request->query('token', '')), 404);
        return $order;
    }

    private function statusUrl(Order $order): string
    {
        return route('order.status', ['orderNumber' => $order->order_number, 'token' => $order->access_token]);
    }

    private function thanksUrl(Order $order): string
    {
        return route('order.thanks', ['orderNumber' => $order->order_number, 'token' => $order->access_token]);
    }
}
