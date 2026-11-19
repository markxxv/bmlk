<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Http\Controllers\ShopController;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_temporary_discount_uses_authoritative_catalog_prices_and_resets_without_changing_base_prices(): void
    {
        $product = $this->product('discount-test');
        $this->assertSame(0, $product->fresh()->discount_percent);

        $product->update(['discount_percent' => 40]);
        $this->assertSame('60', $product->formatted_price);
        $this->assertSame(0.01, $product->priceAfterDiscount(0.01));
        $this->assertNull($product->priceAfterDiscount(null));

        $line = ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 1];
        $item = app(ShopController::class)->normalizeItems([$line])->first();
        $this->assertEquals(60, $item['unit_price']);
        $this->assertEquals(120, $item['line_total']);

        $this->postJson(route('api.cart.availability'), [
            'product_ids' => [$product->id],
            'cart_items' => [$line],
        ])->assertOk()->assertJsonPath('cart_prices.0', 60);

        foreach (['', '/en', '/es'] as $prefix) {
            $this->get($prefix.'/shop')
                ->assertOk()->assertSee('−40%')->assertSee('60 €');
            $this->get($prefix.'/shop/product/discount-test')
                ->assertOk()->assertSee('−40%')->assertSee('60 €');
        }

        $product->update(['discount_percent' => 0]);
        $this->assertSame('100', $product->formatted_price);
        $this->assertEquals(100, $product->price);
        $this->assertEquals(125, $product->compare_at_price);
        $this->postJson(route('api.cart.availability'), [
            'product_ids' => [$product->id],
            'cart_items' => [$line],
        ])->assertOk()->assertJsonPath('cart_prices.0', 100);
    }

    public function test_discount_applies_to_priced_options_and_price_ranges(): void
    {
        $product = $this->product('variants-test');
        $product->update([
            'price' => null,
            'price_min' => 5,
            'price_max' => 20,
            'discount_percent' => 40,
            'options' => [[
                'id' => 'pack_quantity',
                'label' => ['FR' => 'Quantité'],
                'values' => [
                    ['quantity' => 1, 'price' => ['amount' => 5]],
                    ['quantity' => 5, 'price' => ['amount' => 20]],
                ],
            ]],
        ]);

        $this->assertSame('3', $product->formatted_price_min);
        $this->assertSame('12', $product->formatted_price_max);

        $item = app(ShopController::class)->normalizeItems([[
            'product_id' => $product->id,
            'quantity' => 2,
            'options' => [['id' => 'pack_quantity', 'quantity' => 5, 'price' => 1]],
        ]])->first();

        $this->assertEquals(12, $item['unit_price']);
        $this->assertEquals(24, $item['line_total']);
        $this->assertEquals(20, $item['options'][0]['price']);
        $this->assertEquals(20, $product->fresh()->options[0]['values'][1]['price']['amount']);
    }

    public function test_admin_can_set_and_reset_discount_for_selected_products(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);

        $first = $this->product('discount-first');
        $second = $this->product('discount-second');
        $other = $this->product('discount-other');

        $page = Livewire::test(ListProducts::class)
            ->selectTableRecords([$first->id, $second->id])
            ->callAction(TestAction::make('setDiscount')->table()->bulk(), data: ['discount_percent' => 40])
            ->assertHasNoFormErrors();

        $this->assertSame(40, $first->fresh()->discount_percent);
        $this->assertSame(40, $second->fresh()->discount_percent);
        $this->assertSame(0, $other->fresh()->discount_percent);

        $page->selectTableRecords([$first->id, $second->id])
            ->callAction(TestAction::make('resetDiscount')->table()->bulk())
            ->assertHasNoFormErrors();

        $this->assertSame(0, $first->fresh()->discount_percent);
        $this->assertSame(0, $second->fresh()->discount_percent);
    }

    private function product(string $slug): Product
    {
        $category = Category::query()->first() ?? Category::query()->create([
            'name' => ['fr' => 'Discount test', 'en' => 'Discount test', 'es' => 'Discount test'],
            'slug' => ['fr' => 'discount-test', 'en' => 'discount-test', 'es' => 'discount-test'],
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => ['fr' => $slug, 'en' => $slug, 'es' => $slug],
            'slug' => ['fr' => $slug, 'en' => $slug, 'es' => $slug],
            'active' => true,
            'price' => 100,
            'compare_at_price' => 125,
        ]);
    }
}
