<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderTelegramNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderTelegramNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.telegram.bot_token', 'test-token');
        config()->set('services.telegram.chat_id', '-1001234567890');
    }

    public function test_new_paid_order_sends_once_and_repeats_each_day_while_pending(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true])]);
        $order = $this->order('paid');
        $notification = OrderTelegramNotification::query()->create(['order_id' => $order->id]);

        $this->artisan('orders:telegram-notifications')->assertSuccessful();
        $this->assertNotNull($notification->fresh()->first_sent_at);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request['text'], 'Nouvelle commande payée'));

        $this->artisan('orders:telegram-notifications')->assertSuccessful();
        $this->travel(23)->hours();
        $this->artisan('orders:telegram-notifications')->assertSuccessful();
        Http::assertSentCount(1);

        $this->travel(2)->hours();
        $this->artisan('orders:telegram-notifications')->assertSuccessful();
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains($request['text'], 'Rappel'));

        $order->update(['status' => 'processing']);
        $this->travel(26)->hours();
        $this->artisan('orders:telegram-notifications')->assertSuccessful();
        Http::assertSentCount(2);
    }

    public function test_unpaid_and_unregistered_historical_orders_are_not_sent(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true])]);
        $unpaid = $this->order('unpaid');
        OrderTelegramNotification::query()->create(['order_id' => $unpaid->id]);
        $this->order('paid');

        $this->artisan('orders:telegram-notifications')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_api_failures_are_retried_without_recording_a_success(): void
    {
        $order = $this->order('paid');
        $notification = OrderTelegramNotification::query()->create(['order_id' => $order->id]);

        Http::fake(['https://api.telegram.org/*' => Http::sequence()
            ->push(['ok' => false, 'description' => 'Retry later'], 429)
            ->push(['ok' => true])]);
        $this->artisan('orders:telegram-notifications')->assertSuccessful();
        $this->assertNull($notification->fresh()->first_sent_at);
        $this->assertNull($notification->fresh()->claimed_at);

        $this->artisan('orders:telegram-notifications')->assertSuccessful();
        $this->assertNotNull($notification->fresh()->first_sent_at);
    }

    private function order(string $paymentStatus): Order
    {
        return Order::query()->create([
            'first_name' => 'Marie',
            'phone' => '0102030405',
            'country' => 'France',
            'city' => 'Paris',
            'address' => 'Rue de Test 1',
            'items' => [['name' => 'Gel', 'quantity' => 2]],
            'subtotal' => 20,
            'delivery_cost' => 5,
            'total_amount' => 25,
            'status' => 'pending',
            'payment_status' => $paymentStatus,
        ]);
    }
}
