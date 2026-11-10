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
        $label = match ($this->kind) {
            'paid' => __('Paiement confirmé'),
            'reminder' => __('Votre commande vous attend'),
            default => __('Paiement non finalisé'),
        };

        return new Envelope(subject: "BLACK MILK · {$label} · {$this->order->order_number}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-status');
    }
}
