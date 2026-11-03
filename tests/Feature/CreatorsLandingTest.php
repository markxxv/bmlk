<?php

namespace Tests\Feature;

use App\Models\RoleApplication;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CreatorsLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_creators_page_is_public(): void
    {
        $this->get(route('createurs'))
            ->assertOk()
            ->assertSee('Testez')
            ->assertSee('createurs-seal-ring.svg')
            ->assertSee('data-rotating-seal')
            ->assertSee('data-creator-faq')
            ->assertSee('data-creator-faq-panel')
            ->assertSee('Envoyer ma candidature');
    }

    public function test_guest_application_creates_account_and_pending_existing_role(): void
    {
        Notification::fake();

        $this->post(route('createurs.apply'), [
            'name' => 'Camille Martin',
            'email' => 'Camille@Example.com',
            'profile' => '@camille.nails',
            'message' => 'Je présente mes manucures.',
            'major' => '1',
            'consent' => '1',
        ])->assertRedirect(route('account.roles'));

        $user = User::query()->where('email', 'camille@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->password);
        $this->assertFalse($user->is_influencer);
        $this->assertDatabaseHas('role_applications', [
            'user_id' => $user->id,
            'role' => 'influencer',
            'status' => 'pending',
            'message' => "Profil : @camille.nails\n\nJe présente mes manucures.",
        ]);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_existing_guest_email_cannot_be_taken_over(): void
    {
        $user = User::factory()->create(['email' => 'camille@example.com']);

        $this->post(route('createurs.apply'), [
            'name' => 'Someone Else',
            'email' => 'camille@example.com',
            'profile' => '@other',
            'major' => '1',
            'consent' => '1',
        ])->assertRedirect(route('account.login'));

        $this->assertGuest();
        $this->assertSame(1, User::query()->count());
        $this->assertSame(0, RoleApplication::query()->count());
        $this->assertSame($user->id, User::query()->firstOrFail()->id);
    }

    public function test_verified_customer_can_apply_once(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['email_verified_at' => now()])->save();

        $payload = [
            'name' => $user->name,
            'email' => $user->email,
            'profile' => '@camille.nails',
            'major' => '1',
            'consent' => '1',
        ];

        $this->actingAs($user)->post(route('createurs.apply'), $payload)
            ->assertRedirect(route('account.roles'));
        $this->actingAs($user)->post(route('createurs.apply'), $payload)
            ->assertRedirect(route('account.roles'));
        $this->assertSame(1, $user->roleApplications()->where('role', 'influencer')->count());
    }

    public function test_minor_application_is_rejected(): void
    {
        $this->from(route('createurs'))->post(route('createurs.apply'), [
            'name' => 'Camille Martin',
            'email' => 'camille@example.com',
            'profile' => '@camille.nails',
            'consent' => '1',
        ])->assertRedirect(route('createurs'))->assertSessionHasErrors(['major']);

        $this->assertDatabaseCount('users', 0);
    }
}
