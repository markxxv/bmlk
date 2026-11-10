<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRatingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_only_paid_delivered_orders_allow_ratings(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();
        $order = $this->order($buyer, [$product]);

        $this->actingAs($buyer)->get(route('account.index'))->assertOk()->assertDontSee('Évaluez vos produits');
        $this->post(route('account.ratings.store', $order), ['ratings' => [0 => 5]])->assertForbidden();

        $order->update(['sendcloud_tracking_phase' => 'delivered']);
        $this->get(route('account.index'))->assertOk()->assertSee('Évaluez vos produits');

        $order->update(['payment_status' => 'failed']);
        $this->post(route('account.ratings.store', $order), ['ratings' => [0 => 5]])->assertForbidden();
        $this->assertDatabaseCount('product_ratings', 0);
    }

    public function test_partial_ratings_remain_visible_and_can_be_updated_without_duplicates(): void
    {
        $buyer = User::factory()->create();
        $first = $this->product();
        $second = $this->product();
        $order = $this->order($buyer, [$first, $second], ['status' => 'completed']);

        $this->actingAs($buyer)->post(route('account.ratings.store', $order), [
            'ratings' => [0 => 2, 1 => ''],
        ])->assertRedirect();

        $this->assertDatabaseHas('product_ratings', [
            'order_id' => $order->id, 'product_id' => $first->id,
            'order_item_index' => 0, 'rating' => 2,
        ]);
        $this->get(route('account.index'))->assertOk()
            ->assertSee('Évaluez vos produits')->assertSee('1 sur 2 notés');

        $this->post(route('account.ratings.store', $order), ['ratings' => [1 => 5]])->assertRedirect();
        $this->get(route('account.index'))->assertOk()->assertDontSee('Évaluez vos produits');
        $this->get(route('account.order', $order))->assertOk()->assertSee('Vos évaluations');
        $this->assertDatabaseCount('product_ratings', 2);

        $this->post(route('account.ratings.store', $order), ['ratings' => [0 => 4]])->assertRedirect();
        $this->assertDatabaseCount('product_ratings', 2);
        $this->assertDatabaseHas('product_ratings', [
            'order_id' => $order->id, 'order_item_index' => 0, 'rating' => 4,
        ]);
    }

    public function test_unknown_lines_and_foreign_orders_are_rejected(): void
    {
        $buyer = User::factory()->create();
        $stranger = User::factory()->create();
        $order = $this->order($buyer, [$this->product()], ['status' => 'completed']);

        $this->actingAs($stranger)->post(route('account.ratings.store', $order), ['ratings' => [0 => 5]])->assertNotFound();
        $this->actingAs($buyer)->post(route('account.ratings.store', $order), ['ratings' => [10 => 5]])->assertSessionHasErrors('ratings');
        $this->post(route('account.ratings.store', $order), ['ratings' => [0 => 6]])->assertSessionHasErrors('ratings.0');
        $this->assertDatabaseCount('product_ratings', 0);
    }

    public function test_product_average_and_count_are_available_for_admin_reporting(): void
    {
        $product = $this->product();
        foreach ([1, 5, 3] as $score) {
            $buyer = User::factory()->create();
            $order = $this->order($buyer, [$product], ['status' => 'completed']);
            $this->actingAs($buyer)->post(route('account.ratings.store', $order), ['ratings' => [0 => $score]])->assertRedirect();
        }

        $summary = Product::query()->withCount('ratings')->withAvg('ratings', 'rating')->findOrFail($product->id);
        $this->assertSame(3, $summary->ratings_count);
        $this->assertEqualsWithDelta(3.0, (float) $summary->ratings_avg_rating, 0.001);
        $product->delete();
        $this->assertSame(3, $product->ratings()->count());
    }

    public function test_preview_cannot_post_ratings(): void
    {
        $buyer = User::factory()->create();
        $order = $this->order($buyer, [$this->product()], ['status' => 'completed']);

        $this->actingAs($buyer)->withSession(['customer_preview' => ['user_id' => $buyer->id]])
            ->post(route('account.ratings.store', $order), ['ratings' => [0 => 5]])->assertForbidden();
        $this->assertDatabaseCount('product_ratings', 0);
    }

    private function product(): Product
    {
        $category = Category::query()->first() ?? Category::query()->create([
            'name' => ['fr' => 'Soins'], 'slug' => ['fr' => 'soins'],
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => ['fr' => 'Gel de soin'],
            'slug' => ['fr' => 'soin-'.bin2hex(random_bytes(5))],
            'active' => true,
            'price' => 20,
        ]);
    }

    private function order(User $buyer, array $products, array $overrides = []): Order
    {
        $items = array_map(fn (Product $product): array => [
            'product_id' => $product->id,
            'name' => $product->getTranslation('name', 'fr'),
            'quantity' => 1,
            'line_total' => 20,
        ], $products);

        return Order::query()->create([
            'user_id' => $buyer->id,
            'first_name' => 'Marie',
            'email' => $buyer->email,
            'country' => 'France',
            'city' => 'Paris',
            'address' => '1 rue Exemple',
            'items' => $items,
            'subtotal' => 20 * count($products),
            'delivery_cost' => 0,
            'total_amount' => 20 * count($products),
            'status' => 'pending',
            'payment_status' => 'paid',
            ...$overrides,
        ]);
    }
}
