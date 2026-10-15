<?php

namespace Tests\Feature;

use App\Http\Controllers\StripeController;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class StripeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        config()->set('services.stripe.secret', 'sk_test_fake');
        config()->set('services.stripe.webhook_secret', 'whsec_test');
    }

    public function test_checkout_creates_a_session_and_keeps_the_order_unpaid(): void
    {
        $order = $this->order();

        Http::fake(['api.stripe.com/*' => Http::response([
            'id' => 'cs_test_one',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_one',
        ])]);

        $url = app(StripeController::class)->checkoutUrl($order);

        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_one', $url);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'unpaid',
            'stripe_session_id' => 'cs_test_one',
        ]);
    }

    public function test_only_valid_stripe_webhooks_confirm_payment(): void
    {
        $order = $this->order();
        $order->update(['stripe_session_id' => 'cs_test_one']);

        $event = [
            'type' => 'checkout.session.completed',
            'data' => ['object' => $this->session($order)],
        ];

        $this->withHeader('Stripe-Signature', 't=1,v1=wrong')
            ->postJson('/stripe/webhook', $event)->assertStatus(400);

        $this->assertSame('unpaid', $order->fresh()->payment_status);

        $body = json_encode($event);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_test');

        $this->withHeader('Stripe-Signature', "t={$timestamp},v1={$signature}")
            ->postJson('/stripe/webhook', $event)->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertNotNull($order->fresh()->paid_at);

        // Duplicate events must not change an already paid order.
        $paidAt = $order->fresh()->paid_at;
        $this->postJson('/stripe/webhook', $event)->assertOk();
        $this->assertSame($paidAt, $order->fresh()->paid_at);
    }

    public function test_legacy_order_link_without_token_shows_recovery_instead_of_404(): void
    {
        $order = $this->order();

        $this->get(route('order.status', ['orderNumber' => $order->order_number]))
            ->assertOk()
            ->assertSee(__('Reprendre votre commande'))
            ->assertDontSee('Rue de Test 1');

        $this->get(route('order.status', [
            'orderNumber' => $order->order_number,
            'token' => $order->access_token,
        ]))->assertOk()->assertSee('Gel');
    }

    public function test_customer_can_recover_existing_order_without_rebuilding_cart(): void
    {
        $order = $this->order();
        $order->update(['email' => 'Marie@example.com']);

        $this->post(route('order.recover.submit'), [
            'order_number' => $order->order_number,
            'phone' => '01 02 03 04 05',
            'email' => 'marie@example.com',
        ])->assertRedirect(route('order.status', [
            'orderNumber' => $order->order_number,
            'token' => $order->access_token,
        ]));

        $this->get(route('order.status', [
            'orderNumber' => $order->order_number,
        ]))->assertOk()->assertSee('Gel');
    }

    public function test_wrong_recovery_details_do_not_grant_access(): void
    {
        $order = $this->order();
        $order->update(['email' => 'marie@example.com']);

        $this->from(route('order.recover'))->post(route('order.recover.submit'), [
            'order_number' => $order->order_number,
            'phone' => '9999999999',
            'email' => 'marie@example.com',
        ])->assertRedirect(route('order.recover'))->assertSessionHasErrors('order_number');

        $this->get(route('order.status', ['orderNumber' => $order->order_number]))
            ->assertSee(__('Reprendre votre commande'))
            ->assertDontSee('Rue de Test 1');
    }

    public function test_legacy_order_without_email_can_recover_with_delivery_city(): void
    {
        $order = $this->order();

        $this->post(route('order.recover.submit'), [
            'order_number' => $order->order_number,
            'phone' => '0102030405',
            'city' => 'paris',
        ])->assertRedirect(route('order.status', [
            'orderNumber' => $order->order_number,
            'token' => $order->access_token,
        ]));
    }

    public function test_failed_delayed_payment_can_create_new_checkout_session(): void
    {
        $order = $this->order();
        $order->update(['stripe_session_id' => 'cs_test_one', 'payment_status' => 'failed']);

        Http::fake([
            'https://api.stripe.com/v1/checkout/sessions/cs_test_one' => Http::response($this->session($order, 'unpaid')),
            'https://api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_two',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_two',
            ]),
        ]);

        $url = app(StripeController::class)->checkoutUrl($order->fresh());

        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_two', $url);
        $this->assertSame('cs_test_two', $order->fresh()->stripe_session_id);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        Http::assertSentCount(2);
    }

    public function test_retry_reuses_an_open_checkout_session(): void
    {
        $order = $this->order();
        $order->update(['stripe_session_id' => 'cs_test_one']);

        Http::fake(['api.stripe.com/*' => Http::response(
            $this->session($order, 'unpaid') + [
                'status' => 'open',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_one',
            ],
        )]);

        $this->post(route('order.pay', [
            'orderNumber' => $order->order_number,
            'token' => $order->access_token,
        ]))->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_one');

        Http::assertSentCount(1);
    }

    public function test_failed_stripe_creation_keeps_order_for_retry(): void
    {
        $order = $this->order();

        Http::fake(['api.stripe.com/*' => Http::response([
            'error' => ['message' => 'Invalid API key'],
        ], 401)]);

        $url = route('order.pay', [
            'orderNumber' => $order->order_number,
            'token' => $order->access_token,
        ]);

        $this->postJson($url)->assertStatus(503)
            ->assertJsonPath('message', __('Stripe ne répond pas pour le moment. Veuillez réessayer.'));

        $this->assertSame(1, Order::query()->count());
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_browser_session_can_recover_order_without_a_query_token(): void
    {
        $order = $this->order();

        $this->withSession([
            "bmlk_orders.{$order->id}" => $order->access_token,
        ])->get(route('order.status', [
            'orderNumber' => $order->order_number,
        ]))->assertOk();
    }

    private function order(): Order
    {
        return Order::query()->create([
            'access_token' => Str::random(48),
            'first_name' => 'Marie',
            'phone' => '0102030405',
            'country' => 'France',
            'city' => 'Paris',
            'address' => 'Rue de Test 1',
            'items' => [[
                'product_id' => 1,
                'name' => 'Gel',
                'unit_price' => 12,
                'quantity' => 1,
                'line_total' => 12,
            ]],
            'subtotal' => 12,
            'delivery_cost' => 3,
            'total_amount' => 15,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);
    }

    private function session(Order $order, string $paymentStatus = 'paid'): array
    {
        return [
            'id' => 'cs_test_one',
            'mode' => 'payment',
            'status' => $paymentStatus === 'paid' ? 'complete' : 'open',
            'payment_status' => $paymentStatus,
            'currency' => 'eur',
            'amount_total' => 1500,
            'payment_intent' => 'pi_test_one',
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
            ],
        ];
    }
}
