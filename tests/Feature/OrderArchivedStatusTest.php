<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderArchivedStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_archive_order_without_changing_payment_status(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $order = Order::query()->create([
            'first_name' => 'Marie',
            'phone' => '0102030405',
            'country' => 'France',
            'city' => 'Paris',
            'address' => 'Rue de Test 1',
            'items' => [['name' => 'Gel', 'quantity' => 1]],
            'subtotal' => 20,
            'delivery_cost' => 5,
            'total_amount' => 25,
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        Livewire::actingAs($admin)
            ->test(EditOrder::class, ['record' => $order->id])
            ->set('data.status', 'archived')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('archived', $order->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('Archivée', $order->fresh()->customerStatusLabel());
    }
}
