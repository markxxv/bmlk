<?php

namespace Tests\Feature;

use App\Http\Controllers\ShopController;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_products_are_available_by_default_and_can_be_paused(): void
    {
        $product = $this->product();
        $this->assertTrue($product->fresh()->available);
        $product->update(['available' => false]);
        $this->assertFalse($product->fresh()->available);
        $this->assertTrue($product->fresh()->active);
    }

    public function test_availability_endpoint_excludes_paused_hidden_and_missing_products(): void
    {
        $available = $this->product();
        $paused = $this->product();
        $paused->update(['available' => false]);
        $hidden = $this->product();
        $hidden->update(['active' => false]);

        $this->postJson(route('api.cart.availability'), [
            'product_ids' => [$available->id, $paused->id, $hidden->id, 999999],
        ])->assertOk()->assertJsonPath('available_ids', [$available->id]);
    }

    public function test_unavailable_product_cannot_be_processed_for_payment_or_shipping(): void
    {
        $product = $this->product();
        $product->update(['available' => false]);

        try {
            app(ShopController::class)->normalizeItems([
                ['product_id' => $product->id, 'quantity' => 1],
            ]);
            $this->fail('An unavailable product must not be accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('cart_items', $exception->errors());
        }
    }

    private function product(): Product
    {
        $category = Category::query()->first() ?? Category::query()->create([
            'name' => ['fr' => 'Soins'],
            'slug' => ['fr' => 'soins'],
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => ['fr' => 'Produit test'],
            'slug' => ['fr' => 'produit-'.bin2hex(random_bytes(5))],
            'active' => true,
            'price' => 12,
        ]);
    }
}
