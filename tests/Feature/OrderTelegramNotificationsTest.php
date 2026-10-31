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


    public function test_immediate_send_includes_products_shipping_address_and_manager_link(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true])]);
        $order = $this->order('paid');
        $order->update([
            'access_token' => 'secret-private-token-should-not-leak',
            'last_name' => 'Martin',
            'email' => 'marie@example.com',
            'address' => '10 Rue des Fleurs',
            'zip' => '75003',
            'shipping_option_name' => 'Colissimo — domicile',
            'service_point_id' => 42,
            'service_point_name' => 'Point relais central',
            'service_point_address' => '12 Rue de Paris',
            'message' => 'Livrer après 15h',
            'promo_code' => 'WELCOME',
            'discount_amount' => 3,
            'items' => [
                [
                    'name' => 'Gel <Rose> & Milk',
                    'quantity' => 2,
                    'unit_price' => 12.00,
                    'line_total' => 24.00,
                    'options' => [['label' => 'Couleur', 'display' => 'Rouge & noir']],
                ],
                [
                    'name' => 'Lime à ongles',
                    'quantity' => 1,
                    'unit_price' => 6.00,
                    'line_total' => 6.00,
                ],
            ],
            'subtotal' => 30,
            'delivery_cost' => 5,
            'total_amount' => 32,
        ]);

        $notification = OrderTelegramNotification::query()->create(['order_id' => $order->id]);

        $this->artisan('orders:telegram-notifications', ['--order' => $order->id])->assertSuccessful();

        Http::assertSent(function ($request) use ($order): bool {
            $text = $request['text'];

            return str_contains($text, 'Gel &lt;Rose&gt; &amp; Milk')
                && str_contains($text, 'Rouge &amp; noir')
                && str_contains($text, 'Lime à ongles')
                && str_contains($text, '2 ×')
                && str_contains($text, '10 Rue des Fleurs')
                && str_contains($text, '75003 Paris')
                && str_contains($text, 'Colissimo')
                && str_contains($text, 'Point relais central')
                && str_contains($text, 'Livrer après 15h')
                && str_contains($text, 'WELCOME')
                && str_contains($text, 'Total payé')
                && str_contains($text, 'Voir la commande sur le site')
                && str_contains($text, '/admin/orders/'.$order->id.'/edit')
                && ! str_contains($text, 'Ouvrir dans Filament')
                && ! str_contains($text, (string) $order->access_token);
        });

        $this->assertNotNull($notification->fresh()->first_sent_at);
        $this->artisan('orders:telegram-notifications', ['--order' => $order->id])->assertSuccessful();
        Http::assertSentCount(1);
    }

    public function test_daily_message_explicitly_says_forgotten_order(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true])]);
        $order = $this->order('paid');

        OrderTelegramNotification::query()->create([
            'order_id' => $order->id,
            'first_sent_at' => now()->subDays(2),
            'last_sent_at' => now()->subHours(25),
        ]);

        $this->artisan('orders:telegram-notifications')->assertSuccessful();

        Http::assertSent(fn ($request): bool =>
            str_contains($request['text'], 'RAPPEL — Commande oubliée')
            && str_contains($request['text'], 'sa préparation n’a toujours pas commencé')
        );
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
