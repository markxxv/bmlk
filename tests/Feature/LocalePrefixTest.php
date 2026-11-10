<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalePrefixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_default_locale_is_served_without_prefix(): void
    {
        $this->get('/livraison')->assertOk()->assertSee('<html lang="fr"', false);
        $this->get('/')->assertOk()->assertSee('<html lang="fr"', false);
    }

    public function test_prefixed_locales_are_served_and_set_the_html_lang(): void
    {
        $this->get('/en/livraison')->assertOk()->assertSee('<html lang="en"', false);
        $this->get('/es/livraison')->assertOk()->assertSee('<html lang="es"', false);
        $this->get('/es')->assertOk()->assertSee('<html lang="es"', false);
    }

    public function test_default_locale_prefix_redirects_to_the_unprefixed_url(): void
    {
        $this->get('/fr/livraison')->assertRedirect('/livraison')->assertStatus(301);
        $this->get('/fr/shop?page=2')->assertRedirect('/shop?page=2');
        $this->get('/fr')->assertRedirect('/');
    }

    public function test_unknown_locale_prefix_is_not_found(): void
    {
        $this->get('/ro/livraison')->assertNotFound();
        $this->get('/de')->assertNotFound();
    }

    public function test_generated_links_keep_the_current_locale(): void
    {
        $this->get('/en/livraison')
            ->assertOk()
            ->assertSee(url('/en/shop'), false)
            ->assertSee(url('/en/formations'), false);

        $this->get('/livraison')
            ->assertOk()
            ->assertSee(url('/shop'), false)
            ->assertDontSee(url('/fr/shop'), false);
    }

    public function test_route_helper_outside_a_localized_request_is_unprefixed(): void
    {
        $this->assertSame(url('/shop'), route('shop.index'));
        $this->assertSame(url('/es/shop'), route('shop.index', ['locale' => 'es']));
        $this->assertSame(url('/'), route('home'));
        $this->assertSame(url('/en'), route('home', ['locale' => 'en']));
    }

    public function test_language_switcher_and_hreflang_link_to_every_locale(): void
    {
        $this->get('/en/livraison')
            ->assertOk()
            ->assertSee('hreflang="fr" href="'.url('/livraison').'"', false)
            ->assertSee('hreflang="en" href="'.url('/en/livraison').'"', false)
            ->assertSee('hreflang="es" href="'.url('/es/livraison').'"', false)
            ->assertSee('hreflang="x-default" href="'.url('/livraison').'"', false);
    }

    public function test_slug_pages_link_to_the_translated_slug_of_each_locale(): void
    {
        $category = Category::query()->create(['name' => ['fr' => 'Soins'], 'slug' => ['fr' => 'soins']]);
        Product::query()->create([
            'category_id' => $category->id,
            'name' => ['fr' => 'Gel', 'en' => 'Gel', 'es' => 'Gel'],
            'slug' => ['fr' => 'gel-fr', 'en' => 'gel-en', 'es' => 'gel-es'],
            'active' => true,
            'price' => 12,
        ]);

        $this->get('/es/shop/product/gel-es')
            ->assertOk()
            ->assertSee('hreflang="fr" href="'.url('/shop/product/gel-fr').'"', false)
            ->assertSee('hreflang="en" href="'.url('/en/shop/product/gel-en').'"', false)
            ->assertSee('hreflang="es" href="'.url('/es/shop/product/gel-es').'"', false);

        // A French slug under a prefix is redirected to that locale's own slug.
        $this->get('/en/shop/product/gel-fr')->assertRedirect(url('/en/shop/product/gel-en'));
    }

    public function test_sitemap_lists_every_locale_with_alternates(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>'.url('/shop').'</loc>', false)
            ->assertSee('<loc>'.url('/en/shop').'</loc>', false)
            ->assertSee('<loc>'.url('/es/shop').'</loc>', false)
            ->assertSee('hreflang="es" href="'.url('/es/shop').'"', false);
    }

    public function test_emailed_and_api_routes_stay_unprefixed(): void
    {
        $this->assertSame(url('/api/promo/TESTCODE'), route('api.promo', ['code' => 'TESTCODE']));
        $this->assertSame(url('/account/reset-password/abc'), route('password.reset', ['token' => 'abc']));
        $this->get('/en/api/promo/TESTCODE')->assertNotFound();
    }
}
