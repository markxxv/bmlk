<x-layout :title="__('Votre avis | BLACK MILK')" robots="noindex, nofollow">
    <main class="mx-auto max-w-2xl px-4 py-8 sm:py-16">
        <div class="mb-6 text-center">
            <span class="inline-flex size-12 items-center justify-center rounded-full bg-bmlk-soft text-bmlk-brand">
                <x-lucide-heart class="size-6" aria-hidden="true" />
            </span>
            <h1 class="mt-4 font-serif text-3xl text-zinc-900 sm:text-4xl">{{ __('Merci pour votre confiance') }}</h1>
            <p class="mx-auto mt-3 max-w-lg text-sm leading-6 text-zinc-600">
                {{ __('Vos retours nous aident à améliorer les produits BLACK MILK. Évaluez les articles de votre commande, sans créer de compte.') }}
            </p>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-2xl bg-emerald-50 px-5 py-4 text-sm text-emerald-800" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if (! $order->hasUnratedItems())
            <div class="mb-5 rounded-2xl bg-bmlk-soft px-5 py-4 text-sm text-bmlk-brand">
                {{ __('Merci ! Tous les produits ont été évalués. Vous pouvez encore modifier vos notes.') }}
            </div>
        @endif

        @include('account.partials.product-ratings', [
            'order' => $order,
            'ratingProducts' => $ratingProducts,
            'ratingsAction' => route('reviews.store', ['token' => $token]),
            'showCompletedRatings' => true,
        ])

        <p class="mt-6 text-center text-xs leading-6 text-zinc-500">
            {{ __('Ce lien privé permet uniquement d’évaluer les produits de cette commande. Il expire 30 jours après l’envoi de l’e-mail.') }}
        </p>
    </main>
</x-layout>
