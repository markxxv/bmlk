<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\Invoices\InvoiceData;
use App\Services\Invoices\OrderInvoiceService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderInvoiceMail extends Mailable
{
    public function __construct(
        public Order $order,
        public string $invoicePath,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'BLACK MILK · '.__('Votre facture').' · '.$this->order->order_number);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-invoice');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk(OrderInvoiceService::DISK, $this->invoicePath)
                ->as('facture-'.InvoiceData::number($this->order).'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
