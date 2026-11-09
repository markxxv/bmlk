<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\User;
use App\Services\OrderRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class WarehouseRefundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.stripe.secret', 'sk_test_warehouse_refunds');
    }

    public function test_customer_cannot_issue_a_refund(): void
    {
        $order = $this->order();
        $this->actingAs(User::factory()->create());

        $this->post(route('warehouse.orders.refund', $order->order_number), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('order_refunds', 0);
    }

    public function test_staff_can_refund_one_discounted_item_and_sees_mobile_confirmation(): void
    {
        $order = $this->order();
        $this->actingAs($this->staff());
        Http::fake([
            'https://api.stripe.com/v1/refunds' => Http::response([
                'id' => 're_test_item',
                'status' => 'succeeded',
                'amount' => 800,
            ]),
        ]);

        $this->get(route('warehouse.orders.show', $order->order_number))
            ->assertOk()
            ->assertSee('Issue a refund')
            ->assertSee('Final confirmation')
            ->assertSee('I confirm this refund.');

        $this->post(route('warehouse.orders.refund', $order->order_number), $this->payload([
            'type' => 'item',
            'item_index' => 0,
            'quantity' => 1,
        ]))->assertSessionHas('success');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.stripe.com/v1/refunds'
            && (int) $request['amount'] === 800
            && $request['payment_intent'] === 'pi_test_warehouse'
            && $request->hasHeader('Idempotency-Key'));

        $this->assertDatabaseHas('order_refunds', [
            'order_id' => $order->id,
            'type' => 'item',
            'item_index' => 0,
            'quantity' => 1,
            'amount_cents' => 800,
            'status' => 'succeeded',
        ]);

        $this->get(route('warehouse.orders.show', $order->order_number))
            ->assertOk()->assertSee('1 refunded / reserved')->assertSee('€13.00');
    }

    public function test_remaining_full_refund_covers_shipping_and_blocks_shipping_actions(): void
    {
        $order = $this->order();
        $this->actingAs($this->staff());
        $request = 0;
        Http::fake(function () use (&$request) {
            $request++;

            return Http::response([
                'id' => 're_test_'.$request,
                'status' => 'succeeded',
            ]);
        });

        $this->post(route('warehouse.orders.refund', $order->order_number), $this->payload([
            'type' => 'item',
            'item_index' => 0,
            'quantity' => 1,
        ]))->assertSessionHas('success');

        $this->post(route('warehouse.orders.refund', $order->order_number), $this->payload([
            'type' => 'order',
        ]))->assertSessionHas('success');

        $this->assertSame(1300, (int) $order->refunds()->where('type', 'order')->firstOrFail()->amount_cents);
        $this->assertSame(2100, (int) $order->refunds()->sum('amount_cents'));

        $this->get(route('warehouse.orders.index'))->assertOk()->assertDontSee($order->order_number);
        $this->get(route('warehouse.orders.show', $order->order_number))
            ->assertOk()->assertSee('Shipment and fulfillment actions are disabled.');

        $this->post(route('warehouse.orders.status', $order->order_number), [
            'status' => 'processing',
        ])->assertStatus(409);
        $this->post(route('warehouse.orders.create-label', $order->order_number))->assertStatus(409);
    }

    public function test_cannot_refund_more_items_than_purchased(): void
    {
        $order = $this->order();
        $this->actingAs($this->staff());
        Http::fake(['https://api.stripe.com/v1/refunds' => Http::response(['id' => 're_test_qty', 'status' => 'succeeded'])]);

        $this->post(route('warehouse.orders.refund', $order->order_number), $this->payload([
            'type' => 'item',
            'item_index' => 0,
            'quantity' => 3,
        ]))->assertSessionHasErrors('refund');

        $this->assertDatabaseCount('order_refunds', 0);
        Http::assertNothingSent();
    }

    public function test_same_request_key_never_issues_a_second_refund(): void
    {
        $order = $this->order();
        $this->actingAs($this->staff());
        $data = $this->payload(['type' => 'item', 'item_index' => 0, 'quantity' => 1]);
        Http::fake(['https://api.stripe.com/v1/refunds' => Http::response(['id' => 're_test_repeat', 'status' => 'succeeded'])]);

        $this->post(route('warehouse.orders.refund', $order->order_number), $data)->assertSessionHas('success');
        $this->post(route('warehouse.orders.refund', $order->order_number), $data)->assertSessionHas('success');

        $this->assertDatabaseCount('order_refunds', 1);
        Http::assertSentCount(1);
    }

    public function test_uncertain_stripe_response_reserves_money_to_avoid_duplicate_refund(): void
    {
        $order = $this->order();
        $this->actingAs($this->staff());
        Http::fake(['https://api.stripe.com/v1/refunds' => Http::response(['error' => ['message' => 'Temporary outage']], 500)]);

        $this->post(route('warehouse.orders.refund', $order->order_number), $this->payload([
            'type' => 'order',
        ]))->assertSessionHasErrors('refund');

        $this->assertDatabaseHas('order_refunds', [
            'order_id' => $order->id,
            'type' => 'order',
            'status' => 'needs_review',
            'amount_cents' => 2100,
        ]);

        $this->post(route('warehouse.orders.refund', $order->order_number), $this->payload())
            ->assertSessionHasErrors('refund');
        Http::assertSentCount(1);
    }

    public function test_stripe_webhook_updates_the_record_only_for_the_matching_payment(): void
    {
        $order = $this->order();
        $refund = $order->refunds()->create([
            'staff_user_id' => $this->staff()->id,
            'request_key' => (string) Str::uuid(),
            'type' => 'item',
            'item_index' => 0,
            'quantity' => 1,
            'amount_cents' => 800,
            'status' => 'pending',
        ]);

        $payload = [
            'id' => 're_webhook_test',
            'status' => 'succeeded',
            'amount' => 800,
            'payment_intent' => 'pi_test_warehouse',
            'metadata' => ['request_key' => $refund->request_key, 'order_id' => (string) $order->id],
        ];

        app(OrderRefundService::class)->syncWebhook(array_replace($payload, ['payment_intent' => 'pi_wrong']));
        $this->assertSame('pending', $refund->fresh()->status);

        app(OrderRefundService::class)->syncWebhook($payload);
        $this->assertSame('succeeded', $refund->fresh()->status);
        $this->assertSame('re_webhook_test', $refund->fresh()->stripe_refund_id);
    }

    private function staff(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_warehouse' => true])->save();

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return [
            'request_key' => (string) Str::uuid(),
            'type' => 'order',
            'confirmation' => 'CONFIRM REFUND',
            ...$overrides,
        ];
    }

    private function order(): Order
    {
        return Order::query()->create([
            'first_name' => 'Marie',
            'email' => 'marie@example.com',
            'country' => 'France',
            'city' => 'Paris',
            'address' => '1 Rue de Test',
            'items' => [[
                'name' => 'Nail care',
                'quantity' => 2,
                'unit_price' => 10,
                'discounted_unit_price' => 8,
                'line_total' => 20,
            ]],
            'subtotal' => 20,
            'discount_amount' => 4,
            'delivery_cost' => 5,
            'total_amount' => 21,
            'status' => 'pending',
            'payment_status' => 'paid',
            'stripe_payment_intent_id' => 'pi_test_warehouse',
        ]);
    }
}
