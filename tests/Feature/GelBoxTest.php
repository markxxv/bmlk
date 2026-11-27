<?php

namespace Tests\Feature;

use App\Http\Controllers\ShopController;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GelBoxTest extends TestCase
{
    use RefreshDatabase;

    private function catalog(): array
    {
        $category = Category::query()->find(1) ?? Category::query()->create([
            'name' => ['fr' => 'Gels'], 'slug' => ['fr' => 'gels'],
        ]);
        $other = Category::query()->create(['name' => ['fr' => 'Boxes'], 'slug' => ['fr' => 'boxes']]);
        $gel = Product::query()->create(['category_id' => $category->id, 'name' => ['fr' => 'Gel A'], 'slug' => ['fr' => 'gel-a'], 'active' => true, 'price' => 10, 'stock' => 20]);
        $box = Product::query()->create(['category_id' => $other->id, 'name' => ['fr' => 'Box'], 'slug' => ['fr' => 'box'], 'active' => true, 'price' => 45, 'stock' => 10, 'requires_gel_selection' => true]);

        return [$box->refresh(), $gel];
    }

    public function test_box_requires_six_gels_and_uses_server_snapshots_and_box_price(): void
    {
        [$box, $gel] = $this->catalog();
        $selection = array_fill(0, 6, ['id' => $gel->id, 'name' => 'Tampered']);
        $item = app(ShopController::class)->normalizeItems([
            ['product_id' => $box->id, 'quantity' => 2, 'box_gels' => $selection],
        ])->first();

        $this->assertSame(45.0, $item['unit_price']);
        $this->assertSame(90.0, $item['line_total']);
        $this->assertCount(6, $item['box_gels']);
        $this->assertSame('Gel A', $item['box_gels'][0]['name']);

        app(ProductStockService::class)->decrementForItems([$item]);
        $this->assertSame(8, $box->fresh()->stock);
        $this->assertSame(8, $gel->fresh()->stock);
    }

    public function test_missing_invalid_or_paused_gels_are_rejected(): void
    {
        [$box, $gel] = $this->catalog();
        foreach ([[], array_fill(0, 5, ['id' => $gel->id]), array_fill(0, 7, ['id' => $gel->id]), array_fill(0, 6, ['id' => $box->id])] as $selection) {
            try {
                app(ShopController::class)->normalizeItems([['product_id' => $box->id, 'box_gels' => $selection]]);
                $this->fail('Invalid gel selection was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('box_gels', $exception->errors());
            }
        }

        $gel->update(['available' => false]);
        $this->postJson(route('api.cart.availability'), [
            'product_ids' => [$box->id],
            'cart_items' => [['product_id' => $box->id, 'box_gels' => array_fill(0, 6, ['id' => $gel->id])]],
        ])->assertOk()->assertJsonPath('invalid_box_indexes', [0]);
    }

    public function test_box_count_is_configurable_and_stock_uses_the_order_snapshot(): void
    {
        [$box, $gel] = $this->catalog();
        $this->assertSame(6, $box->gel_selection_count);

        foreach ([1, 3, 12] as $count) {
            $box->update(['gel_selection_count' => $count]);
            $selection = array_fill(0, $count, ['id' => $gel->id]);
            $cart = ['product_id' => $box->id, 'quantity' => 1, 'box_gels' => $selection];

            $this->postJson(route('api.cart.availability'), [
                'product_ids' => [$box->id], 'cart_items' => [$cart],
            ])->assertOk()->assertJsonCount($count, 'box_gels.0')->assertJsonPath('invalid_box_indexes', []);

            $item = app(ShopController::class)->normalizeItems([$cart])->first();
            $this->assertCount($count, $item['box_gels']);
            $this->assertSame(45.0, $item['unit_price']);

            $box->update(['gel_selection_count' => $count + 1]);
            $this->postJson(route('api.cart.availability'), [
                'product_ids' => [$box->id], 'cart_items' => [$cart],
            ])->assertOk()->assertJsonPath('invalid_box_indexes', [0]);

            $this->assertSame($count, app(ProductStockService::class)->quantitiesForItems([$item])[$gel->id]);
        }
    }

    public function test_stale_box_does_not_block_an_ordinary_cart_line(): void
    {
        [$box, $gel] = $this->catalog();
        $this->postJson(route('api.cart.availability'), [
            'product_ids' => [$box->id, $gel->id],
            'cart_items' => [
                ['product_id' => $box->id],
                ['product_id' => $gel->id],
            ],
        ])->assertOk()->assertJsonPath('invalid_box_indexes', [0])->assertJsonPath('cart_prices.1', 10);

        $this->assertFalse($gel->fresh()->requires_gel_selection);
    }
}
