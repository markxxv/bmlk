<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WarehousePwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_route_is_not_shadowed_by_a_public_directory(): void
    {
        $this->assertDirectoryDoesNotExist(public_path('warehouse'));

        $this->get('/warehouse')->assertRedirect(route('warehouse.login'));
        $this->get('/warehouse/sw.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
    }


    public function test_warehouse_login_uses_navbar_branding_and_existing_logo_for_pwa_icons(): void
    {
        $this->get(route('warehouse.login'))
            ->assertOk()
            ->assertSee('src="/img/logo.png"', false)
            ->assertSee('BLACK MILK')
            ->assertSee('<html lang="en">', false)
            ->assertSee('Sign in')
            ->assertSee("The professionals' choice")
            ->assertDontSee('Preparation area')
            ->assertDontSee('Staff access only.')
            ->assertDontSee('Stay signed in on this device for 14 days.');

        $manifest = json_decode(file_get_contents(public_path('pwa-warehouse/manifest.webmanifest')), true);

        $this->assertSame('/img/logo.png', $manifest['icons'][0]['src']);
    }

    public function test_warehouse_staff_pages_use_english_copy(): void
    {
        $this->get(route('warehouse.login'))
            ->assertOk()
            ->assertSee('Sign in')
            ->assertSee('Password')
            ->assertDontSee('Mot de passe');

        $this->actingAs($this->staff());
        $order = $this->order();

        $this->get(route('warehouse.orders.index'))
            ->assertOk()
            ->assertSee('All orders')
            ->assertSee('To prepare')
            ->assertDontSee('Commandes payées');

        $this->get(route('warehouse.orders.show', $order->order_number))
            ->assertOk()
            ->assertSee('Pending preparation')
            ->assertSee('Shipping label')
            ->assertDontSee('Étiquette de livraison');
    }

    public function test_guests_are_redirected_to_the_warehouse_login(): void
    {
        $order = $this->order();

        $this->get(route('warehouse.orders.index'))
            ->assertRedirect(route('warehouse.login'));

        $this->get(route('warehouse.orders.show', $order->order_number))
            ->assertRedirect(route('warehouse.login'));
    }

    public function test_customer_cannot_view_orders_or_create_labels(): void
    {
        $order = $this->order();
        $this->actingAs(User::factory()->create());

        $this->get(route('warehouse.orders.index'))->assertForbidden();
        $this->get(route('warehouse.orders.show', $order->order_number))->assertForbidden();
        $this->post(route('warehouse.orders.create-label', $order->order_number))->assertForbidden();
        $this->post(route('warehouse.orders.status', $order->order_number), [
            'status' => 'processing',
        ])->assertForbidden();
    }

    public function test_staff_can_login_and_return_to_the_order_from_telegram(): void
    {
        $order = $this->order();
        $staff = $this->staff();

        $this->get(route('warehouse.orders.show', $order->order_number))
            ->assertRedirect(route('warehouse.login'));

        $this->post(route('warehouse.login.submit'), [
            'email' => $staff->email,
            'password' => 'warehouse-password-123',
        ])->assertRedirect(route('warehouse.orders.show', $order->order_number));

        $response = $this->get(route('warehouse.orders.show', $order->order_number));
        $response->assertOk()->assertSee($order->order_number)->assertSee('Gel');

        $cookies = $response->headers->getCookies();
        $warehouseCookie = collect($cookies)->first(fn ($cookie) => $cookie->getName() === 'bmlk_warehouse_session');

        $this->assertNotNull($warehouseCookie);
        $this->assertSame('/warehouse', $warehouseCookie->getPath());
        $this->assertGreaterThanOrEqual(now()->addDays(13)->getTimestamp(), $warehouseCookie->getExpiresTime());
    }

    public function test_password_for_non_warehouse_customer_is_rejected(): void
    {
        $customer = User::factory()->create(['password' => 'warehouse-password-123']);

        $this->post(route('warehouse.login.submit'), [
            'email' => $customer->email,
            'password' => 'warehouse-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_warehouse_lists_all_orders_and_filters_payment_and_fulfillment(): void
    {
        $this->actingAs($this->staff());

        $pending = $this->order();
        $processing = $this->order(['status' => 'processing']);
        $shipped = $this->order(['status' => 'shipped']);
        $unpaid = $this->order(['payment_status' => 'unpaid']);
        $failed = $this->order(['payment_status' => 'failed']);

        $this->get(route('warehouse.orders.index'))
            ->assertOk()
            ->assertSee($pending->order_number)
            ->assertSee($processing->order_number)
            ->assertSee($shipped->order_number)
            ->assertSee($unpaid->order_number)
            ->assertSee($failed->order_number)
            ->assertSee('All orders')
            ->assertSee('Payment pending')
            ->assertSee('Payment failed')
            ->assertSee('1 to prepare');

        $this->get(route('warehouse.orders.index', ['state' => 'pending']))
            ->assertOk()
            ->assertSee($pending->order_number)
            ->assertDontSee($processing->order_number)
            ->assertDontSee($shipped->order_number)
            ->assertDontSee($unpaid->order_number);

        $this->get(route('warehouse.orders.index', ['state' => 'shipped']))
            ->assertOk()
            ->assertSee($shipped->order_number)
            ->assertDontSee($pending->order_number);

        $this->get(route('warehouse.orders.index', ['state' => 'payment_pending']))
            ->assertOk()
            ->assertSee($unpaid->order_number)
            ->assertDontSee($shipped->order_number);

        $this->get(route('warehouse.orders.index', ['state' => 'payment_failed']))
            ->assertOk()
            ->assertSee($failed->order_number)
            ->assertDontSee($pending->order_number);
    }

    public function test_order_sort_toggle_orders_records_and_preserves_filters(): void
    {
        $this->actingAs($this->staff());

        $older = $this->order(['city' => 'Longwy']);
        $older->forceFill(['created_at' => now()->subDays(2)])->saveQuietly();
        $newer = $this->order(['city' => 'Longwy']);

        $this->get(route('warehouse.orders.index', ['state' => 'pending', 'q' => 'Longwy']))
            ->assertOk()
            ->assertSeeInOrder([$newer->order_number, $older->order_number])
            ->assertSee('Newest first');

        $this->get(route('warehouse.orders.index', ['state' => 'pending', 'q' => 'Longwy', 'sort' => 'oldest']))
            ->assertOk()
            ->assertSeeInOrder([$older->order_number, $newer->order_number])
            ->assertSee('Oldest first')
            ->assertDontSee('Plus anciennes d’abord')
            ->assertSee('q=Longwy', false);
    }

    public function test_tracking_number_can_be_copied_and_tracking_page_opens_at_bottom(): void
    {
        $this->actingAs($this->staff());
        $order = $this->order([
            'status' => 'shipped',
            'tracking_number' => 'TRK-123-456',
            'tracking_url' => 'https://tracking.example.com/TRK-123-456',
        ]);

        $this->get(route('warehouse.orders.show', $order->order_number))
            ->assertOk()
            ->assertSeeInOrder(['Tracking number', 'TRK-123-456', 'Items'])
            ->assertSee('id="warehouse-tracking-copy"', false)
            ->assertSee('id="warehouse-tracking-check-icon"', false)
            ->assertSee('Copied')
            ->assertSee('href="https://tracking.example.com/TRK-123-456"', false)
            ->assertSee('Track shipment');
    }

    public function test_tracking_controls_hide_when_missing_and_unsafe_links_are_rejected(): void
    {
        $this->actingAs($this->staff());

        $withoutTracking = $this->order();
        $this->get(route('warehouse.orders.show', $withoutTracking->order_number))
            ->assertOk()
            ->assertDontSee('id="warehouse-tracking-copy"', false)
            ->assertDontSee('Track shipment');

        $unsafe = $this->order([
            'tracking_number' => 'TRK-999',
            'tracking_url' => 'javascript:alert(1)',
        ]);
        $this->get(route('warehouse.orders.show', $unsafe->order_number))
            ->assertOk()
            ->assertSee('TRK-999')
            ->assertDontSee('Track shipment')
            ->assertDontSee('javascript:alert(1)');
    }

    public function test_staff_can_start_preparing_and_cannot_ship_without_a_label(): void
    {
        $this->actingAs($this->staff());
        $order = $this->order();

        $this->post(route('warehouse.orders.status', $order->order_number), [
            'status' => 'shipped',
        ])->assertSessionHasErrors('status');
        $this->assertSame('pending', $order->fresh()->status);

        $this->post(route('warehouse.orders.status', $order->order_number), [
            'status' => 'processing',
        ])->assertSessionHas('success');

        $this->assertSame('processing', $order->fresh()->status);
        $this->post(route('warehouse.orders.status', $order->order_number), [
            'status' => 'pending',
        ])->assertSessionHas('success');
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_sendcloud_label_is_created_once_and_pdf_requires_staff_login(): void
    {
        $this->actingAs($this->staff());
        $order = $this->order([
            'shipping_country_code' => 'FR',
            'shipping_option_code' => 'chrono_shop2shop',
            'shipping_contract_id' => 10,
            'sendcloud_order_id' => 'sc-100',
            'shipping_parcel' => ['weight' => ['value' => '0.500', 'unit' => 'kg']],
        ]);

        Storage::fake('local');
        Cache::put('sendcloud.integration_id', 55);
        Http::fake([
            'https://panel.sendcloud.sc/api/v3/orders/create-label-sync' => Http::response([
                'data' => [[
                    'tracking_number' => 'TRK-WAREHOUSE',
                    'label' => ['file' => base64_encode("%PDF-1.4\nWarehouse test\n")],
                    'parcel_id' => 'sc-parcel-100',
                    'shipment_id' => 'sc-shipment-100',
                ]],
            ]),
        ]);

        $this->post(route('warehouse.orders.create-label', $order->order_number))
            ->assertRedirect(route('warehouse.orders.show', $order->order_number));

        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertSame('TRK-WAREHOUSE', $order->fresh()->tracking_number);
        $this->assertTrue(Storage::disk('local')->exists('sendcloud-labels/'.$order->id.'.pdf'));

        $this->get(route('warehouse.orders.label', $order->order_number))->assertOk();

        $this->post(route('warehouse.orders.create-label', $order->order_number))->assertStatus(409);
        Http::assertSentCount(1);
    }

    public function test_unpaid_order_is_visible_but_cannot_be_fulfilled_or_refunded(): void
    {
        $this->actingAs($this->staff());
        $order = $this->order(['payment_status' => 'unpaid']);

        $this->get(route('warehouse.orders.show', $order->order_number))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Payment pending')
            ->assertSee('read-only until payment succeeds')
            ->assertDontSee('Start preparing')
            ->assertDontSee('Issue a refund');

        $this->post(route('warehouse.orders.status', $order->order_number), [
            'status' => 'processing',
        ])->assertNotFound();

        $this->post(route('warehouse.orders.create-label', $order->order_number))->assertNotFound();
    }

    private function staff(): User
    {
        $user = User::factory()->create(['password' => 'warehouse-password-123']);
        $user->forceFill(['is_warehouse' => true])->save();

        return $user;
    }

    private function order(array $overrides = []): Order
    {
        return Order::query()->create([
            'first_name' => 'Marie',
            'phone' => '0102030405',
            'country' => 'France',
            'city' => 'Paris',
            'address' => 'Rue de Test 1',
            'items' => [
                ['name' => 'Gel', 'quantity' => 2, 'unit_price' => 10, 'line_total' => 20],
            ],
            'subtotal' => 20,
            'delivery_cost' => 5,
            'total_amount' => 25,
            'status' => 'pending',
            'payment_status' => 'paid',
            ...$overrides,
        ]);
    }
}
