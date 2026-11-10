<?php

namespace App\Mail;

use App\Models\CoursePurchase;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CoursePurchasedMail extends Mailable
{
    public function __construct(public CoursePurchase $purchase) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'BLACK MILK · '.__('Votre formation est prête').' · '.$this->purchase->course->name);
    }

    public function content(): Content
    {
        $user = $this->purchase->user;

        return new Content(
            view: 'emails.account-action',
            with: [
                'title' => __('Votre formation est prête'),
                'name' => $user?->name,
                'bodyText' => __('Merci pour votre achat : « :course » est disponible dans votre espace, avec les liens vers les supports et le chat de la formation.', ['course' => $this->purchase->course->name]),
                'actionUrl' => $user && $user->password
                    ? route('account.courses', ['locale' => $this->purchase->locale])
                    : route('password.request', ['locale' => $this->purchase->locale]),
                'actionLabel' => $user && $user->password ? __('Accéder à ma formation') : __('Définir mon mot de passe'),
                'expiry' => $user && $user->password
                    ? __('Connectez-vous avec cette adresse e-mail pour retrouver vos formations.')
                    : __('Définissez votre mot de passe avec cette adresse e-mail pour accéder à votre formation.'),
                'disclaimer' => __('Si vous n’êtes pas à l’origine de cet achat, ignorez cet e-mail.'),
            ],
        );
    }
}
