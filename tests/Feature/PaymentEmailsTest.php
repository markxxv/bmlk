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

    public function test_paid_email_contains_items_options_amounts_and_private_order_link(): void
    {
        $order = $this->order();
        $order->update([
            'payment_status' => 'paid',
            'items' => [[
                'name' => 'Gel BLACK MILK',
                'quantity' => 2,
                'unit_price' => 6,
                'line_total' => 12,
                'options' => [['label' => 'Taille', 'display' => '100 ml']],
            ]],
        ]);

        $html = view('emails.payment-status', [
            'order' => $order,
            'kind' => 'paid',
        ])->render();

        $this->assertStringContainsString('Gel BLACK MILK', $html);
        $this->assertStringContainsString('100 ml', $html);
        $this->assertStringContainsString('2 ×', $html);
        $this->assertStringContainsString(__('Livraison'), $html);
        $this->assertStringContainsString(__('Voir ma commande'), $html);
        $this->assertStringContainsString(route('account.checkout', [
            'orderNumber' => $order->order_number,
            'token' => $order->access_token,
        ]), $html);
    }

    public function test_failed_mail_and_reminder_link_to_existing_order_and_retry(): void
    {
        $order = $this->order();
        $order->update(['payment_status' => 'failed']);

        foreach (['failed', 'reminder'] as $kind) {
            $html = view('emails.payment-status', compact('order', 'kind'))->render();

            $this->assertStringContainsString('Gel', $html);
            $this->assertStringContainsString(__('Reprendre le paiement'), $html);
            $this->assertStringContainsString(route('account.checkout', [
                'orderNumber' => $order->order_number,
                'token' => $order->access_token,
            ]), $html);
            $this->assertStringContainsString(route('order.status', [
                'orderNumber' => $order->order_number,
                'token' => $order->access_token,
            ]), $html);
        }
    }

    public function test_guest_can_view_paid_and_failed_orders_but_not_without_private_token(): void
    {
        $order = $this->order();
        $order->update(['payment_status' => 'paid']);

        $url = route('account.checkout', [
            'orderNumber' => $order->order_number,
            'token' => $order->access_token,
        ]);

        $this->get($url)->assertOk()
            ->assertSee('Gel')
            ->assertSee(__('Sous-total'))
            ->assertSee(__('Livraison'));
        $this->assertGuest();

        $this->get(route('account.checkout', [
            'orderNumber' => $order->order_number,
            'token' => 'invalid',
        ]))->assertNotFound();

        $this->get(route('order.status', [
            'orderNumber' => $order->order_number,
            'token' => $order->access_token,
        ]))->assertRedirect($url);

        $order->update(['payment_status' => 'failed']);
        $this->get($url)->assertOk()
            ->assertSee(__('Reprendre le paiement'))
            ->assertSee('Gel');
        $this->assertGuest();
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
