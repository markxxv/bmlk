<x-layout :title="$metaTitle" robots="noindex, nofollow">
    <script>
        document.addEventListener('alpine:init', () => {
            try {
                if (sessionStorage.getItem('bm_pending_order') === @js($order->order_number)) {
                    Alpine.store('cart').clearCart();
                    sessionStorage.removeItem('bm_pending_order');
                }

                const saved = JSON.parse(localStorage.getItem('bm_unpaid_order') || 'null');
                if (saved?.number === @js($order->order_number)) {
                    localStorage.removeItem('bm_unpaid_order');
                }
            } catch (error) {
                // Payment confirmation is not affected by browser storage.
            }
        });

        // Give the customer time to read the confirmation. The link works
        // immediately and is also a fallback when JavaScript is disabled.
        window.setTimeout(() => {
            window.location.assign(@js($accountUrl));
        }, 5000);
    </script>

    <main class="px-3 py-16 sm:px-5 sm:py-24 lg:px-8">
        <div class="mx-auto max-w-2xl rounded-3xl bg-white p-8 sm:p-12">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                <x-lucide-check class="h-6 w-6" />
            </span>

            <p class="mt-7 text-sm font-medium text-bmlk-brand">{{ __('Paiement confirmé') }}</p>
            <h1 class="mt-2 font-serif text-4xl font-medium tracking-tight text-zinc-900">
                {{ __('Merci pour votre commande') }}
            </h1>
            <p class="mt-4 text-sm leading-6 text-zinc-500">
                {{ __('Votre paiement a été reçu. Nous préparons votre commande.') }}
            </p>

            <div class="mt-8 space-y-4 rounded-2xl bg-bmlk-page p-5">
                <div class="flex items-center justify-between gap-4 text-sm">
                    <span class="text-zinc-500">{{ __('Numéro de commande') }}</span>
                    <span class="font-medium text-zinc-900">{{ $order->order_number }}</span>
                </div>
                @if ((float) $order->discount_amount > 0)
                    <div class="flex items-center justify-between gap-4 text-sm">
                        <span class="text-bmlk-brand">{{ __('Réduction') }} · {{ $order->promo_code }}</span>
                        <span class="text-bmlk-brand">− {{ number_format($order->discount_amount, 2, ',', ' ') }} €</span>
                    </div>
                @endif
                <div class="flex items-center justify-between gap-4 text-sm">
                    <span class="text-zinc-500">{{ __('Total payé') }}</span>
                    <span class="font-semibold text-zinc-900">{{ number_format((float) $order->total_amount, 2, ',', ' ') }} €</span>
                </div>
            </div>

            <div class="mt-8">
                <a href="{{ $accountUrl }}"
                    class="inline-flex items-center gap-2 rounded-full bg-bmlk-brand px-6 py-3.5 text-sm font-semibold text-white transition hover:opacity-90">
                    <x-lucide-user-round class="h-4 w-4" />
                    {{ $accountReady ? __('Accéder à mon espace') : __('Se connecter à mon espace') }}
                    <x-lucide-arrow-right class="h-4 w-4" />
                </a>

                <p class="mt-4 text-xs leading-5 text-zinc-500">
                    {{ __('Redirection automatique dans 5 secondes.') }}
                </p>

                @unless ($accountReady)
                    <p class="mt-2 text-xs leading-5 text-zinc-500">
                        {{ __('Un compte existe déjà avec cet e-mail ? Connectez-vous pour accéder à vos commandes.') }}
                    </p>
                @endunless
            </div>

            <a href="{{ route('shop.index') }}"
                class="mt-7 inline-flex text-sm text-zinc-500 transition hover:text-zinc-900">
                {{ __('Continuer mes achats') }}
            </a>
        </div>
    </main>
</x-layout>
