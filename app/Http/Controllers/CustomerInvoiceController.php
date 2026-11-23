<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Invoices\InvoiceData;
use App\Services\Invoices\OrderInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerInvoiceController extends Controller
{
    public function __invoke(Request $request, Order $order): StreamedResponse
    {
        // Customer preview must remain read-only. An email-only guest token
        // is not an invoice authorization credential.
        abort_if($request->session()->has('customer_preview'), 403);
        abort_unless((int) $order->user_id === (int) $request->user()?->id, 404);
        abort_unless($order->payment_status === 'paid', 404);

        // Existing paid orders can be downloaded immediately even before the
        // background queue has built their invoice.
        $path = app(OrderInvoiceService::class)->issue($order);
        abort_unless($path, 404);

        return Storage::disk(OrderInvoiceService::DISK)->download(
            $path,
            'facture-'.InvoiceData::number($order).'.pdf',
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
