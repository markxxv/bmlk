<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Invoices\InvoiceData;
use App\Services\Invoices\OrderInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
