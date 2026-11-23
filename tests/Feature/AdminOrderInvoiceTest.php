<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Models\Order;
use App\Models\User;
use App\Services\Invoices\OrderInvoiceService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOrderInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Storage::fake(OrderInvoiceService::DISK);
    }

    public function test_paid_order_editor_shows_invoice_download_action(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = $this->order();

        Livewire::actingAs($admin)
            ->test(EditOrder::class, ['record' => $order->id])
            ->assertActionVisible('downloadInvoice');
    }

    public function test_unpaid_order_editor_hides_invoice_download_action(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = $this->order('unpaid');

        Livewire::actingAs($admin)
            ->test(EditOrder::class, ['record' => $order->id])
            ->assertActionHidden('downloadInvoice');
    }

    public function test_admin_can_download_same_private_invoice_multiple_times(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = $this->order();
        $url = route('admin.orders.invoice', $order);

        $this->get($url)->assertRedirect(url('/admin/login'));
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->assertNull($order->fresh()->invoice_file_path);

        $this->actingAs($admin)->get($url)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $issued = $order->fresh();
        $this->assertNotNull($issued->invoice_issued_at);
        $this->assertNotNull($issued->invoice_file_path);
        Storage::disk('local')->assertExists($issued->invoice_file_path);
        $bytes = Storage::disk('local')->get($issued->invoice_file_path);
        $this->assertStringStartsWith('%PDF-', $bytes);

        $this->get($url)->assertOk();
        $this->assertSame($bytes, Storage::disk('local')->get($issued->invoice_file_path));
        $this->assertTrue($issued->invoice_issued_at->equalTo($order->fresh()->invoice_issued_at));
    }

    public function test_admin_cannot_issue_unpaid_invoice(): void
    {
        $order = $this->order('unpaid');

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.orders.invoice', $order))
            ->assertNotFound();

        $this->assertNull($order->fresh()->invoice_file_path);
        $this->assertSame([], Storage::disk('local')->allFiles('invoices'));
    }

    private function order(string $paymentStatus = 'paid'): Order
    {
        return Order::query()->create([
            'first_name' => 'Marie', 'last_name' => 'Dupont',
            'phone' => '0102030405', 'email' => 'marie@example.com',
            'country' => 'France', 'shipping_country_code' => 'FR',
            'city' => 'Paris', 'zip' => '75001', 'address' => '10 rue Livraison',
            'items' => [['name' => 'Gel', 'quantity' => 1, 'unit_price' => 10, 'line_total' => 10]],
            'subtotal' => 10, 'delivery_cost' => 0, 'total_amount' => 10,
            'payment_status' => $paymentStatus, 'paid_at' => $paymentStatus === 'paid' ? now() : null,
            'status' => 'pending',
        ]);
    }
}
