<?php

namespace Tests\Feature;

use App\Mail\PaymentStatusMail;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentEmailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Mail::fake();
    }

    public function test_confirmation_is_sent_only_once_for_paid_orders(): void
    {
        $order = $this->order();
        $order->update(['payment_status' => 'paid', 'paid_at' => now()]);

        $this->artisan('orders:payment-emails')->assertSuccessful();
        $this->artisan('orders:payment-emails')->assertSuccessful();

        Mail::assertSent(PaymentStatusMail::class, 1);
        Mail::assertSent(PaymentStatusMail::class, fn ($mail): bool =>
            $mail->kind === 'paid' && $mail->order->id === $order->id
        );
        $this->assertNotNull($order->fresh()->paid_email_sent_at);
    }

    public function test_failed_email_is_sent_now_and_reminder_once_next_day_after_ten_in_paris(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 16:20:00', 'Europe/Paris'));

        $order = $this->order();
        $order->update(['payment_status' => 'failed', 'payment_failed_at' => now()]);

        $this->artisan('orders:payment-emails')->assertSuccessful();

        Mail::assertSent(PaymentStatusMail::class, 1);
        Mail::assertSent(PaymentStatusMail::class, fn ($mail): bool => $mail->kind === 'failed');

        $this->travelTo(Carbon::parse('2026-10-16 09:59:00', 'Europe/Paris'));
        $this->artisan('orders:payment-emails')->assertSuccessful();
        Mail::assertSent(PaymentStatusMail::class, 1);

        $this->travelTo(Carbon::parse('2026-10-16 10:01:00', 'Europe/Paris'));
        $this->artisan('orders:payment-emails')->assertSuccessful();
        $this->artisan('orders:payment-emails')->assertSuccessful();

        Mail::assertSent(PaymentStatusMail::class, 2);
        Mail::assertSent(PaymentStatusMail::class, fn ($mail): bool => $mail->kind === 'reminder');
        $this->assertNotNull($order->fresh()->failed_email_sent_at);
        $this->assertNotNull($order->fresh()->failed_reminder_sent_at);
    }

    public function test_success_prevents_reminders_for_previously_failed_payment(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 17:00:00', 'Europe/Paris'));

        $order = $this->order();
        $order->update(['payment_status' => 'failed', 'payment_failed_at' => now()]);
        $this->artisan('orders:payment-emails')->assertSuccessful();

        $order->update(['payment_status' => 'paid', 'paid_at' => now()]);
        $this->travelTo(Carbon::parse('2026-10-16 11:00:00', 'Europe/Paris'));
        $this->artisan('orders:payment-emails')->assertSuccessful();

        Mail::assertSent(PaymentStatusMail::class, 2);
        Mail::assertNotSent(PaymentStatusMail::class, fn ($mail): bool => $mail->kind === 'reminder');
        $this->assertNull($order->fresh()->failed_reminder_sent_at);
    }

    public function test_failed_payment_without_customer_email_sends_no_mail(): void
    {
        $order = $this->order();
        $order->update([
            'email' => null,
            'payment_status' => 'failed',
            'payment_failed_at' => now(),
        ]);

        $this->artisan('orders:payment-emails')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertNull($order->fresh()->failed_email_sent_at);
    }

    public function test_unpaid_order_with_previous_failure_gets_its_single_reminder(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 13:00:00', 'Europe/Paris'));

        $order = $this->order();
        $order->update(['payment_status' => 'failed', 'payment_failed_at' => now()]);
        $this->artisan('orders:payment-emails')->assertSuccessful();

        $order->update(['payment_status' => 'unpaid']);
        $this->travelTo(Carbon::parse('2026-10-16 10:05:00', 'Europe/Paris'));
        $this->artisan('orders:payment-emails')->assertSuccessful();

        Mail::assertSent(PaymentStatusMail::class, 2);
        Mail::assertSent(PaymentStatusMail::class, fn ($mail): bool => $mail->kind === 'reminder');
    }

    private function order(): Order
    {
        return Order::query()->create([
            'access_token' => Str::random(48),
            'first_name' => 'Marie',
            'phone' => '0102030405',
            'email' => 'marie@example.com',
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
}
