<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ReviewRequestMail extends Mailable
{
    public function __construct(
        public Order $order,
        public string $reviewUrl,
        public string $unsubscribeUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'BLACK MILK · '.__('Votre avis sur les produits reçus'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.review-request');
    }
}
