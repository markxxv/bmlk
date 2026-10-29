<?php

namespace Tests\Feature;

use App\Filament\Resources\RoleApplications\Pages\EditRoleApplication;
use App\Models\RoleApplication;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerApplicationAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_approval_saves_influencer_code_and_discount_on_the_user(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();
        $application = RoleApplication::create([
            'user_id' => $customer->id,
            'role' => 'influencer',
            'status' => 'pending',
        ]);

        Livewire::actingAs($admin)->test(EditRoleApplication::class, ['record' => $application->id])
            ->set('data.status', 'approved')
            ->set('data.influencer_code', 'brand_25')
            ->set('data.influencer_discount', 25.5)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('approved', $application->fresh()->status);
        $this->assertTrue($customer->fresh()->is_influencer);
        $this->assertSame('BRAND_25', $customer->fresh()->influencer_code);
        $this->assertSame(25.5, (float) $customer->fresh()->influencer_discount);
    }

    public function test_code_must_be_unique_regardless_of_case_and_error_does_not_approve(): void
    {
        $admin = $this->admin();
        User::factory()->create(['influencer_code' => 'TAKEN_CODE']);
        $customer = User::factory()->create();
        $application = RoleApplication::create([
            'user_id' => $customer->id,
            'role' => 'influencer',
            'status' => 'pending',
        ]);

        Livewire::actingAs($admin)->test(EditRoleApplication::class, ['record' => $application->id])
            ->set('data.status', 'approved')
            ->set('data.influencer_code', 'taken_code')
            ->call('save')
            ->assertHasErrors(['data.influencer_code']);

        $this->assertSame('pending', $application->fresh()->status);
        $this->assertFalse($customer->fresh()->is_influencer);
        $this->assertNull($customer->fresh()->influencer_code);
    }

    public function test_existing_code_can_be_saved_unchanged_by_its_owner(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['influencer_code' => 'OWN_CODE']);
        $application = RoleApplication::create([
            'user_id' => $customer->id,
            'role' => 'influencer',
            'status' => 'approved',
        ]);

        Livewire::actingAs($admin)->test(EditRoleApplication::class, ['record' => $application->id])
            ->set('data.influencer_discount', 40)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('OWN_CODE', $customer->fresh()->influencer_code);
        $this->assertSame(40.0, (float) $customer->fresh()->influencer_discount);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }
}
