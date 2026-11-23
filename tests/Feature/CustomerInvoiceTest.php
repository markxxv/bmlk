<?php

namespace Tests\Feature;

use App\Jobs\GenerateOrderInvoice;
use App\Models\Order;
use App\Models\User;
use App\Services\Invoices\InvoiceData;
use App\Services\Invoices\OrderInvoiceService;
use App\Services\Invoices\InvoiceStorageException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake(OrderInvoiceService::DISK);
    }

    public function test_paid_customer_sees_invoice_links_in_account_and_order(): void
    {
        $user = User::factory()->create();
        $order = $this->order($user);

        $this->actingAs($user)->get(route('account.index'))
            ->assertOk()->assertSee(route('account.invoice', $order), false);
        $this->get(route('account.order', $order))
            ->assertOk()->assertSee(__('Télécharger la facture'));
    }

    public function test_invoice_download_issues_and_reuses_private_pdf(): void
    {
        $user = User::factory()->create();
        $order = $this->order($user, [
            'billing_type' => 'business', 'billing_company' => 'Atelier Test SARL',
            'billing_vat' => 'FR12345678901', 'billing_address' => '15 rue Fiscale',
            'billing_zip' => '69001', 'billing_city' => 'Lyon', 'billing_country_code' => 'FR',
            'billing_country' => 'France',
        ]);

        $this->actingAs($user)->get(route('account.invoice', $order))
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $fresh = $order->fresh();
        $this->assertNotNull($fresh->invoice_issued_at);
        $this->assertSame('invoices/invoice-'.$order->order_number.'.pdf', $fresh->invoice_file_path);
        Storage::disk('local')->assertExists($fresh->invoice_file_path);
        $this->assertStringStartsWith('%PDF-', Storage::disk('local')->get($fresh->invoice_file_path));
        $data = InvoiceData::forOrder($fresh);
        $this->assertContains('Atelier Test SARL', $data['address']);
        $this->assertContains('TVA / VAT: FR12345678901', $data['address']);

        $bytes = Storage::disk('local')->get($fresh->invoice_file_path);
        $issuedAt = $fresh->invoice_issued_at;
        GenerateOrderInvoice::dispatchSync($order->id);
        $this->get(route('account.invoice', $order))->assertOk();
        $this->assertSame($bytes, Storage::disk('local')->get($fresh->invoice_file_path));
        $this->assertTrue($issuedAt->equalTo($order->fresh()->invoice_issued_at));
        $this->assertCount(1, Storage::disk('local')->allFiles('invoices'));
    }

    public function test_customer_gets_temporary_pdf_when_storage_is_unwritable(): void
    {
        $user = User::factory()->create();
        $order = $this->order($user);
        $service = \Mockery::mock(OrderInvoiceService::class);
        $service->shouldReceive('issue')->once()
            ->andThrow(new InvoiceStorageException('invoices/test.pdf'));
        $this->app->instance(OrderInvoiceService::class, $service);

        $this->actingAs($user)->get(route('account.invoice', $order))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertNull($order->fresh()->invoice_file_path);
        $this->assertNull($order->fresh()->invoice_issued_at);
    }

    public function test_unpaid_or_other_customers_orders_cannot_be_downloaded(): void
    {
        $owner = User::factory()->create();
        $another = User::factory()->create();
        $paid = $this->order($owner);
        $unpaid = $this->order($owner, ['payment_status' => 'unpaid']);

        $this->get(route('account.invoice', $paid))->assertRedirect();
        $this->actingAs($another)->get(route('account.invoice', $paid))->assertNotFound();
        $this->actingAs($owner)->get(route('account.invoice', $unpaid))->assertNotFound();
        Storage::disk('local')->assertDirectoryEmpty('invoices');
    }

    public function test_customer_preview_can_download_pdf_without_issuing_an_invoice(): void
    {
        $user = User::factory()->create();
        $order = $this->order($user);

        $this->actingAs($user)->withSession(['customer_preview' => ['user_id' => $user->id]])
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee(route('account.invoice', $order), false);
        $this->get(route('account.order', $order))
            ->assertOk()
            ->assertSee(__('Télécharger la facture'));
        $this->get(route('account.invoice', $order))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertNull($order->fresh()->invoice_issued_at);
        $this->assertNull($order->fresh()->invoice_file_path);
        $this->assertSame([], Storage::disk(OrderInvoiceService::DISK)->allFiles('invoices'));
    }

    public function test_preview_can_download_unclaimed_verified_email_order_but_not_another_customer_order(): void
    {
        $user = User::factory()->create();
        $unclaimed = $this->order($user, ['user_id' => null]);
        $another = User::factory()->create();
        $otherOrder = $this->order($another);

        $this->actingAs($user)->withSession(['customer_preview' => ['user_id' => $user->id]]);
        $this->get(route('account.index'))
            ->assertOk()
            ->assertSee(route('account.invoice', $unclaimed), false)
            ->assertDontSee(route('account.invoice', $otherOrder), false);
        $this->get(route('account.invoice', $unclaimed))->assertOk();
        $this->get(route('account.invoice', $otherOrder))->assertNotFound();

        $this->assertNull($unclaimed->fresh()->user_id);
        $this->assertNull($unclaimed->fresh()->invoice_file_path);
    }

    private function order(User $user, array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
            'user_id' => $user->id,
            'first_name' => 'Marie', 'last_name' => 'Dupont',
            'phone' => '0102030405', 'email' => $user->email,
            'country' => 'France', 'shipping_country_code' => 'FR',
            'address' => '10 rue Livraison', 'zip' => '75001', 'city' => 'Paris',
            'items' => [['name' => 'Gel', 'quantity' => 1, 'unit_price' => 10, 'line_total' => 10]],
            'subtotal' => 10, 'delivery_cost' => 0, 'total_amount' => 10,
            'payment_status' => 'paid', 'paid_at' => now(), 'status' => 'completed',
        ], $overrides));
    }
}
