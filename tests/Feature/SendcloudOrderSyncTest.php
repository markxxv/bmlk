<?php

namespace Tests\Feature;

use App\Http\Controllers\SendcloudController;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendcloudOrderSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_order_sync_includes_customer_contact_details(): void
    {
        config()->set('services.sendcloud.public_key', 'test-public-key');
        config()->set('services.sendcloud.secret_key', 'test-secret-key');
        Cache::put('sendcloud.integration_id', 55);

        Http::fake([
            'https://panel.sendcloud.sc/api/v3/orders' => Http::response([
                'data' => [['id' => 12345]],
            ], 201),
        ]);

        $order = Order::query()->create([
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'phone' => '+33612345678',
            'email' => 'marie@example.com',
            'country' => 'France',
            'shipping_country_code' => 'FR',
            'shipping_option_name' => 'Colissimo — Domicile',
            'zip' => '75001',
            'city' => 'Paris',
            'address' => '12 Rue de Test',
            'items' => [['name' => 'Gel', 'quantity' => 1, 'line_total' => 20]],
            'shipping_parcel' => ['weight' => ['value' => 0.5, 'unit' => 'kg']],
            'subtotal' => 20,
            'delivery_cost' => 5,
            'total_amount' => 25,
            'payment_status' => 'paid',
            'status' => 'pending',
        ]);

        app(SendcloudController::class)->syncPaidOrder($order);

        Http::assertSent(fn (Request $request): bool =>
            $request->method() === 'POST'
                && $request->url() === 'https://panel.sendcloud.sc/api/v3/orders'
                && data_get($request->data(), '0.shipping_address.name') === 'Marie Dupont'
                && data_get($request->data(), '0.shipping_address.email') === 'marie@example.com'
                && data_get($request->data(), '0.shipping_address.phone_number') === '+33612345678'
        );
        $this->assertSame('12345', $order->fresh()->sendcloud_order_id);
    }
}
