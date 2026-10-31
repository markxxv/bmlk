<?php

namespace Tests\Feature;

use App\Http\Controllers\SendcloudController;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendcloudWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.sendcloud.webhook_secret', 'sendcloud-test-secret');
    }

    public function test_webhook_requires_a_valid_signature(): void
    {
        $this->postJson(route('sendcloud.webhook'), ['action' => 'test'])->assertUnauthorized();
    }

    public function test_tracking_changes_status_to_shipped_and_delivery_to_completed(): void
    {
        $order = $this->order();
        $this->event($order, 'Processing', 1000, 'TRK-123');

        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertSame('TRK-123', $order->fresh()->tracking_number);

        $this->event($order, 'Delivered', 1001, 'TRK-123');
        $this->assertSame('completed', $order->fresh()->status);

        // Even a newer carrier update cannot regress a delivered order.
        $this->event($order, 'Shipped', 1002, 'TRK-123');
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_creating_label_with_tracking_marks_order_shipped(): void
    {
        $order = $this->order();
        $order->update([
            'shipping_country_code' => 'FR',
            'sendcloud_order_id' => 'sc-100',
        ]);
        Cache::put('sendcloud.integration_id', 55);

        Http::fake([
            'https://panel.sendcloud.sc/api/v3/orders/create-label-sync' => Http::response([
                'data' => [[
                    'tracking_number' => 'TRK-456',
                    'tracking_url' => 'https://tracking.example/456',
                    'parcel_id' => 'sc-parcel-456',
                    'shipment_id' => 'sc-shipment-456',
                ]],
            ]),
        ]);

        app(SendcloudController::class)->createLabel($order);

        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertSame('TRK-456', $order->fresh()->tracking_number);
    }

    private function event(Order $order, string $status, int $timestamp, string $tracking): void
    {
        $payload = [
            'action' => 'parcel_status_changed',
            'carrier_status_change_timestamp' => $timestamp,
            'parcel' => [
                'order_number' => $order->order_number,
                'id' => 'sc-parcel-1',
                'tracking_number' => $tracking,
                'status' => ['message' => $status],
            ],
        ];
        $signature = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE), 'sendcloud-test-secret');

        $this->withHeader('Sendcloud-Signature', $signature)
            ->postJson(route('sendcloud.webhook'), $payload)
            ->assertOk();
    }

    private function order(): Order
    {
        return Order::query()->create([
            'first_name' => 'Marie',
            'phone' => '0102030405',
            'country' => 'France',
            'city' => 'Paris',
            'address' => 'Rue de Test 1',
            'items' => [['name' => 'Gel', 'quantity' => 1]],
            'subtotal' => 20,
            'delivery_cost' => 5,
            'total_amount' => 25,
            'payment_status' => 'paid',
            'status' => 'pending',
        ]);
    }
}
