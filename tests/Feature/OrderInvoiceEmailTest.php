<?php

namespace Tests\Feature;

use App\Jobs\SendOrderInvoiceEmail;
use App\Mail\OrderInvoiceMail;
use App\Models\Order;
use App\Services\Invoices\OrderInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderInvoiceEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(OrderInvoiceService::DISK);
        Mail::fake();
    }

    public function test_scheduler_only_queues_new_paid_shipped_or_completed_orders(): void
    {
        Queue::fake();

        $old = $this->order(['invoice_email_enabled' => false]);
        $new = $this->order(['status' => 'processing']);
        $completed = $this->order(['status' => 'completed']);
        $unpaid = $this->order(['payment_status' => 'unpaid']);
        $sent = $this->order(['invoice_email_sent_at' => now()]);
        $recentAttempt = $this->order(['invoice_email_attempted_at' => now()]);
        $cancelled = $this->order(['status' => 'cancelled']);
        $noEmail = $this->order(['email' => null]);

        // Warehouse uses a query update, not an Eloquent model event.
        Order::query()->whereKey($new->id)->update(['status' => 'shipped']);

        $this->artisan('orders:queue-invoice-emails')->assertSuccessful();

        Queue::assertPushed(SendOrderInvoiceEmail::class, 2);
        Queue::assertPushed(SendOrderInvoiceEmail::class, fn ($job) => $job->orderId === $new->id);
        Queue::assertPushed(SendOrderInvoiceEmail::class, fn ($job) => $job->orderId === $completed->id);
        Mail::assertNothingSent();
    }

    public function test_job_sends_saved_pdf_once_to_the_order_email_in_order_language(): void
    {
        $order = $this->order(['locale' => 'es']);
        $invoices = app(OrderInvoiceService::class);
        $path = $invoices->issue($order);
        $bytes = Storage::disk('local')->get($path);
        $issuedAt = $order->fresh()->invoice_issued_at;

        $job = new SendOrderInvoiceEmail($order->id);
        $job->handle($invoices);
        $job->handle($invoices);

        Mail::assertSent(OrderInvoiceMail::class, 1);
        Mail::assertSent(OrderInvoiceMail::class, function (OrderInvoiceMail $mail) use ($order, $path): bool {
            $mail->assertHasAttachment(Attachment::fromStorageDisk('local', $path)
                ->as('facture-'.$order->order_number.'.pdf')
                ->withMime('application/pdf'));

            return $mail->hasTo($order->email) && $mail->locale === 'es';
        });

        $this->assertNotNull($order->fresh()->invoice_email_sent_at);
        $this->assertSame($bytes, Storage::disk('local')->get($path));
        $this->assertTrue($issuedAt->equalTo($order->fresh()->invoice_issued_at));
    }

    public function test_job_rechecks_eligibility_and_never_sends_old_or_unshipped_orders(): void
    {
        $invoices = app(OrderInvoiceService::class);

        foreach ([
            ['invoice_email_enabled' => false],
            ['status' => 'pending'],
            ['payment_status' => 'failed'],
            ['status' => 'cancelled'],
            ['invoice_email_sent_at' => now()],
        ] as $attributes) {
            $order = $this->order($attributes);
            (new SendOrderInvoiceEmail($order->id))->handle($invoices);
        }

        Mail::assertNothingSent();
        $this->assertSame([], Storage::disk('local')->allFiles('invoices'));
    }

    public function test_failed_storage_does_not_mark_invoice_as_sent(): void
    {
        $order = $this->order();
        $service = \Mockery::mock(OrderInvoiceService::class);
        $service->shouldReceive('issue')->once()
            ->andThrow(new \App\Services\Invoices\InvoiceStorageException('invoices/test.pdf'));

        try {
            (new SendOrderInvoiceEmail($order->id))->handle($service);
            $this->fail('Storage failure should be retried by the queue.');
        } catch (\App\Services\Invoices\InvoiceStorageException) {
            $this->assertNull($order->fresh()->invoice_email_sent_at);
            $this->assertNotNull($order->fresh()->invoice_email_attempted_at);
            Mail::assertNothingSent();
        }
    }

    public function test_historical_and_imported_orders_default_to_disabled(): void
    {
        $order = $this->order(['invoice_email_enabled' => false]);
        $legacy = $order->replicate(['order_number', 'invoice_email_enabled']);
        $legacy->save();

        $this->assertFalse($legacy->fresh()->invoice_email_enabled);
    }

    private function order(array $attributes = []): Order
    {
        return Order::query()->create(array_merge([
            'first_name' => 'Marie', 'last_name' => 'Dupont',
            'phone' => '0102030405', 'email' => 'marie@example.com',
            'country' => 'France', 'shipping_country_code' => 'FR',
            'city' => 'Paris', 'zip' => '75001', 'address' => '10 rue Livraison',
            'items' => [['name' => 'Gel', 'quantity' => 1, 'unit_price' => 10, 'line_total' => 10]],
            'subtotal' => 10, 'delivery_cost' => 0, 'total_amount' => 10,
            'payment_status' => 'paid', 'paid_at' => now(),
            'status' => 'shipped', 'locale' => 'fr', 'invoice_email_enabled' => true,
        ], $attributes));
    }
}
