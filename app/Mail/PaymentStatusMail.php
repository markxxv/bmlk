<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaymentStatusMail extends Mailable
{
    public function __construct(
        public Order $order,
        public string $kind,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->kind) {
            'paid' => "BLACK MILK · Paiement confirmé · {$this->order->order_number}",
            'reminder' => "BLACK MILK · Votre commande vous attend · {$this->order->order_number}",
            default => "BLACK MILK · Paiement non finalisé · {$this->order->order_number}",
        });
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-status');
    }
}
