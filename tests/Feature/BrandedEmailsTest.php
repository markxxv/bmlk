<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BrandedEmailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_email_uses_the_black_milk_design_in_french(): void
    {
        $user = User::factory()->create([
            'name' => 'Marie',
            'email' => 'marie@example.com',
            'email_verified_at' => null,
        ]);

        $notification = (new VerifyEmail)->toMail($user);
        $html = view($notification->view, $notification->viewData)->render();

        $this->assertSame('emails.account-action', $notification->view);
        $this->assertSame('BLACK MILK · Confirmez votre adresse e-mail', $notification->subject);
        $this->assertStringContainsString('Bonjour Marie', $html);
        $this->assertStringContainsString('Confirmer mon adresse e-mail', $html);
        $this->assertStringContainsString('/account/verify-email/'.$user->id.'/', $html);
        $this->assertStringContainsString(config('email.colors.accent'), $html);
        $this->assertStringContainsString(config('email.colors.canvas'), $html);
        $this->assertStringNotContainsString('Verify Email Address', $html);
        $this->assertStringNotContainsString('Hello!', $html);
    }

    public function test_password_reset_email_uses_the_same_design_and_existing_secure_route(): void
    {
        $user = User::factory()->create([
            'name' => 'Marie',
            'email' => 'marie@example.com',
        ]);

        $notification = (new ResetPassword('reset-token-123'))->toMail($user);
        $html = view($notification->view, $notification->viewData)->render();

        $this->assertSame('emails.account-action', $notification->view);
        $this->assertSame('BLACK MILK · Créez votre mot de passe', $notification->subject);
        $this->assertStringContainsString('Bonjour Marie', $html);
        $this->assertStringContainsString('Créer mon mot de passe', $html);
        $this->assertStringContainsString('/account/reset-password/reset-token-123', $html);
        $this->assertStringContainsString(config('email.colors.accent'), $html);
        $this->assertStringNotContainsString('Reset Password', $html);
        $this->assertStringNotContainsString('Hello!', $html);
    }

    public function test_verification_notification_reaches_the_real_mail_renderer(): void
    {
        // A direct view()->render() misses Laravel Mailer's reserved $message
        // variable. Exercise the actual notification mail channel instead.
        config()->set('mail.default', 'array');

        $user = User::factory()->create([
            'name' => 'Marie',
            'email' => 'marie@example.com',
            'email_verified_at' => null,
        ]);

        $user->sendEmailVerificationNotification();

        $this->assertTrue(true);
    }

    public function test_password_reset_notification_reaches_the_real_mail_renderer(): void
    {
        config()->set('mail.default', 'array');

        $user = User::factory()->create([
            'name' => 'Marie',
            'email' => 'marie@example.com',
        ]);

        $user->sendPasswordResetNotification('test-reset-token');

        $this->assertTrue(true);
    }

    public function test_forgot_password_form_sends_branded_email_without_internal_error(): void
    {
        config()->set('mail.default', 'array');

        User::factory()->create([
            'name' => 'Marie',
            'email' => 'marie@example.com',
        ]);

        $this->post(route('password.email'), ['email' => 'marie@example.com'])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_order_email_reuses_the_same_brand_layout(): void
    {
        $order = Order::query()->create([
            'access_token' => Str::random(48),
            'first_name' => 'Marie',
            'phone' => '0102030405',
            'email' => 'marie@example.com',
            'country' => 'France',
            'city' => 'Paris',
            'address' => 'Rue de Test 1',
            'items' => [[
                'name' => 'Crème BLACK MILK',
                'unit_price' => 24,
                'quantity' => 1,
                'line_total' => 24,
            ]],
            'subtotal' => 24,
            'delivery_cost' => 0,
            'total_amount' => 24,
            'status' => 'pending',
            'payment_status' => 'paid',
        ]);

        $html = view('emails.payment-status', [
            'order' => $order,
            'kind' => 'paid',
        ])->render();

        $this->assertStringContainsString('Crème BLACK MILK', $html);
        $this->assertStringContainsString('Voir ma commande', $html);
        $this->assertStringContainsString(config('email.colors.accent'), $html);
        $this->assertStringContainsString(config('email.colors.canvas'), $html);
        $this->assertStringContainsString('BLACK MILK · Merci pour votre confiance.', $html);
    }
}
