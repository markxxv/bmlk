<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Str;
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

    public function test_historical_imported_orders_never_request_ratings_by_default(): void
    {
        $buyer = User::factory()->create();
        $order = $this->order($buyer, [$this->product()], [
            'status' => 'completed',
            'ratings_enabled' => false,
        ]);

        $this->assertFalse($order->fresh()->ratings_enabled);
        $this->actingAs($buyer)->get(route('account.index'))
            ->assertOk()->assertDontSee('Évaluez vos produits');

        $this->get(route('account.order', $order))
            ->assertOk()->assertDontSee('Vos évaluations');

        $this->post(route('account.ratings.store', $order), ['ratings' => [0 => 5]])
            ->assertForbidden();

        $this->assertDatabaseCount('product_ratings', 0);

        // Explicit opt-in is available for a test or selected historical order.
        $order->update(['ratings_enabled' => true]);
        $this->get(route('account.index'))
            ->assertOk()->assertSee('Évaluez vos produits');
    }

    public function test_database_default_excludes_directly_imported_orders(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();

        // Simulate the import path: create a historical order without the
        // new storefront's explicit ratings_enabled opt-in.
        $order = Order::query()->create([
            'user_id' => $buyer->id,
            'first_name' => 'Client historique',
            'phone' => '0000000000',
            'email' => $buyer->email,
            'country' => 'France',
            'city' => 'Paris',
            'address' => 'Adresse historique',
            'items' => [['product_id' => $product->id, 'name' => 'Gel', 'quantity' => 1]],
            'subtotal' => 20,
            'delivery_cost' => 0,
            'total_amount' => 20,
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $this->assertFalse($order->fresh()->ratings_enabled);
        $this->actingAs($buyer)->get(route('account.index'))
            ->assertOk()->assertDontSee('Évaluez vos produits');
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

    public function test_rating_card_uses_current_product_image_not_stale_checkout_snapshot(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();
        $order = $this->order($buyer, [$product], ['status' => 'completed']);

        $items = $order->items;
        $items[0]['image'] = 'https://old.example.com/removed-image.jpg';
        $order->update(['items' => $items]);

        $media = Media::query()->create([
            'model_type' => Product::class,
            'model_id' => $product->id,
            'uuid' => (string) Str::uuid(),
            'collection_name' => 'images',
            'name' => 'current',
            'file_name' => 'current.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => 100,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => ['thumb' => true],
            'responsive_images' => [],
            'order_column' => 1,
        ]);

        $this->actingAs($buyer)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee($media->getUrl('thumb'), false)
            ->assertDontSee('https://old.example.com/removed-image.jpg');

        $this->get(route('account.order', $order))
            ->assertOk()
            ->assertSee($media->getUrl('thumb'), false);
    }

    public function test_rating_markup_has_hover_preview_and_mobile_stacked_layout(): void
    {
        $buyer = User::factory()->create();
        $order = $this->order($buyer, [$this->product()], ['status' => 'completed']);

        $this->actingAs($buyer)->get(route('account.index'))
            ->assertOk()
            ->assertSee('hovered =', false)
            ->assertSee('sm:flex-row', false)
            ->assertSee('size-11', false);
    }

    public function test_customer_preview_displays_pending_ratings_without_save_controls(): void
    {
        $buyer = User::factory()->create();
        $order = $this->order($buyer, [$this->product()], [
            'status' => 'completed',
        ]);

        $this->actingAs($buyer)->withSession([
            'customer_preview' => ['user_id' => $buyer->id],
        ]);

        $this->get(route('account.index'))
            ->assertOk()
            ->assertSee('Évaluez vos produits')
            ->assertSee('Aperçu administrateur')
            ->assertDontSee('Enregistrer mes notes');

        $this->get(route('account.order', $order))
            ->assertOk()
            ->assertSee('Évaluez vos produits')
            ->assertSee('Aperçu administrateur')
            ->assertDontSee('Enregistrer mes notes');

        $this->post(route('account.ratings.store', $order), [
            'ratings' => [0 => 5],
        ])->assertForbidden();

        $this->assertDatabaseCount('product_ratings', 0);
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
            'phone' => '0102030405',
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
            'ratings_enabled' => true,
            ...$overrides,
        ]);
    }
}
