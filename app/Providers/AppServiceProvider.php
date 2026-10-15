<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Event;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use App\Observers\TranslatableSlugObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::component('layouts.app', 'layout');

        // Reuse the same branded Blade email design as the order receipts.
        // Laravel still owns its signed verification and reset-token logic.
        VerifyEmail::toMailUsing(function (object $notifiable, string $verificationUrl): MailMessage {
            return (new MailMessage)
                ->subject('BLACK MILK · Confirmez votre adresse e-mail')
                ->view('emails.account-action', [
                    'title' => 'Confirmez votre adresse e-mail',
                    'name' => $notifiable->name,
                    'message' => 'Confirmez votre adresse e-mail pour sécuriser votre compte BLACK MILK et retrouver toutes vos commandes.',
                    'actionLabel' => 'Confirmer mon adresse e-mail',
                    'actionUrl' => $verificationUrl,
                    'expiry' => 'Ce lien est valable pendant '.config('auth.verification.expire', 60).' minutes.',
                    'disclaimer' => 'Si vous n’avez pas créé de compte, vous pouvez ignorer cet e-mail.',
                ]);
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $resetUrl = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return (new MailMessage)
                ->subject('BLACK MILK · Créez votre mot de passe')
                ->view('emails.account-action', [
                    'title' => 'Votre mot de passe',
                    'name' => $notifiable->name,
                    'message' => 'Vous pouvez définir un nouveau mot de passe pour accéder à votre espace BLACK MILK et consulter vos commandes.',
                    'actionLabel' => 'Créer mon mot de passe',
                    'actionUrl' => $resetUrl,
                    'expiry' => 'Ce lien est valable pendant '.config('auth.passwords.users.expire', 60).' minutes.',
                    'disclaimer' => 'Si vous n’avez pas demandé ce lien, aucune action n’est nécessaire.',
                ]);
        });

        View::composer('layouts.footer', function ($view) {
            $view->with('footerCategories', Category::query()
                ->whereKeyNot(7)
                ->whereHas('products', fn ($query) => $query->where('active', true))
                ->orderBy('id')
                ->limit(8)
                ->get(['id', 'name', 'slug']));
        });

        Product::observe(TranslatableSlugObserver::class);
        Category::observe(TranslatableSlugObserver::class);
        Page::observe(TranslatableSlugObserver::class);
        Event::observe(TranslatableSlugObserver::class);
    }
}
