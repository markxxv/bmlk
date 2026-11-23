<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Invoices\InvoiceData;
use App\Services\Invoices\InvoicePdf;
use App\Services\Invoices\InvoiceStorageException;
use App\Services\Invoices\OrderInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminOrderInvoiceController extends Controller
{
    public function __invoke(Request $request, Order $order): StreamedResponse|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->guest(url('/admin/login'));
        }

        abort_unless($request->user()->is_admin === true, 403);
        abort_unless($order->payment_status === 'paid', 404);

        // Share the already issued private PDF with customers; never generate
        // a second invoice or change a previously established issue date.
        $filename = 'facture-'.InvoiceData::number($order).'.pdf';
        $headers = [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ];

        try {
            $path = app(OrderInvoiceService::class)->issue($order);
        } catch (InvoiceStorageException $exception) {
            Log::error('Invoice PDF storage failed; check write permissions for the PHP-FPM user.', [
                'order_id' => $order->id,
                'disk' => OrderInvoiceService::DISK,
                'path' => $exception->path,
            ]);

            // The admin can still obtain the PDF while private storage is
            // unavailable. The database is unchanged until storage works.
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
