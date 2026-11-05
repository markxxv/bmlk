<?php

namespace Tests\Feature;

use App\Filament\Widgets\OrdersWidget as FilamentOrdersWidget;
use App\Livewire\Admin\OrdersWidget;
use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardOrdersWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_dashboard_discovers_maison_plush_style_orders_widget(): void
    {
        $this->assertContains(FilamentOrdersWidget::class, Filament::getPanel('admin')->getWidgets());
    }

    public function test_non_admin_cannot_mount_order_list(): void
    {
        $customer = User::factory()->create();

        Livewire::actingAs($customer)
            ->test(OrdersWidget::class)
            ->assertForbidden();
    }

    public function test_payment_filters_show_correct_counts_and_do_not_confuse_fulfillment_status(): void
    {
        $this->actingAs($this->admin());

        $unpaid = $this->order('unpaid', 'pending');
        $paidPending = $this->order('paid', 'pending');
        $paidProcessing = $this->order('paid', 'processing');
        $processingPayment = $this->order('processing', 'pending');
        $failed = $this->order('failed', 'pending');
        $cancelled = $this->order('paid', 'cancelled');

        $widget = Livewire::test(OrdersWidget::class);

        $widget
            ->assertSee('All orders (6)')
            ->assertSee('Payment pending (1)')
            ->assertSee('Paid (3)')
            ->assertSee('Payment processing (1)')
            ->assertSee('Payment failed (1)')
            ->assertSee('To prepare (1)')
            ->assertSee('Preparing (1)')
            ->assertSee('Cancelled (1)');

        $widget->call('filterByStatus', 'unpaid')
            ->assertSee($unpaid->order_number)
            ->assertDontSee($paidPending->order_number);

        $widget->call('filterByStatus', 'paid')
            ->assertSee($paidPending->order_number)
            ->assertSee($paidProcessing->order_number)
            ->assertSee($cancelled->order_number)
            ->assertDontSee($unpaid->order_number)
            ->assertDontSee($processingPayment->order_number);

        $widget->call('filterByStatus', 'to_prepare')
            ->assertSee($paidPending->order_number)
            ->assertDontSee($unpaid->order_number)
            ->assertDontSee($paidProcessing->order_number);

        $widget->call('filterByStatus', 'payment_processing')
            ->assertSee($processingPayment->order_number)
            ->assertDontSee($paidProcessing->order_number);

        $widget->call('filterByStatus', 'payment_failed')
            ->assertSee($failed->order_number)
            ->assertDontSee($paidPending->order_number);

        $widget->call('filterByStatus', 'cancelled')
            ->assertSee($cancelled->order_number)
            ->assertDontSee($paidProcessing->order_number);

        $widget->call('filterByStatus', 'invalid_filter')->assertSet('statusFilter', 'cancelled');
    }

    public function test_fulfillment_badges_have_semantic_colors_and_archived_can_be_filtered(): void
    {
        $this->actingAs($this->admin());

        $archived = $this->order('paid', 'archived');
        $active = $this->order('paid', 'processing');
        $cancelled = $this->order('paid', 'cancelled');

        $widget = Livewire::test(OrdersWidget::class)
            ->assertSee('Archived (1)')
            ->assertSee('Preparing (1)')
            ->assertSee('Cancelled (1)');

        $component = $widget->instance();

        foreach ([
            'pending' => 'bg-amber-100',
            'processing' => 'bg-blue-100',
            'shipped' => 'bg-sky-100',
            'completed' => 'bg-green-100',
            'cancelled' => 'bg-red-100',
            'archived' => 'bg-gray-100',
        ] as $status => $class) {
            $this->assertStringContainsString($class, $component->fulfillmentBadgeClass($status));
        }

        $widget->call('filterByStatus', 'archived')
            ->assertSee($archived->order_number)
            ->assertDontSee($active->order_number)
            ->assertDontSee($cancelled->order_number)
            ->assertSee('bg-gray-100', escape: false);
    }

    private function order(string $paymentStatus, string $status): Order
    {
        return Order::query()->create([
            'first_name' => 'Marie',
            'last_name' => 'Martin',
            'phone' => '0102030405',
            'country' => 'France',
            'city' => 'Paris',
            'address' => '10 Rue des Fleurs',
            'items' => [['name' => 'Gel', 'quantity' => 1]],
            'subtotal' => 20,
            'delivery_cost' => 5,
            'total_amount' => 25,
            'status' => $status,
            'payment_status' => $paymentStatus,
        ]);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }
}
