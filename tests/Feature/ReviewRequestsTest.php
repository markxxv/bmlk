<?php

namespace Tests\Feature;

use App\Mail\ReviewRequestMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReviewAccessToken;
use App\Models\ReviewEmailOptOut;
use App\Models\User;
use App\Support\ReviewSendWindow;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ReviewRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-11-17 11:15:00', 'Europe/Paris'));
    }

    public function test_sends_once_after_seven_full_days_and_token_allows_guest_rating(): void
    {
        $order = $this->order(deliveredAt: '2026-11-10 10:00:00');

        $this->artisan('orders:send-review-requests')->assertSuccessful();
        $this->artisan('orders:send-review-requests')->assertSuccessful();

        Mail::assertSent(ReviewRequestMail::class, 1);
        $this->assertNotNull($order->fresh()->review_request_sent_at);

        /** @var ReviewRequestMail $mail */
        $mail = Mail::sent(ReviewRequestMail::class)->first();
        $token = basename(parse_url($mail->reviewUrl, PHP_URL_PATH));

        $this->assertSame(hash('sha256', $token), ReviewAccessToken::query()->where('order_id', $order->id)->value('token_hash'));
        $this->assertFalse(auth()->check());

        $this->get($mail->reviewUrl)
            ->assertOk()
            ->assertSee('Évaluez vos produits')
            ->assertSee('Gel de soin')
            ->assertDontSee($order->address);

        $this->post(route('reviews.store', ['token' => $token]), [
            'ratings' => [0 => 5],
        ])->assertRedirect(route('reviews.show', ['token' => $token]));

        $this->assertDatabaseHas('product_ratings', [
            'order_id' => $order->id, 'order_item_index' => 0, 'rating' => 5,
        ]);
        $this->assertGuest();
        $this->get($mail->reviewUrl)->assertOk()->assertSee('Tous les produits ont été évalués');

        $this->artisan('orders:send-review-requests')->assertSuccessful();
        Mail::assertSent(ReviewRequestMail::class, 1);
    }

    public function test_never_sends_before_seven_complete_days(): void
    {
        $order = $this->order(deliveredAt: '2026-11-10 11:30:00');

        $this->artisan('orders:send-review-requests')->assertSuccessful();
        Mail::assertNothingSent();
        $this->assertNull($order->fresh()->review_request_sent_at);

        $this->travelTo(Carbon::parse('2026-11-17 12:00:00', 'Europe/Paris'));
        $this->artisan('orders:send-review-requests')->assertSuccessful();
        Mail::assertSent(ReviewRequestMail::class, 1);
    }

    public function test_historic_imports_and_undelivered_orders_are_excluded(): void
    {
        $this->order(deliveredAt: '2026-11-01 10:00:00', overrides: [
            'ratings_enabled' => false,
        ]);
        $this->order(deliveredAt: null, overrides: [
            'status' => 'shipped',
            'sendcloud_tracking_phase' => null,
        ]);
        $this->order(deliveredAt: '2026-11-01 10:00:00', overrides: [
            'payment_status' => 'failed',
        ]);
        $this->order(deliveredAt: '2026-11-01 10:00:00', overrides: [
            'status' => 'cancelled',
        ]);

        $this->artisan('orders:send-review-requests')->assertSuccessful();
        Mail::assertNothingSent();
    }

    public function test_completed_ratings_are_excluded_from_email(): void
    {
        $order = $this->order(deliveredAt: '2026-11-01 10:00:00');
        $order->ratings()->create([
            'order_item_index' => 0,
            'product_id' => (int) $order->items[0]['product_id'],
            'product_name' => 'Gel de soin',
            'rating' => 4,
        ]);

        $this->artisan('orders:send-review-requests')->assertSuccessful();
        Mail::assertNothingSent();
    }

    public function test_public_link_cannot_rate_another_orders_items_and_expires(): void
    {
        $order = $this->order(deliveredAt: '2026-11-01 10:00:00');
        $this->artisan('orders:send-review-requests')->assertSuccessful();

        $mail = Mail::sent(ReviewRequestMail::class)->first();
        $token = basename(parse_url($mail->reviewUrl, PHP_URL_PATH));

        $this->post(route('reviews.store', ['token' => $token]), [
            'ratings' => [20 => 5],
        ])->assertSessionHasErrors('ratings');

        $this->get(route('reviews.show', ['token' => str_repeat('a', 64)]))->assertNotFound();

        ReviewAccessToken::query()->where('order_id', $order->id)
            ->update(['expires_at' => now()->subMinute()]);

        $this->get($mail->reviewUrl)->assertStatus(410);
        $this->post(route('reviews.store', ['token' => $token]), [
            'ratings' => [0 => 5],
        ])->assertStatus(410);
        $this->assertDatabaseCount('product_ratings', 0);
    }

    public function test_unsubscribe_requires_signed_link_and_explicit_post_and_excludes_future_orders(): void
    {
        $order = $this->order(deliveredAt: '2026-11-01 10:00:00');
        $this->artisan('orders:send-review-requests')->assertSuccessful();
        $mail = Mail::sent(ReviewRequestMail::class)->first();

        $this->get($mail->unsubscribeUrl)->assertOk();
        $this->assertDatabaseCount('review_email_opt_outs', 0);

        $this->get(route('reviews.unsubscribe', ['order' => $order->id]))->assertForbidden();

        $this->post($mail->unsubscribeUrl)->assertRedirect();
        $this->assertTrue(ReviewEmailOptOut::contains($order->email));

        $second = $this->order(deliveredAt: '2026-11-01 10:00:00', overrides: [
            'email' => $order->email,
        ]);
        $this->artisan('orders:send-review-requests')->assertSuccessful();
        $this->assertNull($second->fresh()->review_request_sent_at);
        Mail::assertSent(ReviewRequestMail::class, 1);
    }

    public function test_public_holidays_weekends_and_late_hours_are_excluded(): void
    {
        $this->order(deliveredAt: '2026-11-01 10:00:00');

        $this->travelTo(Carbon::parse('2026-11-11 11:00:00', 'Europe/Paris'));
        $this->assertFalse(ReviewSendWindow::isOpen(now()));
        $this->artisan('orders:send-review-requests')->assertSuccessful();

        $this->travelTo(Carbon::parse('2026-11-14 11:00:00', 'Europe/Paris'));
        $this->assertFalse(ReviewSendWindow::isOpen(now()));
        $this->artisan('orders:send-review-requests')->assertSuccessful();

        $this->travelTo(Carbon::parse('2026-11-16 19:15:00', 'Europe/Paris'));
        $this->assertFalse(ReviewSendWindow::isOpen(now()));
        $this->artisan('orders:send-review-requests')->assertSuccessful();

        Mail::assertNothingSent();
        $this->travelTo(Carbon::parse('2026-11-17 11:15:00', 'Europe/Paris'));
        $this->artisan('orders:send-review-requests')->assertSuccessful();
        Mail::assertSent(ReviewRequestMail::class, 1);
    }

    private function order(?string $deliveredAt, array $overrides = []): Order
    {
        $category = Category::query()->first() ?? Category::query()->create([
            'name' => ['fr' => 'Soins'], 'slug' => ['fr' => 'soins'],
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => ['fr' => 'Gel de soin'],
            'slug' => ['fr' => 'soin-'.bin2hex(random_bytes(5))],
            'active' => true,
            'price' => 10,
        ]);

        return Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'first_name' => 'Marie',
            'email' => 'marie@example.com',
            'country' => 'France',
            'city' => 'Paris',
            'address' => 'Rue privée 1',
            'items' => [[
                'product_id' => $product->id,
                'name' => 'Gel de soin',
                'quantity' => 1,
                'line_total' => 10,
            ]],
            'subtotal' => 10,
            'delivery_cost' => 0,
            'total_amount' => 10,
            'status' => 'completed',
            'sendcloud_tracking_phase' => 'delivered',
            'payment_status' => 'paid',
            'ratings_enabled' => true,
            'delivered_at' => $deliveredAt ? Carbon::parse($deliveredAt, 'Europe/Paris')->utc() : null,
            ...$overrides,
        ]);
    }
}
