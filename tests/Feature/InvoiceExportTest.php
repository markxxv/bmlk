<?php

namespace Tests\Feature;

use App\Filament\Pages\Invoices;
use App\Models\InvoiceExport;
use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class InvoiceExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Storage::fake(InvoiceExport::DISK);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function order(string $paidAt, string $payment = 'paid'): Order
    {
        return Order::query()->create([
            'first_name' => 'Anna', 'last_name' => 'Müller', 'phone' => '1', 'email' => 'a@example.com',
            'country' => 'Germany', 'shipping_country_code' => 'DE', 'zip' => '10115', 'city' => 'Berlin',
            'address' => 'Hauptstraße 5',
            'items' => [['name' => 'Gel polish', 'options' => [], 'quantity' => 2, 'unit_price' => 14.5, 'line_total' => 29.0]],
            'subtotal' => 29, 'delivery_cost' => 6.9, 'total_amount' => 35.9,
            'status' => 'completed', 'payment_status' => $payment, 'paid_at' => $paidAt,
        ]);
    }

    public function test_command_builds_zip_with_only_paid_orders_in_range_and_notifies_telegram(): void
    {
        config(['services.telegram.bot_token' => 'tok', 'services.telegram.chat_id' => '42']);
        Http::fake();

        $inside = $this->order('2026-10-15 10:00:00');
        $this->order('2026-09-30 10:00:00');
        $this->order('2026-10-16 10:00:00', 'unpaid');

        $export = InvoiceExport::query()->create([
            'date_from' => '2026-10-01', 'date_to' => '2026-10-31', 'status' => InvoiceExport::PENDING,
        ]);

        Artisan::call('invoices:build-exports');

        $export->refresh();
        $this->assertSame(InvoiceExport::READY, $export->status);
        $this->assertSame(1, $export->invoices_count);
        Storage::disk(InvoiceExport::DISK)->assertExists($export->file_path);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk(InvoiceExport::DISK)->path($export->file_path)));
        $this->assertSame(1, $zip->numFiles);
        $this->assertSame('invoice-'.$inside->order_number.'.pdf', $zip->getNameIndex(0));
        $this->assertStringStartsWith('%PDF-', $zip->getFromIndex(0));

        Http::assertSent(fn ($r) => str_contains($r->url(), '/bottok/sendMessage')
            && str_contains($r['text'], route('invoice-exports.download', $export)));
    }

    public function test_empty_period_fails_with_a_message(): void
    {
        $export = InvoiceExport::query()->create([
            'date_from' => '2026-01-01', 'date_to' => '2026-01-31', 'status' => InvoiceExport::PENDING,
        ]);

        Artisan::call('invoices:build-exports');

        $export->refresh();
        $this->assertSame(InvoiceExport::FAILED, $export->status);
        $this->assertStringContainsString('No paid orders', $export->error);
    }

    public function test_stale_processing_export_is_marked_failed(): void
    {
        $export = InvoiceExport::query()->create([
            'date_from' => '2026-01-01', 'date_to' => '2026-01-31',
            'status' => InvoiceExport::PROCESSING, 'started_at' => now()->subHour(),
        ]);

        Artisan::call('invoices:build-exports');

        $this->assertSame(InvoiceExport::FAILED, $export->fresh()->status);
    }

    public function test_page_creates_pending_row_and_delete_removes_file(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(Invoices::class)
            ->fillForm(['date_from' => '2026-10-01', 'date_to' => '2026-10-31'])
            ->call('createExport')
            ->assertHasNoFormErrors();

        $export = InvoiceExport::query()->firstOrFail();
        $this->assertSame(InvoiceExport::PENDING, $export->status);
        $this->assertSame($admin->id, $export->requested_by);

        Storage::disk(InvoiceExport::DISK)->put('invoice-exports/x.zip', 'zip');
        $export->update(['status' => InvoiceExport::READY, 'file_path' => 'invoice-exports/x.zip']);

        Livewire::actingAs($admin)->test(Invoices::class)
            ->callAction('delete', arguments: ['export' => $export->id]);

        $this->assertDatabaseCount('invoice_exports', 0);
        Storage::disk(InvoiceExport::DISK)->assertMissing('invoice-exports/x.zip');
    }

    public function test_download_is_admin_only(): void
    {
        Storage::disk(InvoiceExport::DISK)->put('invoice-exports/x.zip', 'zip');
        $export = InvoiceExport::query()->create([
            'date_from' => '2026-10-01', 'date_to' => '2026-10-31',
            'status' => InvoiceExport::READY, 'file_path' => 'invoice-exports/x.zip',
        ]);

        $this->get($export->downloadUrl())->assertRedirect(url('/admin/login'));
        $this->actingAs(User::factory()->create())->get($export->downloadUrl())->assertForbidden();
        $this->actingAs($this->admin())->get($export->downloadUrl())->assertOk();
    }
}
