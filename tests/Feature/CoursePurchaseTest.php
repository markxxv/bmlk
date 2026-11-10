<?php

namespace Tests\Feature;

use App\Mail\CoursePurchasedMail;
use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CoursePurchaseTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config()->set('services.stripe.secret', 'sk_test_fake');
        config()->set('services.stripe.webhook_secret', 'whsec_test');
        $this->seed(CourseSeeder::class);
        $this->course = Course::where('slug', 'wax-manicure')->firstOrFail();
        $this->course->update([
            'telegram_channel_url' => 'https://t.me/wax-materials',
            'telegram_chat_url' => 'https://t.me/wax-chat',
        ]);
        Mail::fake();
    }

    private function fakeStripe(string $id = 'cs_test_course'): void
    {
        Http::fake(['api.stripe.com/v1/checkout/sessions' => Http::response([
            'id' => $id,
            'url' => 'https://checkout.stripe.com/c/pay/'.$id,
        ])]);
    }

    private function webhook(CoursePurchase $purchase, string $email = 'new@example.com', string $type = 'checkout.session.completed'): void
    {
        $event = ['type' => $type, 'data' => ['object' => [
            'id' => $purchase->stripe_session_id,
            'mode' => 'payment',
            'currency' => 'eur',
            'amount_total' => $purchase->amount_cents,
            'payment_status' => 'paid',
            'payment_intent' => 'pi_test',
            'metadata' => ['course_purchase_id' => (string) $purchase->id],
            'customer_details' => ['email' => $email, 'name' => 'New Customer'],
        ]]];
        $body = json_encode($event);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_test');

        $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertOk();
    }

    public function test_guest_buys_in_one_click_and_is_redirected_to_stripe(): void
    {
        $this->fakeStripe();

        $this->post(route('courses.buy', ['slug' => 'wax-manicure']))
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_course');

        $this->assertDatabaseHas('course_purchases', [
            'user_id' => null,
            'course_id' => $this->course->id,
            'status' => 'pending',
            'amount_cents' => 15000,
            'stripe_session_id' => 'cs_test_course',
        ]);

        Http::assertSent(fn ($request) => $request['line_items'][0]['price_data']['unit_amount'] === 15000
            && ! isset($request['customer_email']));
    }

    public function test_logged_in_customer_prefills_stripe_email_and_owner_is_not_charged_twice(): void
    {
        $user = User::factory()->create();
        $this->fakeStripe();

        $this->actingAs($user)->post(route('courses.buy', ['slug' => 'wax-manicure']))
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_course');
        Http::assertSent(fn ($request) => ($request['customer_email'] ?? null) === $user->email);

        CoursePurchase::query()->update(['status' => 'paid']);
        Http::fake();
        $this->actingAs($user)->post(route('courses.buy', ['slug' => 'wax-manicure']))
            ->assertRedirect(route('account.courses'));
        Http::assertNothingSent();
    }

    public function test_webhook_marks_a_guest_purchase_paid_creates_the_account_and_is_idempotent(): void
    {
        $this->fakeStripe();
        $this->post(route('courses.buy', ['slug' => 'wax-manicure']));
        $purchase = CoursePurchase::firstOrFail();

        $this->webhook($purchase);

        $purchase->refresh();
        $this->assertSame('paid', $purchase->status);
        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertNull($user->password);
        $this->assertSame($user->id, $purchase->user_id);
        $this->assertTrue($purchase->account_created);
        Mail::assertSent(CoursePurchasedMail::class, 1);

        $this->webhook($purchase);
        Mail::assertSent(CoursePurchasedMail::class, 1);
        $this->assertSame(1, CoursePurchase::count());
    }

    public function test_webhook_with_wrong_amount_does_not_grant_access(): void
    {
        $this->fakeStripe();
        $this->post(route('courses.buy', ['slug' => 'wax-manicure']));
        $purchase = CoursePurchase::firstOrFail();
        $purchase->update(['amount_cents' => 100]); // stored amount differs from the Stripe session total

        $event = ['type' => 'checkout.session.completed', 'data' => ['object' => [
            'id' => 'cs_test_course', 'mode' => 'payment', 'currency' => 'eur', 'amount_total' => 15000,
            'payment_status' => 'paid', 'metadata' => ['course_purchase_id' => (string) $purchase->id],
            'customer_details' => ['email' => 'x@example.com'],
        ]]];
        $body = json_encode($event);
        $timestamp = time();
        $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1=".hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_test'),
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertOk();

        $this->assertSame('pending', $purchase->fresh()->status);
    }

    public function test_guest_returning_from_stripe_in_the_same_browser_is_signed_in_to_the_new_account(): void
    {
        $this->fakeStripe();
        $this->post(route('courses.buy', ['slug' => 'wax-manicure']));
        $purchase = CoursePurchase::firstOrFail();

        Http::fake(['api.stripe.com/v1/checkout/sessions/*' => Http::response([
            'id' => 'cs_test_course', 'mode' => 'payment', 'currency' => 'eur', 'amount_total' => 15000,
            'payment_status' => 'paid', 'payment_intent' => 'pi_test',
            'metadata' => ['course_purchase_id' => (string) $purchase->id],
            'customer_details' => ['email' => 'new@example.com', 'name' => 'New Customer'],
        ])]);

        $this->get(route('courses.thanks', ['purchase' => $purchase->id, 'token' => $purchase->access_token, 'session_id' => 'cs_test_course']))
            ->assertRedirect(route('account.courses'));

        $this->assertAuthenticatedAs(User::where('email', 'new@example.com')->firstOrFail());
    }

    public function test_another_browser_with_the_thanks_link_is_not_signed_in(): void
    {
        $this->fakeStripe();
        $this->post(route('courses.buy', ['slug' => 'wax-manicure']));
        $purchase = CoursePurchase::firstOrFail();
        $this->webhook($purchase);
        $this->flushSession();

        $this->get(route('courses.thanks', ['purchase' => $purchase->id, 'token' => $purchase->access_token]))
            ->assertOk()
            ->assertSee(route('password.request'), false);
        $this->assertGuest();

        $this->get(route('courses.thanks', ['purchase' => $purchase->id, 'token' => 'wrong']))->assertNotFound();
    }

    public function test_existing_account_is_not_signed_in_by_an_anonymous_purchase(): void
    {
        $existing = User::factory()->create(['email' => 'owner@example.com']);
        $this->fakeStripe();
        $this->post(route('courses.buy', ['slug' => 'wax-manicure']));
        $purchase = CoursePurchase::firstOrFail();
        $this->webhook($purchase, 'owner@example.com');

        $this->assertSame($existing->id, $purchase->fresh()->user_id);
        $this->assertFalse($purchase->fresh()->account_created);

        $this->get(route('courses.thanks', ['purchase' => $purchase->id, 'token' => $purchase->access_token]))->assertOk();
        $this->assertGuest();
    }

    public function test_account_shows_private_links_only_to_the_buyer(): void
    {
        $buyer = User::factory()->create();
        $other = User::factory()->create();
        CoursePurchase::create([
            'user_id' => $buyer->id, 'email' => $buyer->email, 'course_id' => $this->course->id,
            'status' => 'paid', 'amount_cents' => 15000, 'access_token' => str_repeat('a', 64), 'paid_at' => now(),
        ]);

        $this->actingAs($buyer)->get(route('account.courses'))->assertOk()
            ->assertSee('https://t.me/wax-materials', false)
            ->assertSee('https://t.me/wax-chat', false);

        $this->actingAs($other)->get(route('account.courses'))->assertOk()
            ->assertDontSee('t.me/wax')
            ->assertSee(__('Vous n’avez pas encore de formation.'));

        auth()->logout();
        $this->get(route('account.courses'))->assertRedirect();
    }

    public function test_a_customer_can_only_own_a_course_once(): void
    {
        $user = User::factory()->create();
        $row = ['user_id' => $user->id, 'course_id' => $this->course->id, 'status' => 'paid', 'amount_cents' => 1, 'access_token' => str_repeat('b', 64)];
        CoursePurchase::create($row);

        $this->expectException(\Illuminate\Database\QueryException::class);
        CoursePurchase::create($row);
    }
}
