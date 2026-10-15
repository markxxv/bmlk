<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
        config()->set('services.stripe.secret', 'sk_test_fake');
    }

    public function test_checkout_creates_a_passwordless_customer_but_only_original_browser_can_log_in(): void
    {
        $order = $this->order();
        $order->update(['stripe_session_id' => 'cs_test_customer']);
        Http::fake(['api.stripe.com/*' => Http::response($this->paidSession($order))]);

        $this->withSession(["bmlk_auto_login.{$order->id}" => $order->access_token])
            ->get(route('order.thanks', [
                'orderNumber' => $order->order_number,
                'token' => $order->access_token,
                'session_id' => 'cs_test_customer',
            ]))->assertRedirect(route('account.index'));

        $user = User::query()->where('email', 'marie@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->password);
        $this->assertFalse($user->is_admin);
        $this->assertSame($user->id, $order->fresh()->user_id);

        Auth::logout();
        $this->get(route('order.thanks', [
            'orderNumber' => $order->order_number,
            'token' => $order->access_token,
        ]))->assertOk();
        $this->assertGuest();
    }

    public function test_existing_email_never_grants_guest_access(): void
    {
        User::query()->create(['name' => 'Marie', 'email' => 'marie@example.com', 'password' => 'secret-password']);
        $order = $this->order();
        $order->update(['stripe_session_id' => 'cs_test_customer']);
        Http::fake(['api.stripe.com/*' => Http::response($this->paidSession($order))]);

        $this->withSession(["bmlk_auto_login.{$order->id}" => $order->access_token])
            ->get(route('order.thanks', [
                'orderNumber' => $order->order_number,
                'token' => $order->access_token,
                'session_id' => 'cs_test_customer',
            ]))->assertOk();

        $this->assertGuest();
        $this->assertNull($order->fresh()->user_id);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_role_applications_coexist_and_influencer_code_is_immutable(): void
    {
        $user = User::query()->create(['name' => 'Marie', 'email' => 'marie@example.com']);
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user)->post(route('account.roles.apply'), ['role' => 'wholesale'])->assertRedirect();
        $this->actingAs($user)->post(route('account.roles.apply'), ['role' => 'influencer'])->assertRedirect();
        $this->assertCount(2, $user->roleApplications);

        $user->forceFill(['is_wholesale' => true, 'is_influencer' => true])->save();
        $this->get(route('account.wholesale'))->assertOk();
        $this->get(route('account.influencer'))->assertOk();

        $this->post(route('account.influencer.code'), ['code' => 'marie30'])->assertRedirect();
        $this->assertSame('MARIE30', $user->fresh()->influencer_code);

        $this->post(route('account.influencer.code'), ['code' => 'CHANGED'])->assertForbidden();
        $this->get(route('api.promo', ['code' => 'marie30']))->assertOk()->assertJsonPath('percent', 30);
    }

    public function test_password_setup_hashes_password_and_leaves_admin_access_disabled(): void
    {
        $user = User::query()->create(['name' => 'Marie', 'email' => 'marie@example.com']);

        $this->actingAs($user)->post(route('account.password'), [
            'password' => 'MyUniquePassword123!',
            'password_confirmation' => 'MyUniquePassword123!',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('MyUniquePassword123!', $user->fresh()->password));
        $this->assertFalse($user->fresh()->is_admin);
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
            'items' => [['name' => 'Gel', 'unit_price' => 12, 'quantity' => 1, 'line_total' => 12]],
            'subtotal' => 12,
            'delivery_cost' => 3,
            'total_amount' => 15,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);
    }

    private function paidSession(Order $order): array
    {
        return [
            'id' => 'cs_test_customer',
            'mode' => 'payment',
            'status' => 'complete',
            'payment_status' => 'paid',
            'currency' => 'eur',
            'amount_total' => 1500,
            'metadata' => ['order_id' => (string) $order->id, 'order_number' => $order->order_number],
        ];
    }
}
