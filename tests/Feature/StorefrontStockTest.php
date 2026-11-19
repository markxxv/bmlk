<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_is_displayed_at_the_correct_thresholds_in_all_storefront_languages(): void
    {
        $this->withoutVite();

        $category = Category::query()->create([
            'name' => ['fr' => 'Stock test', 'en' => 'Stock test', 'es' => 'Stock test'],
            'slug' => ['fr' => 'stock-test', 'en' => 'stock-test', 'es' => 'stock-test'],
        ]);

        $this->product($category, 'stock-low', 7);
        $this->product($category, 'stock-threshold', 10);
        $this->product($category, 'stock-high', 15);
        $this->product($category, 'stock-zero', 0);
        $this->product($category, 'stock-untracked', null);

        foreach ([
            ['', '7 en stock', '15 en stock', 'Rupture de stock'],
            ['/en', '7 in stock', '15 in stock', 'Out of stock'],
            ['/es', '7 en stock', '15 en stock', 'Sin stock'],
        ] as [$prefix, $lowLabel, $highLabel, $zeroLabel]) {
            $this->get($prefix.'/shop')
                ->assertOk()
                ->assertSee('data-stock-badge="7"', false)
                ->assertSee($lowLabel)
                ->assertSee('data-stock-badge="0"', false)
                ->assertSee($zeroLabel)
                ->assertDontSee('data-stock-badge="10"', false)
                ->assertDontSee('data-stock-badge="15"', false)
                ->assertDontSee('data-stock-badge=""', false);

            $this->get($prefix.'/shop/product/stock-low')
                ->assertOk()
                ->assertSee('data-product-stock="7"', false)
                ->assertSee($lowLabel);

            $this->get($prefix.'/shop/product/stock-high')
                ->assertOk()
                ->assertSee('data-product-stock="15"', false)
                ->assertSee($highLabel);

            $this->get($prefix.'/shop/product/stock-zero')
                ->assertOk()
                ->assertSee('data-product-stock="0"', false)
                ->assertSee($zeroLabel);

            $this->get($prefix.'/shop/product/stock-untracked')
                ->assertOk()
                ->assertDontSee('data-product-stock=', false);
        }

        $this->get('/shop/category/stock-test')
            ->assertOk()
            ->assertSee('data-stock-badge="7"', false);
    }

    private function product(Category $category, string $slug, ?int $stock): Product
    {
        return Product::query()->create([
            'category_id' => $category->id,
            'name' => ['fr' => $slug, 'en' => $slug, 'es' => $slug],
            'slug' => ['fr' => $slug, 'en' => $slug, 'es' => $slug],
            'price' => 12,
            'stock' => $stock,
            'active' => true,
        ]);
    }
}
