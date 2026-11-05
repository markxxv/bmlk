<?php

namespace Tests\Feature;

use App\Jobs\SendPartnerApplicationTelegramReminder;
use App\Models\RoleApplication;
use App\Models\User;
use App\Services\PartnerApplicationTelegramNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PartnerApplicationTelegramNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.telegram.bot_token', 'test-token');
        config()->set('services.telegram.chat_id', '-1001234567890');
    }

    public function test_new_application_sends_immediately_without_queue(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true])]);
        Queue::fake();

        $user = $this->customer();
        $this->actingAs($user)
            ->post(route('account.roles.apply'), [
                'role' => 'influencer',
                'message' => 'Je crée du contenu <beauté> & soin.',
            ])->assertRedirect();

        $application = RoleApplication::query()->sole();

        $this->assertSame('pending', $application->status);
        $this->assertNotNull($application->telegram_first_sent_at);
        $this->assertNotNull($application->telegram_last_sent_at);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool =>
            str_contains($request['text'], 'Nouvelle demande de partenariat')
            && str_contains($request['text'], 'Créatrice / Influenceuse')
            && str_contains($request['text'], $user->email)
            && str_contains($request['text'], '&lt;beauté&gt; &amp; soin')
            && str_contains($request['text'], '/admin/role-applications/'.$application->id.'/edit')
        );
        Queue::assertNothingPushed();

        // A duplicate submission does not alert twice or create a second row.
        $this->post(route('account.roles.apply'), ['role' => 'influencer'])->assertRedirect();
        $this->assertSame(1, RoleApplication::query()->count());
        Http::assertSentCount(1);
    }

    public function test_failed_immediate_send_does_not_block_application_and_is_retried_directly(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::sequence()
            ->push(['ok' => false, 'description' => 'Rate limit'], 429)
            ->push(['ok' => true])]);
        Queue::fake();

        $this->actingAs($this->customer())
            ->post(route('account.roles.apply'), ['role' => 'wholesale'])->assertRedirect();

        $application = RoleApplication::query()->sole();
        $this->assertNull($application->telegram_first_sent_at);
        $this->assertNull($application->telegram_claimed_at);

        $this->artisan('partners:telegram-notifications')->assertSuccessful();

        $this->assertNotNull($application->fresh()->telegram_first_sent_at);
        Http::assertSentCount(2);
        Queue::assertNothingPushed();
    }

    public function test_only_one_reminder_is_queued_after_24_hours_and_sends_once(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true])]);
        Queue::fake();

        $this->actingAs($this->customer())
            ->post(route('account.roles.apply'), ['role' => 'wholesale'])->assertRedirect();
        $application = RoleApplication::query()->sole();

        $this->travel(23)->hours();
        $this->artisan('partners:telegram-notifications')->assertSuccessful();
        Queue::assertNothingPushed();

        $this->travel(2)->hours();
        $this->artisan('partners:telegram-notifications')->assertSuccessful();
        $this->artisan('partners:telegram-notifications')->assertSuccessful();

        Queue::assertPushed(SendPartnerApplicationTelegramReminder::class, 1);

        $queued = null;
        Queue::assertPushed(SendPartnerApplicationTelegramReminder::class, function ($job) use ($application, &$queued): bool {
            $queued = $job;
            return $job->applicationId === $application->id
                && $job->connection === 'database'
                && $job->queue === 'partner-telegram';
        });

        $queued->handle(app(PartnerApplicationTelegramNotifier::class));

        Http::assertSentCount(2);
        Http::assertSent(fn ($request): bool =>
            str_contains($request['text'], 'RAPPEL — Demande de partenariat en attente')
            && str_contains($request['text'], 'attend toujours une décision')
        );

        $this->assertNull($application->fresh()->telegram_reminder_queued_at);

        $this->artisan('partners:telegram-notifications')->assertSuccessful();
        Queue::assertPushed(SendPartnerApplicationTelegramReminder::class, 1);

        $this->travel(25)->hours();
        $this->artisan('partners:telegram-notifications')->assertSuccessful();
        Queue::assertPushed(SendPartnerApplicationTelegramReminder::class, 2);
    }

    public function test_approved_application_is_not_reminded_even_if_job_was_queued(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true])]);
        Queue::fake();

        $this->actingAs($this->customer())
            ->post(route('account.roles.apply'), ['role' => 'influencer'])->assertRedirect();
        $application = RoleApplication::query()->sole();

        $this->travel(25)->hours();
        $this->artisan('partners:telegram-notifications')->assertSuccessful();

        $queued = null;
        Queue::assertPushed(SendPartnerApplicationTelegramReminder::class, function ($job) use (&$queued): bool {
            $queued = $job;
            return true;
        });

        $application->update(['status' => 'approved']);
        $queued->handle(app(PartnerApplicationTelegramNotifier::class));

        Http::assertSentCount(1);

        $this->travel(25)->hours();
        $this->artisan('partners:telegram-notifications')->assertSuccessful();
        Queue::assertPushed(SendPartnerApplicationTelegramReminder::class, 1);
    }

    public function test_existing_pending_application_is_picked_up_by_scheduler(): void
    {
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true])]);
        Queue::fake();

        $application = RoleApplication::query()->create([
            'user_id' => $this->customer()->id,
            'role' => 'wholesale',
            'status' => 'pending',
            'message' => 'Bonjour',
        ]);

        $this->artisan('partners:telegram-notifications')->assertSuccessful();

        $this->assertNotNull($application->fresh()->telegram_first_sent_at);
        Http::assertSentCount(1);
        Queue::assertNothingPushed();
    }

    private function customer(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }
}
