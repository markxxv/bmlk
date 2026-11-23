<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Order;
use App\Services\Invoices\InvoiceData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_b2b_checkout_requires_company_and_vat_but_reuses_shipping_address_by_default(): void
    {
        $this->postJson(route('checkout.store'), [
            'billing_type' => 'business', 'billing_same_as_shipping' => '1',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['billing_company', 'billing_vat'])
            ->assertJsonMissingValidationErrors([
                'billing_address', 'billing_zip', 'billing_city', 'billing_country_code',
            ]);
    }

    public function test_b2b_with_separate_fiscal_address_requires_all_address_fields(): void
    {
        $this->postJson(route('checkout.store'), [
            'billing_type' => 'business', 'billing_same_as_shipping' => '0',
            'billing_company' => 'Atelier Test SARL', 'billing_vat' => 'FR12345678901',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'billing_address', 'billing_zip', 'billing_city', 'billing_country_code',
            ]);
    }

    public function test_b2c_does_not_require_business_invoice_fields(): void
    {
        $this->postJson(route('checkout.store'), [
            'billing_type' => 'individual',
        ])->assertUnprocessable()
            ->assertJsonMissingValidationErrors([
                'billing_company', 'billing_vat', 'billing_address',
                'billing_zip', 'billing_city', 'billing_country_code',
            ]);
    }

    public function test_invoice_uses_fiscal_address_for_a_business_and_shipping_for_an_individual(): void
    {
        $business = $this->order([
            'billing_type' => 'business',
            'billing_company' => 'Atelier Test SARL',
            'billing_vat' => 'FR12345678901',
            'billing_address' => '15 avenue Fiscal',
            'billing_zip' => '69001',
            'billing_city' => 'Lyon',
            'billing_country_code' => 'FR',
            'billing_country' => 'France',
        ]);

        $address = InvoiceData::forOrder($business)['address'];
        $this->assertContains('Atelier Test SARL', $address);
        $this->assertContains('TVA / VAT: FR12345678901', $address);
        $this->assertContains('15 avenue Fiscal', $address);
        $this->assertNotContains('10 rue Livraison', $address);

        $consumerAddress = InvoiceData::forOrder($this->order())['address'];
        $this->assertContains('Marie Dupont', $consumerAddress);
        $this->assertContains('10 rue Livraison', $consumerAddress);
    }

    private function order(array $billing = []): Order
    {
        return Order::query()->create(array_merge([
            'first_name' => 'Marie', 'last_name' => 'Dupont',
            'phone' => '0102030405', 'email' => 'marie@example.com',
            'country' => 'France', 'shipping_country_code' => 'FR',
            'address' => '10 rue Livraison', 'zip' => '75001', 'city' => 'Paris',
            'items' => [['name' => 'Gel', 'quantity' => 1, 'unit_price' => 10, 'line_total' => 10]],
            'subtotal' => 10, 'delivery_cost' => 0, 'total_amount' => 10,
            'payment_status' => 'paid', 'status' => 'pending',
        ], $billing));
    }
}
