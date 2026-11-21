<?php

namespace Tests\Feature;

use App\Filament\Pages\Homepage;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\HomepageSettings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class HomepageSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_defaults_preserve_existing_hero_and_localized_seo(): void
    {
        $this->get('/')->assertOk()->assertSee('Moins de produits')->assertSee('plus de précision')->assertSee('data-theme="signature"', false);
        $this->get('/en')->assertOk()->assertSee('Fewer products')->assertSee('HEMA-Free UV Gels, Bases &amp; Tops', false);
        $this->get('/es')->assertOk()->assertSee('Menos productos')->assertSee('más precisión');
        $this->assertDatabaseCount('site_settings', 0);
    }

    public function test_saved_hero_badge_and_metadata_are_localized_and_update_one_record(): void
    {
        $settings = HomepageSettings::defaults();
        $settings['theme'] = 'black-friday';
        $settings['badge_enabled'] = true;
        $settings['translations']['en']['headline'] = 'Precision for everyone';
        $settings['translations']['en']['badge'] = 'Seasonal offer';
        $settings['translations']['en']['meta_title'] = 'Our new homepage';
        $settings['translations']['en']['meta_description'] = 'Find your new favourites.';

        HomepageSettings::save(['settings' => $settings]);
        $this->get('/en')->assertOk()->assertSee('data-theme="black-friday"', false)->assertSee('Precision for everyone')->assertSee('Seasonal offer')->assertSee('<title>Our new homepage</title>', false)->assertSee('Find your new favourites.');
        $this->get('/')->assertOk()->assertSee('plus de précision')->assertDontSee('Precision for everyone');

        $settings['badge_enabled'] = false;
        HomepageSettings::save(['settings' => $settings]);
        $this->get('/en')->assertOk()->assertDontSee('Seasonal offer');
        $this->assertDatabaseCount('site_settings', 1);
        $this->assertSame(false, SiteSetting::first()->value['badge_enabled']);
    }

    public function test_unsupported_theme_extra_keys_and_markup_cannot_be_saved(): void
    {
        $settings = HomepageSettings::defaults();
        $settings['theme'] = 'url(https://example.com)';
        $settings['translations']['fr']['headline'] = '<b>Injected HTML</b>';
        $settings['translations']['en']['custom_html'] = 'Unexpected';

        try {
            HomepageSettings::save(['settings' => $settings]);
            $this->fail('Invalid settings were accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.settings.theme', $exception->errors());
            $this->assertArrayHasKey('data.settings.translations.fr.headline', $exception->errors());
            $this->assertArrayHasKey('data.settings.translations.en', $exception->errors());
        }
        $this->assertDatabaseCount('site_settings', 0);
    }

    public function test_preview_is_admin_only_and_uses_the_same_hero(): void
    {
        $this->get(route('homepage.preview'))->assertRedirect();
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('homepage.preview'))->assertForbidden();
        $user->forceFill(['is_admin' => true])->save();
        $this->actingAs($user)->get(route('homepage.preview'))->assertOk()->assertSee('data-home-hero', false)->assertSee('data-home-field="headline"', false);
        $this->assertTrue(Homepage::canAccess());
        $this->assertSame('/admin/homepage', Homepage::getUrl(isAbsolute: false, panel: 'admin'));
    }

    public function test_filament_publish_persists_the_deferred_editor_state(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);

        $settings = HomepageSettings::defaults();
        $settings['theme'] = 'porcelain';
        $settings['translations']['fr']['headline'] = 'Une nouvelle précision';

        Livewire::test(Homepage::class)
            ->set('data.settings', $settings)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($settings, HomepageSettings::load());
    }
}
