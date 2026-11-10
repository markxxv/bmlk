<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\SendcloudTrackingService;
use App\Support\ShipmentTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendcloudTrackingHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config()->set('services.sendcloud.public_key', 'test-public');
        config()->set('services.sendcloud.secret_key', 'test-secret');
    }

    public function test_v3_tracking_history_is_saved_without_duplicate_events_on_repeated_import(): void
    {
        $order = $this->order('SC-TRACK-123');
        Http::fake([
            'https://panel.sendcloud.sc/api/v3/parcels/tracking/SC-TRACK-123' =>
                Http::response($this->trackingPayload('SC-TRACK-123')),
        ]);

        $service = app(SendcloudTrackingService::class);
        $this->assertSame(4, $service->sync($order));
        $this->assertSame(4, $order->trackingEvents()->count());

        $this->assertSame(4, $service->sync($order));
        $this->assertSame(4, $order->trackingEvents()->count());

        $this->assertSame('in_transit', $order->fresh()->sendcloud_tracking_phase);
        $this->assertNotNull($order->fresh()->sendcloud_tracking_synced_at);
        $this->assertSame('https://tracking.example.com/SC-TRACK-123', $order->fresh()->tracking_url);
        $this->assertSame('shipped', $order->fresh()->status);

        $events = $order->trackingEvents()->get()->pluck('phase');
        $this->assertContains('label_created', $events);
        $this->assertContains('accepted', $events);
        $this->assertContains('in_transit', $events);

        $stages = ShipmentTimeline::steps($order->fresh()->load('trackingEvents'));
        $this->assertSame('done', $stages[0]['state']);
        $this->assertSame('done', $stages[1]['state']);
        $this->assertSame('current', $stages[2]['state']);
        $this->assertSame('future', $stages[3]['state']);
        $this->assertSame('future', $stages[4]['state']);
    }

    public function test_wrapped_sendcloud_tracking_payload_is_imported(): void
    {
        $order = $this->order('SC-WRAPPED-123');

        Http::fake([
            'https://panel.sendcloud.sc/api/v3/parcels/tracking/*' =>
                Http::response(['data' => $this->trackingPayload('SC-WRAPPED-123')]),
        ]);

        $this->assertSame(4, app(SendcloudTrackingService::class)->sync($order));
        $this->assertSame(4, $order->trackingEvents()->count());
        $this->assertSame('in_transit', $order->fresh()->sendcloud_tracking_phase);
    }

    public function test_announced_parcel_without_carrier_events_imports_label_only(): void
    {
        $order = $this->order('SC-LABEL-ONLY');

        Http::fake([
            'https://panel.sendcloud.sc/api/v3/parcels/tracking/*' => Http::response([
                'announced_at' => '2026-11-08T10:00:00Z',
                'tracking_numbers' => [['tracking_number' => 'SC-LABEL-ONLY']],
                'events' => null,
            ]),
        ]);

        $this->assertSame(1, app(SendcloudTrackingService::class)->sync($order));
        $this->assertSame('label_created', $order->trackingEvents()->sole()->phase);
        $this->assertNotNull($order->fresh()->sendcloud_tracking_synced_at);

        // The second attempt updates the existing event, without duplicating it.
        $this->assertSame(1, app(SendcloudTrackingService::class)->sync($order));
        $this->assertSame(1, $order->trackingEvents()->count());
    }

    public function test_empty_tracking_history_does_not_create_fake_carrier_events(): void
    {
        $order = $this->order('SC-NO-SCANS');

        Http::fake([
            'https://panel.sendcloud.sc/api/v3/parcels/tracking/*' => Http::response([
                'tracking_numbers' => [['tracking_number' => 'SC-NO-SCANS']],
                'events' => [],
            ]),
        ]);

        $this->assertSame(0, app(SendcloudTrackingService::class)->sync($order));
        $this->assertDatabaseCount('order_tracking_events', 0);
        $this->assertNotNull($order->fresh()->sendcloud_tracking_synced_at);
    }

    public function test_unsupported_json_reports_field_names_without_exposing_values(): void
    {
        $order = $this->order('SC-BAD-SHAPE');

        Http::fake([
            'https://panel.sendcloud.sc/api/v3/parcels/tracking/*' => Http::response([
                'message' => 'Sensitive customer information',
                'foo' => ['address' => '1 Private Road'],
            ]),
        ]);

        try {
            app(SendcloudTrackingService::class)->sync($order);
            $this->fail('Unexpected tracking response should be rejected.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('keys: message, foo', $e->getMessage());
            $this->assertStringNotContainsString('Sensitive customer information', $e->getMessage());
            $this->assertStringNotContainsString('1 Private Road', $e->getMessage());
        }

        $this->assertDatabaseCount('order_tracking_events', 0);
        $this->assertNull($order->fresh()->sendcloud_tracking_synced_at);
    }

    public function test_delivered_updates_order_but_does_not_regress_archived_orders(): void
    {
        $order = $this->order('SC-DEL-123');
        $archive = $this->order('SC-ARCH-123', ['status' => 'archived']);

        Http::fake([
            'https://panel.sendcloud.sc/api/v3/parcels/tracking/*' => Http::response([
                'announced_at' => '2026-11-05T08:00:00Z',
                'events' => [[
                    'event_at' => '2026-11-07T15:20:00Z',
                    'event_type' => 'carrier',
                    'phase' => 'delivered',
                    'exception' => 'none',
                    'status_type' => 'success',
                    'description' => 'Delivered to customer.',
                ]],
                'tracking_numbers' => [],
            ]),
        ]);

        $service = app(SendcloudTrackingService::class);
        $service->sync($order);
        $service->sync($archive);

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('archived', $archive->fresh()->status);
        $this->assertSame('delivered', $order->fresh()->sendcloud_tracking_phase);
        $this->assertSame('2026-11-07 15:20:00', $order->fresh()->delivered_at->utc()->format('Y-m-d H:i:s'));
        $service->sync($order);
        $this->assertSame('2026-11-07 15:20:00', $order->fresh()->delivered_at->utc()->format('Y-m-d H:i:s'));
    }

    public function test_customer_can_expand_real_events_but_no_tracking_history_is_invented(): void
    {
        $customer = User::factory()->create();
        $order = $this->order('SC-TRACK-123', ['user_id' => $customer->id]);

        $this->actingAs($customer);
        $this->get(route('account.index'))
            ->assertOk()
            ->assertSee('Numéro de suivi')
            ->assertSee('SC-TRACK-123')
            ->assertSee('Suivi de livraison')
            ->assertSee('Les mises à jour du transporteur apparaîtront ici')
            ->assertSee('data-creator-faq-panel', false);

        Http::fake([
            'https://panel.sendcloud.sc/api/v3/parcels/tracking/*' =>
                Http::response($this->trackingPayload('SC-TRACK-123')),
        ]);
        app(SendcloudTrackingService::class)->sync($order);

        $this->get(route('account.index'))
            ->assertOk()
            ->assertSee('Pris en charge')
            ->assertSee('En transit')
            ->assertSee('Colis traité au centre de tri')
            ->assertSee('Suivre mon colis');

        $this->get(route('account.order', $order))
            ->assertOk()
            ->assertSee('SC-TRACK-123')
            ->assertSee('Mises à jour du transporteur')
            ->assertSee('En transit');
    }

    public function test_cli_backfills_delivered_orders_with_progress_and_is_repeatable(): void
    {
        $order = $this->order('SC-OLD-123', ['status' => 'completed']);
        Http::fake([
            'https://panel.sendcloud.sc/api/v3/parcels/tracking/*' =>
                Http::response($this->trackingPayload('SC-OLD-123')),
        ]);

        $this->artisan('shipping:sync-tracking', ['--all' => true, '--delay' => 0])
            ->assertExitCode(0);

        $this->assertSame(4, $order->trackingEvents()->count());

        $this->artisan('shipping:sync-tracking', ['--all' => true, '--delay' => 0])
            ->assertExitCode(0);

        $this->assertSame(4, $order->trackingEvents()->count());
    }

    public function test_invalid_tracking_api_responses_do_not_erase_existing_data(): void
    {
        $order = $this->order('SC-FAILED-123', [
            'tracking_url' => 'https://tracking.example.com/original',
        ]);

        Http::fake([
            'https://panel.sendcloud.sc/api/v3/parcels/tracking/*' =>
                Http::response(['detail' => 'Parcel does not exist'], 404),
        ]);

        $this->expectException(\RuntimeException::class);
        try {
            app(SendcloudTrackingService::class)->sync($order);
        } finally {
            $this->assertSame('https://tracking.example.com/original', $order->fresh()->tracking_url);
            $this->assertDatabaseCount('order_tracking_events', 0);
        }
    }

    private function trackingPayload(string $number): array
    {
        return [
            'announced_at' => '2026-11-05T08:00:00Z',
            'details' => ['expected_delivery_date' => '2026-11-10'],
            'events' => [
                [
                    'event_at' => '2026-11-06T10:30:00Z',
                    'event_type' => 'carrier',
                    'phase' => 'accepted',
                    'exception' => 'none',
                    'sub_status' => 'none',
                    'status_type' => 'success',
                    'description' => 'Parcel accepted by carrier',
                ],
                [
                    'event_at' => '2026-11-06T14:30:00Z',
                    'event_type' => 'carrier',
                    'phase' => 'accepted',
                    'exception' => 'none',
                    'sub_status' => 'none',
                    'status_type' => 'success',
                    'description' => 'Colis accepté',
                ],
                [
                    'event_at' => '2026-11-07T08:15:00Z',
                    'event_type' => 'carrier',
                    'phase' => 'in_transit',
                    'exception' => 'none',
                    'sub_status' => 'none',
                    'status_type' => 'success',
                    'description' => 'Colis traité au centre de tri',
                ],
            ],
            'tracking_numbers' => [
                ['tracking_number' => $number, 'tracking_url' => 'https://tracking.example.com/'.$number],
            ],
        ];
    }

    private function order(string $tracking, array $overrides = []): Order
    {
        return Order::query()->create([
            'first_name' => 'Marie',
            'email' => 'marie@example.com',
            'country' => 'France',
            'city' => 'Paris',
            'address' => 'Rue de Test',
            'items' => [['name' => 'Gel', 'quantity' => 1]],
            'subtotal' => 20,
            'delivery_cost' => 5,
            'total_amount' => 25,
            'payment_status' => 'paid',
            'status' => 'shipped',
            'tracking_number' => $tracking,
            ...$overrides,
        ]);
    }
}
