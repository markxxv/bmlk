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

        $this->get(route('warehouse.orders.index'))->assertOk()->assertSee($order->order_number);
        $this->get(route('warehouse.orders.index', ['state' => 'pending']))
            ->assertOk()->assertDontSee($order->order_number);
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

    public function test_all_item_refunds_also_prevent_fulfillment_when_shipping_is_free(): void
    {
        $order = $this->order();
        $order->forceFill(['delivery_cost' => 0, 'total_amount' => 16])->save();
        $this->actingAs($this->staff());

        $counter = 0;
        Http::fake(function () use (&$counter) {
            $counter++;

            return Http::response([
                'id' => 're_items_'.$counter,
                'status' => 'succeeded',
            ]);
        });

        for ($i = 0; $i < 2; $i++) {
            $this->post(route('warehouse.orders.refund', $order->order_number), $this->payload([
                'type' => 'item',
                'item_index' => 0,
                'quantity' => 1,
            ]))->assertSessionHas('success');
        }

        $this->assertSame(1600, (int) $order->refunds()->sum('amount_cents'));
        $this->get(route('warehouse.orders.index'))->assertOk()->assertSee($order->order_number);
        $this->get(route('warehouse.orders.index', ['state' => 'pending']))
            ->assertOk()->assertDontSee($order->order_number);
        $this->get(route('warehouse.orders.show', $order->order_number))
            ->assertOk()->assertSee('Full payment refunded or reserved.');
        $this->post(route('warehouse.orders.status', $order->order_number), ['status' => 'processing'])
            ->assertStatus(409);
    }

    public function test_staff_can_check_an_uncertain_refund_without_creating_another_one(): void
    {
        $order = $this->order();
        $staff = $this->staff();
        $this->actingAs($staff);
        $refund = $order->refunds()->create([
            'staff_user_id' => $staff->id,
            'request_key' => (string) Str::uuid(),
            'type' => 'order',
            'amount_cents' => 2100,
            'status' => 'needs_review',
        ]);

        Http::fake(function ($request) use ($refund, $order) {
            $this->assertSame('GET', $request->method());

            return Http::response(['data' => [[
                'id' => 're_confirmed',
                'amount' => 2100,
                'status' => 'succeeded',
                'payment_intent' => 'pi_test_warehouse',
                'metadata' => [
                    'request_key' => $refund->request_key,
                    'order_id' => (string) $order->id,
                ],
            ]]]);
        });

        $this->post(route('warehouse.orders.refunds.refresh', [$order->order_number, $refund->id]))
            ->assertSessionHas('success');

        $this->assertSame('succeeded', $refund->fresh()->status);
        $this->assertSame('re_confirmed', $refund->fresh()->stripe_refund_id);
        Http::assertSentCount(1);
    }

    public function test_staff_can_refund_several_products_with_independent_quantities_in_one_stripe_request(): void
    {
        $order = $this->multiProductOrder();
        $this->actingAs($this->staff());
        Http::fake([
            'https://api.stripe.com/v1/refunds' => Http::response([
                'id' => 're_test_multi',
                'status' => 'succeeded',
                'amount' => 1800,
            ]),
        ]);

        $this->get(route('warehouse.orders.show', $order->order_number))
            ->assertOk()
            ->assertSee('Select products')
            ->assertSee('Choose products and quantities')
            ->assertSee('data-refund-check', false)
            ->assertSee('data-refund-minus', false)
            ->assertSee('data-refund-plus', false)
            ->assertSee('name="items[0][item_index]"', false)
            ->assertSee('name="items[1][quantity]"', false)
            ->assertDontSee('id="refund-quantity-minus"', false);

        $this->post(route('warehouse.orders.refund', $order->order_number), $this->payload([
            'type' => 'items',
            'items' => [
                ['item_index' => 0, 'quantity' => 1],
                ['item_index' => 1, 'quantity' => 2],
            ],
        ]))->assertSessionHas('success');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.stripe.com/v1/refunds'
            && (int) $request['amount'] === 1800
            && $request['payment_intent'] === 'pi_test_warehouse'
            && $request->hasHeader('Idempotency-Key'));

        $refund = $order->refunds()->sole();
        $this->assertSame('items', $refund->type);
        $this->assertSame(1800, $refund->amount_cents);
        $this->assertNull($refund->item_index);
        $this->assertNull($refund->quantity);
        $this->assertCount(2, $refund->items);
        $this->assertSame([
            ['item_index' => 0, 'quantity' => 1, 'unit_cents' => 800, 'name' => 'Nail care'],
            ['item_index' => 1, 'quantity' => 2, 'unit_cents' => 500, 'name' => 'Cuticle oil'],
        ], $refund->items);

        $summary = app(OrderRefundService::class)->summary($order);
        $this->assertSame(2200, $summary['remaining_cents']);
        $this->assertSame(1, $summary['items'][0]['available_quantity']);
        $this->assertSame(1, $summary['items'][1]['available_quantity']);
        $this->assertSame(1, $summary['items'][2]['available_quantity']);

        $this->get(route('warehouse.orders.show', $order->order_number))
            ->assertOk()
            ->assertSee('Nail care × 1')
            ->assertSee('Cuticle oil × 2');
        $this->get(route('warehouse.orders.index'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Partial refund')
            ->assertSee('3 items');
    }

    public function test_multi_product_refund_rejects_invalid_duplicate_and_excessive_quantities(): void
    {
        $order = $this->multiProductOrder();
        $this->actingAs($this->staff());
        Http::fake();

        foreach ([
            [],
            [['item_index' => 0, 'quantity' => 0]],
            [['item_index' => 1, 'quantity' => 4]],
            [['item_index' => 99, 'quantity' => 1]],
            [['item_index' => 0, 'quantity' => 1], ['item_index' => 0, 'quantity' => 1]],
        ] as $items) {
            $this->post(route('warehouse.orders.refund', $order->order_number), $this->payload([
                'type' => 'items',
                'items' => $items,
            ]))->assertSessionHasErrors();
        }

        $this->assertDatabaseCount('order_refunds', 0);
        Http::assertNothingSent();
    }

    public function test_multi_item_refund_respects_legacy_refunds_and_remains_idempotent(): void
    {
        $order = $this->multiProductOrder();
        $this->actingAs($this->staff());
        $order->refunds()->create([
            'staff_user_id' => auth()->id(),
            'request_key' => (string) Str::uuid(),
            'type' => 'item',
            'item_index' => 0,
            'quantity' => 1,
            'amount_cents' => 800,
            'status' => 'succeeded',
        ]);

        $data = $this->payload([
            'type' => 'items',
            'items' => [
                ['item_index' => 0, 'quantity' => 1],
                ['item_index' => 1, 'quantity' => 3],
            ],
        ]);
        Http::fake([
            'https://api.stripe.com/v1/refunds' => Http::response(['id' => 're_test_batch', 'status' => 'succeeded']),
        ]);

        $this->post(route('warehouse.orders.refund', $order->order_number), $data)->assertSessionHas('success');
        $this->post(route('warehouse.orders.refund', $order->order_number), $data)->assertSessionHas('success');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => (int) $request['amount'] === 2300);
        $this->assertSame(3100, (int) $order->refunds()->sum('amount_cents'));
        $summary = app(OrderRefundService::class)->summary($order);
        $this->assertSame(0, $summary['items'][0]['available_quantity']);
        $this->assertSame(0, $summary['items'][1]['available_quantity']);

        $this->post(route('warehouse.orders.refund', $order->order_number), $this->payload([
            'type' => 'items',
            'items' => [['item_index' => 1, 'quantity' => 1]],
        ]))->assertSessionHasErrors('refund');
        Http::assertSentCount(1);
    }

    private function multiProductOrder(): Order
    {
        $order = $this->order();
        $order->forceFill([
            'items' => [
                ['name' => 'Nail care', 'quantity' => 2, 'unit_price' => 10,
                    'discounted_unit_price' => 8, 'line_total' => 20],
                ['name' => 'Cuticle oil', 'quantity' => 3, 'unit_price' => 6,
                    'discounted_unit_price' => 5, 'line_total' => 18],
                ['name' => 'File', 'quantity' => 1, 'unit_price' => 4,
                    'discounted_unit_price' => 4, 'line_total' => 4],
            ],
            'subtotal' => 42,
            'discount_amount' => 7,
            'delivery_cost' => 5,
            'total_amount' => 40,
        ])->save();

        return $order;
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
