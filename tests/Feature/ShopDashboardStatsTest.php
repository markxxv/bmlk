<?php

namespace Tests\Feature;

use App\Filament\Widgets\ShopOverviewStats;
use App\Models\Order;
use App\Models\RoleApplication;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class ShopDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);
    }

    public function test_dashboard_discovers_shop_overview_widget(): void
    {
        $this->assertContains(ShopOverviewStats::class, Filament::getPanel('admin')->getWidgets());
    }

    public function test_counts_only_pending_applications_and_paid_pending_orders_and_charts_paid_sales_in_paris_time(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-05 12:00:00', 'Europe/Paris'));

        $user = User::factory()->create();

        foreach (['pending', 'pending', 'approved', 'rejected'] as $status) {
            RoleApplication::query()->create([
                'user_id' => $user->id,
                'role' => 'influencer',
                'status' => $status,
            ]);
        }

        // In Paris this was November 1, even though the UTC date is Oct 31.
        $this->order('pending', 'paid', '2026-10-31 23:30:00');
        $this->order('shipped', 'paid', '2026-11-03 08:00:00');
        $this->order('completed', 'paid', '2026-11-03 19:00:00');

        // An old paid order needs preparation but is not a sale this month.
        $this->order('pending', 'paid', '2026-10-29 10:00:00');

        // Unpaid, failed, and paid orders without a payment timestamp
        // never inflate the sales metric.
        $this->order('pending', 'unpaid', null);
        $this->order('pending', 'failed', null);
        $this->order('shipped', 'paid', null);

        $stats = (new ReflectionMethod(ShopOverviewStats::class, 'getStats'))
            ->invoke(app(ShopOverviewStats::class));

        $this->assertCount(3, $stats);
        $this->assertSame(2, $stats[0]->getValue());
        $this->assertSame(2, $stats[1]->getValue());
        $this->assertSame(3, $stats[2]->getValue());
        $this->assertSame([1, 0, 2, 0, 0], $stats[2]->getChart());
    }

    public function test_empty_month_uses_a_zero_filled_chart(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-12-04 14:00:00', 'Europe/Paris'));

        $stats = (new ReflectionMethod(ShopOverviewStats::class, 'getStats'))
            ->invoke(app(ShopOverviewStats::class));

        $this->assertSame(0, $stats[2]->getValue());
        $this->assertSame([0, 0, 0, 0], $stats[2]->getChart());
    }

    private function order(string $status, string $paymentStatus, ?string $paidAt): Order
    {
        return Order::query()->create([
            'first_name' => 'Marie',
            'phone' => '0102030405',
            'country' => 'France',
            'city' => 'Paris',
            'address' => 'Rue du Test 1',
            'items' => [['name' => 'Gel', 'quantity' => 1]],
            'subtotal' => 10,
            'delivery_cost' => 0,
            'total_amount' => 10,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'paid_at' => $paidAt,
        ]);
    }
}
