<?php

namespace App\Services\Invoices;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderInvoiceService
{
    public const DISK = 'local';

    /**
     * Issue exactly one private PDF for a paid order. Database row locking
     * protects against simultaneous queue jobs and customer downloads.
     */
    public function issue(Order $order): ?string
    {
        return DB::transaction(function () use ($order): ?string {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($locked->payment_status !== 'paid') {
                return null;
            }

            if ($locked->invoice_file_path &&
                Storage::disk(self::DISK)->exists($locked->invoice_file_path)) {
                return $locked->invoice_file_path;
            }

            $issuedAt = $locked->invoice_issued_at ?? now();
            $locked->invoice_issued_at = $issuedAt;
            $path = 'invoices/invoice-'.InvoiceData::number($locked).'.pdf';
            $pdf = InvoicePdf::render(InvoiceData::forOrder($locked));

            if (! Storage::disk(self::DISK)->put($path, $pdf)) {
                throw new InvoiceStorageException($path);
            }

            $locked->forceFill([
                'invoice_file_path' => $path,
                'invoice_issued_at' => $issuedAt,
            ])->save();

            return $path;
        }, 3);
    }
}
