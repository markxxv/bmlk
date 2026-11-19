<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Tables\Columns\EditableColumn;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
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
        $page = Livewire::test(ListProducts::class);

        foreach (['-1', '1.5', 'abc', '2147483648'] as $invalid) {
            $page->call('updateTableColumnState', 'stock', (string) $product->id, $invalid);

            $this->assertSame(12, (int) $product->fresh()->stock);
        }
    }

    public function test_stock_is_rendered_as_a_custom_editable_column_and_can_be_sorted(): void
    {
        $product = $this->product(12);

        Livewire::test(ListProducts::class)
            ->assertTableColumnExists('stock', fn (EditableColumn $column): bool => ! $column->isDisabled(), $product)
            ->assertCanRenderTableColumn('stock')
            ->assertSeeHtml('data-inline-edit-column="stock"')
            ->call('sortTable', 'stock', 'asc')
            ->assertSet('tableSort', 'stock:asc');
    }

    public function test_stock_cannot_be_updated_when_the_product_update_policy_denies_access(): void
    {
        $product = $this->product(12);

        Gate::policy(Product::class, ReadOnlyStockProductPolicy::class);

        Livewire::test(ListProducts::class)
            ->assertTableColumnExists('stock', fn (EditableColumn $column): bool => $column->isDisabled(), $product)
            ->call('updateTableColumnState', 'stock', (string) $product->id, '99');

        $this->assertSame(12, (int) $product->fresh()->stock);
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

class ReadOnlyStockProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return true;
    }

    public function update(User $user, Product $product): bool
    {
        return false;
    }
}
