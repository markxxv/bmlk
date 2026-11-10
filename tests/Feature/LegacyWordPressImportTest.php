<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\Legacy\WordPressImporter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyWordPressImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.legacy_test', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'wprw_',
        ]);
        DB::purge('legacy_test');
        $s = Schema::connection('legacy_test');

        $s->create('users', function (Blueprint $t) {
            $t->integer('ID')->primary(); $t->string('user_email'); $t->string('display_name')->nullable(); $t->string('user_registered');
        });
        $s->create('usermeta', function (Blueprint $t) {
            $t->integer('user_id'); $t->string('meta_key'); $t->text('meta_value')->nullable();
        });
        $s->create('wc_orders', function (Blueprint $t) {
            $t->integer('id')->primary(); $t->string('status'); $t->string('type'); $t->decimal('total_amount', 20, 8);
            $t->integer('customer_id'); $t->string('billing_email')->nullable(); $t->string('date_created_gmt');
            $t->string('date_updated_gmt'); $t->integer('parent_order_id')->default(0);
        });
        $s->create('wc_order_addresses', function (Blueprint $t) {
            $t->integer('order_id'); $t->string('address_type'); $t->string('first_name')->nullable(); $t->string('last_name')->nullable();
            $t->string('address_1')->nullable(); $t->string('address_2')->nullable(); $t->string('city')->nullable();
            $t->string('postcode')->nullable(); $t->string('country')->nullable(); $t->string('phone')->nullable();
        });
        $s->create('wc_order_operational_data', function (Blueprint $t) {
            $t->integer('order_id'); $t->string('date_paid_gmt')->nullable(); $t->decimal('shipping_total_amount', 20, 8);
            $t->decimal('shipping_tax_amount', 20, 8); $t->decimal('discount_total_amount', 20, 8); $t->decimal('discount_tax_amount', 20, 8);
        });
        $s->create('woocommerce_order_items', function (Blueprint $t) {
            $t->integer('order_item_id')->primary(); $t->string('order_item_name'); $t->string('order_item_type'); $t->integer('order_id');
        });
        $s->create('woocommerce_order_itemmeta', function (Blueprint $t) {
            $t->integer('order_item_id'); $t->string('meta_key'); $t->text('meta_value')->nullable();
        });

        $L = DB::connection('legacy_test');
        $L->table('users')->insert([
            ['ID' => 914, 'user_email' => 'Maxine@Example.fr', 'display_name' => 'Maxine P', 'user_registered' => '2025-01-01 10:00:00'],
            ['ID' => 915, 'user_email' => 'admin@example.fr', 'display_name' => 'Admin', 'user_registered' => '2025-01-01 10:00:00'],
        ]);
        $L->table('usermeta')->insert([
            ['user_id' => 914, 'meta_key' => 'first_name', 'meta_value' => 'Maxine'],
            ['user_id' => 914, 'meta_key' => 'last_name', 'meta_value' => 'Payet'],
            ['user_id' => 914, 'meta_key' => 'wprw_capabilities', 'meta_value' => 'a:1:{s:8:"customer";b:1;}'],
            ['user_id' => 915, 'meta_key' => 'wprw_capabilities', 'meta_value' => 'a:1:{s:13:"administrator";b:1;}'],
        ]);
        $this->legacyOrder($L, 7489, 'wc-completed', 914, 'maxine@example.fr', 47.75, 5.25, 6.25, 1.25);
        $this->legacyOrder($L, 7490, 'wc-refunded', 0, 'Maxine@example.fr', 12.50, 2.50, 0, 0);
        $this->legacyOrder($L, 7491, 'wc-completed', 915, 'admin@example.fr', 5, 0, 0, 0);
        $this->legacyOrder($L, 7492, 'wc-failed', 914, 'maxine@example.fr', 9, 0, 0, 0);
        $this->legacyOrder($L, 7493, 'wc-completed', 0, '', 5, 0, 0, 0);
    }

    private function legacyOrder($L, int $id, string $status, int $customer, string $email, float $total, float $ship, float $disc, float $discTax): void
    {
        $L->table('wc_orders')->insert([
            'id' => $id, 'status' => $status, 'type' => 'shop_order', 'total_amount' => $total, 'customer_id' => $customer,
            'billing_email' => $email, 'date_created_gmt' => '2026-10-06 07:55:27', 'date_updated_gmt' => '2026-10-08 17:02:59',
        ]);
        $L->table('wc_order_addresses')->insert(['order_id' => $id, 'address_type' => 'billing', 'first_name' => 'Maxine',
            'last_name' => 'PAYET', 'address_1' => '17 domaine', 'city' => 'Mazeres', 'postcode' => '09270', 'country' => 'FR', 'phone' => '0102030405']);
        $L->table('wc_order_operational_data')->insert(['order_id' => $id, 'date_paid_gmt' => '2026-10-06 07:55:30',
            'shipping_total_amount' => $ship, 'shipping_tax_amount' => 0, 'discount_total_amount' => $disc, 'discount_tax_amount' => $discTax]);
        $gross = round($total - $ship + $disc + $discTax, 2);
        $L->table('woocommerce_order_items')->insert(['order_item_id' => $id, 'order_item_name' => 'Rose Milk', 'order_item_type' => 'line_item', 'order_id' => $id]);
        $L->table('woocommerce_order_itemmeta')->insert([
            ['order_item_id' => $id, 'meta_key' => '_qty', 'meta_value' => '1'],
            ['order_item_id' => $id, 'meta_key' => '_line_subtotal', 'meta_value' => (string) round($gross / 1.2, 6)],
            ['order_item_id' => $id, 'meta_key' => '_line_subtotal_tax', 'meta_value' => (string) round($gross - $gross / 1.2, 6)],
        ]);
    }

    private function run_import(bool $dry = false): WordPressImporter
    {
        $importer = new WordPressImporter(DB::connection('legacy_test'), DB::connection(), $dry);
        $importer->importUsers(fn () => null);
        $importer->importOrders(fn () => null);

        return $importer;
    }

    public function test_imports_history_without_passwords_ratings_or_emails(): void
    {
        $importer = $this->run_import();

        $user = User::query()->where('email', 'maxine@example.fr')->firstOrFail();
        $this->assertNull($user->password);
        $this->assertNull($user->email_verified_at);
        $this->assertSame('Maxine Payet', $user->name);
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.fr']);

        // completed + refunded + admin's order; failed and email-less orders are ignored.
        $this->assertSame(3, Order::query()->count());
        $this->assertSame(0, Order::query()->where('ratings_enabled', true)->count());
        $this->assertSame(0, $importer->verifyRatingsDisabled());
        $this->assertSame(0, Order::query()->whereNull('paid_email_sent_at')->count());

        $order = Order::query()->where('order_number', 'BM-20261006-007489')->firstOrFail();
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('completed', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertFalse($order->canBeRated());
        $this->assertEqualsWithDelta(47.75, (float) $order->total_amount, 0.001);
        $this->assertEqualsWithDelta(50.0, (float) $order->subtotal, 0.001);
        $this->assertEqualsWithDelta(7.5, (float) $order->discount_amount, 0.001);

        // Guest order with the same email (different case) is attached to the customer.
        $this->assertSame($user->id, Order::query()->where('order_number', 'BM-20261006-007490')->value('user_id'));
        $this->assertDatabaseHas('order_refunds', ['order_id' => Order::query()->where('order_number', 'BM-20261006-007490')->value('id'), 'amount_cents' => 1250, 'status' => 'succeeded']);
        $this->assertNull(Order::query()->where('order_number', 'BM-20261006-007491')->value('user_id'));
    }

    public function test_dry_run_writes_nothing_and_rerun_is_idempotent(): void
    {
        $this->run_import(dry: true);
        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, User::query()->count());

        $this->run_import();
        $second = $this->run_import();

        $this->assertSame(3, Order::query()->count());
        $this->assertSame(1, User::query()->count());
        $this->assertSame(0, $second->stats['orders_created']);
        $this->assertSame(0, $second->stats['users_created']);
    }
}
