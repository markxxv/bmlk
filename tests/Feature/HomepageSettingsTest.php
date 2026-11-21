<?php

namespace Tests\Feature;

use App\Filament\Pages\Homepage;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\HomepageBenefitIcons;
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
        $settings['badge_percent'] = 55;
        $settings['translations']['en']['headline'] = 'Precision for everyone';
        $settings['translations']['en']['badge'] = 'Seasonal offer';
        $settings['translations']['en']['badge_note'] = '22–29 November';
        $settings['translations']['en']['meta_title'] = 'Our new homepage';
        $settings['translations']['en']['meta_description'] = 'Find your new favourites.';

        HomepageSettings::save(['settings' => $settings]);
        $response = $this->get('/en')->assertOk()->assertSee('data-theme="black-friday"', false)->assertSee('Precision for everyone')->assertSee('Seasonal offer')->assertSee('22–29 November')->assertSee('<title>Our new homepage</title>', false)->assertSee('Find your new favourites.');
        // Main offer, mobile medallion, desktop medallion use the same number.
        $this->assertSame(3, substr_count($response->getContent(), '−55&nbsp;%'));
        $this->get('/')->assertOk()->assertSee('plus de précision')->assertSee('−55&nbsp;%', false)->assertDontSee('Precision for everyone')->assertDontSee('22–29 November');

        $settings['badge_enabled'] = false;
        HomepageSettings::save(['settings' => $settings]);
        $this->get('/en')->assertOk()->assertDontSee('Seasonal offer')->assertDontSee('data-home-field="badge_percent"', false)->assertDontSee('22–29 November');
        $this->assertDatabaseCount('site_settings', 1);
        $this->assertSame(false, SiteSetting::first()->value['badge_enabled']);
    }

    public function test_existing_settings_gain_a_safe_default_discount_without_losing_custom_copy(): void
    {
        $legacy = HomepageSettings::defaults();
        unset($legacy['badge_percent'], $legacy['benefit_icons']);
        foreach ($legacy['translations'] as &$copy) {
            unset($copy['badge_note']);
        }
        unset($copy);
        $legacy['translations']['fr']['badge'] = 'Offre spéciale';
        SiteSetting::query()->create(['key' => HomepageSettings::KEY, 'value' => $legacy]);

        $settings = HomepageSettings::load();
        $this->assertSame(70, $settings['badge_percent']);
        $this->assertSame(HomepageBenefitIcons::DEFAULTS, $settings['benefit_icons']);
        $this->assertSame('Offre spéciale', $settings['translations']['fr']['badge']);
        $this->assertSame('', $settings['translations']['fr']['badge_note']);
        $this->assertSame($settings, HomepageSettings::save(['settings' => $settings]));
    }

    public function test_campaign_rejects_invalid_discount_values(): void
    {
        $settings = HomepageSettings::defaults();
        $settings['badge_enabled'] = true;

        foreach ([0, 101, 70.5, '70', null] as $invalid) {
            $settings['badge_percent'] = $invalid;
            try {
                HomepageSettings::save(['settings' => $settings]);
                $this->fail('Invalid discount was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('data.settings.badge_percent', $exception->errors());
            }
        }
        $this->assertDatabaseCount('site_settings', 0);
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

    public function test_benefit_icons_are_shared_by_every_language_and_validated_against_lucide(): void
    {
        $settings = HomepageSettings::defaults();
        $settings['benefit_icons']['feature_one'] = 'shield-check';
        HomepageSettings::save(['settings' => $settings]);

        $this->assertSame($settings, HomepageSettings::load());
        foreach (['/', '/en', '/es'] as $url) {
            $this->get($url)->assertOk()->assertSee('data-home-icon="feature_one" data-icon-name="shield-check"', false);
        }

        foreach (['unknown-icon-xyz', '../../shield-check', '<svg></svg>'] as $invalid) {
            $settings['benefit_icons']['feature_one'] = $invalid;
            try {
                HomepageSettings::save(['settings' => $settings]);
                $this->fail('Invalid icon was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('data.settings.benefit_icons.feature_one', $exception->errors());
            }
        }
        $this->assertSame('shield-check', HomepageSettings::load()['benefit_icons']['feature_one']);
    }

    public function test_icon_catalogue_is_admin_only_searchable_and_paginated(): void
    {
        $this->get(route('homepage.icons'))->assertRedirect();
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('homepage.icons'))->assertForbidden();
        $user->forceFill(['is_admin' => true])->save();

        $this->actingAs($user)->getJson(route('homepage.icons', ['q' => 'shield check']))
            ->assertOk()->assertJsonPath('icons.0.name', 'shield-check');
        $this->assertStringContainsString('<svg', HomepageBenefitIcons::search('shield-check', 1)['icons'][0]['svg']);
        $first = $this->getJson(route('homepage.icons'))->assertOk()->assertJsonCount(36, 'icons')->json();
        $second = $this->getJson(route('homepage.icons', ['page' => 2]))->assertOk()->assertJsonPath('page', 2)->json();
        $this->assertGreaterThan(36, $first['total']);
        $this->assertEmpty(array_intersect(array_column($first['icons'], 'name'), array_column($second['icons'], 'name')));
        $this->getJson(route('homepage.icons', ['q' => '../../shield-check']))->assertOk()->assertJsonPath('total', 0);
    }

    public function test_filament_publish_persists_the_deferred_editor_state(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);

        $settings = HomepageSettings::defaults();
        $settings['theme'] = 'porcelain';
        $settings['benefit_icons']['feature_three'] = 'award';
        $settings['translations']['fr']['headline'] = 'Une nouvelle précision';

        Livewire::test(Homepage::class)
            ->set('data.settings', $settings)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($settings, HomepageSettings::load());
    }
}
