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

        document.addEventListener('DOMContentLoaded', () => {
            let destination = @js($accountUrl);
            const link = document.getElementById('checkout-account-link');
            const orderNumber = @js($order->order_number);
            const authenticated = @js($accountReady);

            // The handoff token was saved by the original first-party checkout,
            // not by Stripe's return URL, email links or order recovery.
            const activate = async () => {
                if (authenticated) return;

                let token;
                try {
                    token = sessionStorage.getItem('bm_checkout_activation_' + orderNumber);
                } catch (error) {
                    return;
                }

                if (!token) return;

                const abort = new AbortController();
                const maxWait = window.setTimeout(() => abort.abort(), 3500);

                try {
                    const response = await fetch(@js(route('account.complete_checkout')), {
                        method: 'POST',
                        credentials: 'same-origin',
                        signal: abort.signal,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ order_number: orderNumber, token }),
                    });

                    if (!response.ok) return;

                    const data = await response.json();
                    if (!data.url) return;

                    destination = data.url;
                    link.href = destination;
                    if (data.authenticated) {
                        sessionStorage.removeItem('bm_checkout_activation_' + orderNumber);
                    }
                } catch (error) {
                    // The confirmation is still available in the private order
                    // view; a failed handoff never forces the login screen.
                } finally {
                    window.clearTimeout(maxWait);
                }
            };

            const activation = activate();

            link.addEventListener('click', async event => {
                event.preventDefault();
                await activation;
                window.location.assign(destination);
            });

            window.setTimeout(() => {
                window.location.assign(destination);
            }, 5000);
        });
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
                <a id="checkout-account-link" href="{{ $accountUrl }}"
                    class="inline-flex items-center gap-2 rounded-full bg-bmlk-brand px-6 py-3.5 text-sm font-semibold text-white transition hover:opacity-90">
                    <x-lucide-user-round class="h-4 w-4" />
                    {{ __('Accéder à mon espace') }}
                    <x-lucide-arrow-right class="h-4 w-4" />
                </a>

                <p class="mt-4 text-xs leading-5 text-zinc-500">
                    {{ __('Redirection automatique dans 5 secondes.') }}
                </p>


            </div>

            <a href="{{ route('shop.index') }}"
                class="mt-7 inline-flex text-sm text-zinc-500 transition hover:text-zinc-900">
                {{ __('Continuer mes achats') }}
            </a>
        </div>
    </main>
</x-layout>
