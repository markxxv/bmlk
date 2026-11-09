<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_preview_customer_orders_and_return_to_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create(['name' => 'Preview Customer']);
        $order = $this->order($customer);

        $this->actingAs($admin)
            ->post(route('customer-preview.start', $customer))
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('customer_preview.admin_id', $admin->id)
            ->assertSessionHas('customer_preview.user_id', $customer->id);

        $this->assertAuthenticatedAs($customer);

        $this->get(route('account.index'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Preview Customer')
            ->assertSee('Read-only preview')
            ->assertSee('Return to admin');

        $this->get(route('account.order', $order))
            ->assertOk()
            ->assertSee($order->order_number);

        $this->post(route('customer-preview.stop'))
            ->assertRedirect(route('filament.admin.resources.customers.index'))
            ->assertSessionMissing('customer_preview');

        $this->assertAuthenticatedAs($admin);
        $this->get(route('account.index'))->assertOk()->assertDontSee('Read-only preview');
    }

    public function test_customer_preview_is_read_only_and_prevents_payment_and_admin_access(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $order = $this->order($customer);

        $this->actingAs($admin)
            ->post(route('customer-preview.start', $customer))
            ->assertRedirect(route('account.index'));

        $this->post(route('account.password'), [
            'current_password' => 'password',
            'password' => 'new-password-12345',
            'password_confirmation' => 'new-password-12345',
        ])->assertForbidden();

        $this->post(route('order.pay', ['orderNumber' => $order->order_number]))
            ->assertForbidden();

        $this->post(route('account.logout'))->assertForbidden();

        $this->post(route('customer-preview.start', User::factory()->create()))->assertForbidden();

        $this->get('/admin')->assertRedirect(route('account.index'));
        $this->get(route('account.checkout', ['orderNumber' => $order->order_number]))
            ->assertRedirect(route('account.index'));

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertAuthenticatedAs($customer);
    }

    public function test_preview_does_not_claim_unowned_orders_from_customer_email(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $orphan = $this->order($customer, ['user_id' => null]);

        $this->actingAs($admin)
            ->post(route('customer-preview.start', $customer))
            ->assertRedirect(route('account.index'));

        $this->get(route('account.index'))
            ->assertOk()
            ->assertSee($orphan->order_number);

        $this->get(route('account.order', $orphan))
            ->assertOk()
            ->assertSee($orphan->order_number);

        $this->assertNull($orphan->fresh()->user_id);
    }

    public function test_preview_does_not_expose_other_people_or_unverified_email_orders(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->unverified()->create();
        $another = User::factory()->create();
        $unclaimed = $this->order($customer, ['user_id' => null]);
        $other = $this->order($another);

        $this->actingAs($admin)
            ->post(route('customer-preview.start', $customer))
            ->assertRedirect(route('account.index'));

        $this->get(route('account.index'))
            ->assertOk()
            ->assertDontSee($unclaimed->order_number)
            ->assertDontSee($other->order_number);

        $this->get(route('account.order', $unclaimed))->assertNotFound();
        $this->get(route('account.order', $other))->assertNotFound();
    }

    public function test_non_admin_cannot_start_preview_or_restore_an_admin(): void
    {
        $customer = User::factory()->create();
        $another = User::factory()->create();

        $this->actingAs($customer)
            ->post(route('customer-preview.start', $another))
            ->assertForbidden();

        $this->post(route('customer-preview.stop'))->assertForbidden();
        $this->assertAuthenticatedAs($customer);
    }

    public function test_admin_cannot_preview_other_admins_or_warehouse_staff(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $otherAdmin = User::factory()->create(['is_admin' => true]);
        $warehouse = User::factory()->create(['is_warehouse' => true]);

        $this->actingAs($admin)
            ->post(route('customer-preview.start', $otherAdmin))
            ->assertForbidden();

        $this->post(route('customer-preview.start', $warehouse))->assertForbidden();
        $this->post(route('customer-preview.start', $admin))->assertForbidden();

        $this->assertAuthenticatedAs($admin);
    }

    private function order(User $customer, array $overrides = []): Order
    {
        return Order::query()->create([
            'user_id' => $customer->id,
            'first_name' => 'Preview',
            'email' => $customer->email,
            'country' => 'France',
            'city' => 'Paris',
            'address' => 'Rue de Test 1',
            'items' => [['name' => 'Gel', 'quantity' => 1, 'unit_price' => 15, 'line_total' => 15]],
            'subtotal' => 15,
            'delivery_cost' => 5,
            'total_amount' => 20,
            'status' => 'shipped',
            'payment_status' => 'paid',
            ...$overrides,
        ]);
    }
}
