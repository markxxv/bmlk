<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
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
            ]))->assertOk()
                ->assertSee(__('Merci pour votre commande'))
                ->assertSee(__('Accéder à mon espace'))
                ->assertSee('}, 5000);', false)
                ->assertSee(route('account.index'), false);

        $user = User::query()->where('email', 'marie@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->password);
        $this->assertFalse($user->is_admin);
        $this->assertSame($user->id, $order->fresh()->user_id);
        $this->assertFalse($user->hasVerifiedEmail());

        $this->get(route('account.index'))
            ->assertOk()
            ->assertSee(__('Protégez votre compte'))
            ->assertSee($order->order_number)
            ->assertSee(__('Générer un mot de passe'));

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
            ]))->assertOk()
                ->assertSee(__('Accéder à mon espace'))
                ->assertSee(route('account.checkout', [
                    'orderNumber' => $order->order_number,
                    'token' => $order->access_token,
                ]), false);

        $this->assertGuest();
        $this->assertNull($order->fresh()->user_id);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_original_checkout_browser_completes_passwordless_sign_in_even_without_its_old_session(): void
    {
        $order = $this->order();
        $order->update([
            'stripe_session_id' => 'cs_test_customer',
            'onboarding_token_hash' => hash('sha256', str_repeat('a', 64)),
        ]);

        Http::fake(['api.stripe.com/*' => Http::response($this->paidSession($order))]);

        $this->get(route('order.thanks', [
            'orderNumber' => $order->order_number,
            'token' => $order->access_token,
            'session_id' => 'cs_test_customer',
        ]))->assertOk()
            ->assertSee(__('Accéder à mon espace'))
            ->assertDontSee(__('Se connecter à mon espace'));

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'marie@example.com',
            'onboarding_order_id' => $order->id,
        ]);

        // URL access tokens alone must never log a visitor into the account.
        $this->postJson(route('account.complete_checkout'), [
            'order_number' => $order->order_number,
            'token' => str_repeat('x', 64),
        ])->assertForbidden();

        $this->get(route('account.checkout', [
            'orderNumber' => $order->order_number,
            'token' => $order->access_token,
        ]))->assertOk();
        $this->assertGuest();

        $this->postJson(route('account.complete_checkout'), [
            'order_number' => $order->order_number,
            'token' => str_repeat('a', 64),
        ])->assertOk()->assertJsonPath('authenticated', true)
            ->assertJsonPath('url', route('account.index'));

        $user = User::query()->where('email', 'marie@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNull($order->fresh()->onboarding_token_hash);

        $this->get(route('account.index'))
            ->assertOk()
            ->assertSee(__('Protégez votre compte'))
            ->assertSee($order->order_number);
    }

    public function test_repeated_guest_checkout_on_existing_email_shows_current_order_not_login(): void
    {
        $original = User::query()->create([
            'name' => 'Marie',
            'email' => 'marie@example.com',
            'password' => 'already-registered',
        ]);
        $order = $this->order();
        $order->update([
            'stripe_session_id' => 'cs_test_customer',
            'onboarding_token_hash' => hash('sha256', str_repeat('b', 64)),
        ]);

        Http::fake(['api.stripe.com/*' => Http::response($this->paidSession($order))]);

        $fallback = route('account.checkout', [
            'orderNumber' => $order->order_number,
            'token' => $order->access_token,
        ]);

        $this->get(route('order.thanks', [
            'orderNumber' => $order->order_number,
            'token' => $order->access_token,
            'session_id' => 'cs_test_customer',
        ]))->assertOk()->assertSee($fallback, false)
            ->assertDontSee(__('Se connecter à mon espace'));

        $this->postJson(route('account.complete_checkout'), [
            'order_number' => $order->order_number,
            'token' => str_repeat('b', 64),
        ])->assertOk()->assertJsonPath('authenticated', false)
            ->assertJsonPath('url', $fallback);

        $this->assertGuest();
        $this->assertNull($order->fresh()->user_id);
        $this->assertSame($original->id, User::query()->where('email', 'marie@example.com')->value('id'));

        $this->get($fallback)->assertOk()
            ->assertSee($order->order_number)
            ->assertSee(__('Créer mon mot de passe'));
    }

    public function test_signed_verification_email_works_without_an_existing_login_session_and_claims_every_order(): void
    {
        $user = User::query()->create([
            'name' => 'Marie',
            'email' => 'marie@example.com',
            'password' => 'ExistingSecurePassword123!',
        ]);

        $paid = $this->order();
        $paid->update(['payment_status' => 'paid']);
        $unpaid = $this->order();
        $unpaid->update(['payment_status' => 'unpaid']);
        $failed = $this->order();
        $failed->update(['payment_status' => 'failed']);

        $signedUrl = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($signedUrl)->assertRedirect(route('account.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->get(route('account.index'))
            ->assertOk()
            ->assertSee($paid->order_number)
            ->assertSee($unpaid->order_number)
            ->assertSee($failed->order_number)
            ->assertDontSee(__('Renvoyer le lien'));

        $this->assertSame(3, $user->orders()->count());
        foreach ([$paid, $unpaid, $failed] as $order) {
            $this->assertSame($user->id, $order->fresh()->user_id);
        }
    }

    public function test_first_password_setup_keeps_the_user_logged_in_even_if_referrer_was_login(): void
    {
        $user = User::query()->create([
            'name' => 'Marie',
            'email' => 'marie@example.com',
        ]);

        $this->actingAs($user)->from(route('account.login'))
            ->post(route('account.password'), [
                'password' => 'ANewVerySecurePassword123!',
                'password_confirmation' => 'ANewVerySecurePassword123!',
            ])->assertRedirect(route('account.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('ANewVerySecurePassword123!', $user->fresh()->password));
    }

    public function test_email_password_recovery_logs_customer_in_and_verifies_email_and_links_existing_orders(): void
    {
        $user = User::query()->create([
            'name' => 'Marie',
            'email' => 'marie@example.com',
            'password' => 'old-password',
        ]);

        $older = $this->order();
        $older->update(['payment_status' => 'paid']);

        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'marie@example.com',
            'password' => 'NewUniqueSecurePassword123!',
            'password_confirmation' => 'NewUniqueSecurePassword123!',
        ])->assertRedirect(route('account.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertTrue(Hash::check('NewUniqueSecurePassword123!', $user->fresh()->password));

        $this->get(route('account.index'))
            ->assertOk()
            ->assertSee($older->order_number);
        $this->assertSame($user->id, $older->fresh()->user_id);
    }

    public function test_unverified_customer_cannot_see_older_unassigned_orders_until_email_proof(): void
    {
        $user = User::query()->create(['name' => 'Marie', 'email' => 'marie@example.com']);

        $current = $this->order();
        $current->update(['user_id' => $user->id]);
        $older = $this->order();
        $older->update(['payment_status' => 'paid']);

        $this->actingAs($user)->get(route('account.index'))
            ->assertOk()
            ->assertSee($current->order_number)
            ->assertDontSee($older->order_number);

        $signedUrl = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($signedUrl)->assertRedirect(route('account.index'));
        $this->get(route('account.index'))
            ->assertOk()
            ->assertSee($current->order_number)
            ->assertSee($older->order_number);
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

        $this->get(route('account.index'))
            ->assertDontSee(__('Protégez votre compte'));
    }

    public function test_influencer_discount_is_recalculated_from_catalog_and_reflected_in_stripe_order(): void
    {
        $user = User::query()->create(['name' => 'Influenceuse', 'email' => 'influenceuse@example.com']);
        $user->forceFill([
            'is_influencer' => true,
            'email_verified_at' => now(),
            'influencer_code' => 'BMLOVER',
            'influencer_discount' => 30,
        ])->save();

        $category = Category::query()->create(['name' => ['fr' => 'Gels']]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => ['fr' => 'Gel'],
            'slug' => ['fr' => 'gel'],
            'price' => 12,
            'options' => [],
            'active' => true,
        ]);
        Delivery::query()->create([
            'code' => 'fr',
            'name' => 'France',
            'price' => 3,
            'active' => true,
        ]);

        Http::fake(['api.stripe.com/*' => Http::response([
            'id' => 'cs_test_coupon',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_coupon',
        ])]);

        $this->postJson(route('checkout.store'), [
            'first_name' => 'Lou',
            'phone' => '0102030405',
            'email' => 'lou@example.com',
            'country' => 'fr',
            'city' => 'Paris',
            'address' => 'Rue Test',
            'privacy' => 1,
            'promo_code' => 'bmlover',
            'cart_items' => json_encode([[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
                'options' => [],
            ]]),
        ])->assertOk()->assertJsonPath('redirect', 'https://checkout.stripe.com/c/pay/cs_test_coupon');

        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertSame(12.0, (float) $order->subtotal);
        $this->assertSame(3.6, (float) $order->discount_amount);
        $this->assertSame(11.4, (float) $order->total_amount);
        $this->assertSame('BMLOVER', $order->promo_code);
        $this->assertEquals(8.4, $order->items[0]['discounted_unit_price']);
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
