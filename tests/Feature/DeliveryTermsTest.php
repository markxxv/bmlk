<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryTermsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_delivery_page_explains_business_hours_and_friday_example(): void
    {
        $this->get(route('delivery.terms'))
            ->assertOk()
            ->assertSee('3 à 5 jours ouvrés')
            ->assertSee('10 h à 19 h')
            ->assertSee('vendredi à 19 h 30')
            ->assertSee('lundi à 10 h')
            ->assertSee('jours fériés')
            ->assertSee('Vos droits légaux restent pleinement applicables');
    }

    public function test_checkout_links_to_delivery_terms_before_payment(): void
    {
        $this->get(route('checkout'))
            ->assertOk()
            ->assertSee(route('delivery.terms'), false)
            ->assertSee('Livraison sous 3 à 5 jours ouvrés');
    }
}
