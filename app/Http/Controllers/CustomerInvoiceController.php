<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Invoices\InvoiceData;
use App\Services\Invoices\InvoicePdf;
use App\Services\Invoices\InvoiceStorageException;
use App\Services\Invoices\OrderInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerInvoiceController extends Controller
{
    public function __invoke(Request $request, Order $order): StreamedResponse
    {
        $preview = $request->session()->has('customer_preview');
        $user = $request->user();
        $ownsOrder = $order->user_id !== null && (int) $order->user_id === (int) $user->id;
        // During admin preview the account can show unclaimed orders linked
        // to the customer's verified email. Mirror that exact access rule.
        $previewMatchesEmail = $preview
            && $user->hasVerifiedEmail()
            && $order->user_id === null
            && filled($order->email)
            && Str::lower(trim($order->email)) === Str::lower(trim($user->email));

        abort_unless($ownsOrder || $previewMatchesEmail, 404);
        abort_unless($order->payment_status === 'paid', 404);

        $filename = 'facture-'.InvoiceData::number($order).'.pdf';
        $headers = [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($preview) {
            // Read-only preview: render without issuing the invoice,
            // updating its fiscal date, or writing a file to storage.
            return response()->streamDownload(
                static function () use ($order): void {
                    echo InvoicePdf::render(InvoiceData::forOrder($order));
                },
                $filename,
                $headers,
            );
        }

        // Existing paid orders can be downloaded immediately even before the
        // background queue has built their invoice.
        try {
            $path = app(OrderInvoiceService::class)->issue($order);
        } catch (InvoiceStorageException $exception) {
            Log::error('Invoice PDF storage failed; check write permissions for the PHP-FPM user.', [
                'order_id' => $order->id,
                'disk' => OrderInvoiceService::DISK,
                'path' => $exception->path,
            ]);

            // Permit a download during a storage outage without silently
            // registering an invoice that was never saved.
            return response()->streamDownload(
                static function () use ($order): void {
                    echo InvoicePdf::render(InvoiceData::forOrder($order));
                },
                $filename,
                $headers,
            );
        }

        abort_unless($path, 404);

        return Storage::disk(OrderInvoiceService::DISK)->download($path, $filename, $headers);
    }
}
