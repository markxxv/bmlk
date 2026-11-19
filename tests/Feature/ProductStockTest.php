<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin);
    }

    public function test_stock_can_be_changed_and_cleared_inline_without_affecting_availability(): void
    {
        $product = $this->product();

        $page = Livewire::test(ListProducts::class)
            ->call('toggleStockEditing')
            ->call('updateTableColumnState', 'stock', (string) $product->id, '42');

        $this->assertSame(42, (int) $product->fresh()->stock);

        $page->call('updateTableColumnState', 'stock', (string) $product->id, '0');
        $this->assertNotNull($product->fresh()->stock);
        $this->assertSame(0, (int) $product->fresh()->stock);
        $this->assertTrue($product->fresh()->available);

        $page->call('updateTableColumnState', 'stock', (string) $product->id, '');
        $this->assertNull($product->fresh()->stock);
        $this->assertTrue($product->fresh()->available);
    }

    public function test_invalid_stock_does_not_overwrite_the_saved_value(): void
    {
        $product = $this->product(12);
        $page = Livewire::test(ListProducts::class)
            ->call('toggleStockEditing');

        foreach (['-1', '1.5', 'abc', '2147483648'] as $invalid) {
            $page->call('updateTableColumnState', 'stock', (string) $product->id, $invalid);

            $this->assertSame(12, (int) $product->fresh()->stock);
        }
    }

    public function test_stock_is_read_only_until_editing_is_enabled_and_is_locked_again_afterwards(): void
    {
        $product = $this->product(12);

        $page = Livewire::test(ListProducts::class)
            ->assertSet('editingStock', false)
            ->assertCanRenderTableColumn('stock_readonly')
            ->assertCanNotRenderTableColumn('stock')
            ->call('updateTableColumnState', 'stock', (string) $product->id, '99');

        $this->assertSame(12, (int) $product->fresh()->stock);

        $page->callAction('toggleStockEditing')
            ->assertSet('editingStock', true)
            ->assertCanRenderTableColumn('stock')
            ->assertCanNotRenderTableColumn('stock_readonly')
            ->call('updateTableColumnState', 'stock', (string) $product->id, '42');

        $this->assertSame(42, (int) $product->fresh()->stock);

        $page->callAction('toggleStockEditing')
            ->assertSet('editingStock', false)
            ->call('updateTableColumnState', 'stock', (string) $product->id, '99');

        $this->assertSame(42, (int) $product->fresh()->stock);
    }

    public function test_stock_sort_is_preserved_when_editing_mode_changes(): void
    {
        Livewire::test(ListProducts::class)
            ->call('sortTable', 'stock_readonly', 'asc')
            ->assertSet('tableSort', 'stock_readonly:asc')
            ->call('toggleStockEditing')
            ->assertSet('tableSort', 'stock:asc')
            ->call('toggleStockEditing')
            ->assertSet('tableSort', 'stock_readonly:asc');
    }

    public function test_unset_stock_filter_does_not_include_zero_stock(): void
    {
        $missing = $this->product();
        $zero = $this->product(0);
        $positive = $this->product(12);

        Livewire::test(ListProducts::class)
            ->filterTable('stock_unset')
            ->assertCanSeeTableRecords([$missing])
            ->assertCanNotSeeTableRecords([$zero, $positive]);
    }

    private function product(?int $stock = null): Product
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
            'stock' => $stock,
        ]);
    }
}
