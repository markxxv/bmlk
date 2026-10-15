<x-layout :title="$metaTitle" robots="noindex, nofollow">
    <script>
        document.addEventListener('alpine:init', () => {
            if (sessionStorage.getItem('bm_pending_order') === @js($order->order_number)) {
                Alpine.store('cart').clearCart();
                sessionStorage.removeItem('bm_pending_order');
            }
        });
    </script>

    <main class="px-3 py-16 sm:px-5 sm:py-24 lg:px-8">
        <div class="mx-auto max-w-2xl rounded-3xl bg-white p-8 sm:p-12">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                <x-lucide-check class="h-6 w-6" />
            </span>

            <p class="mt-7 text-sm font-medium text-[#A9636F]">{{ __('Paiement confirmé') }}</p>
            <h1 class="mt-2 font-serif text-4xl font-medium tracking-tight text-zinc-900">
                {{ __('Merci pour votre commande') }}
            </h1>
            <p class="mt-4 text-sm leading-6 text-zinc-500">
                {{ __('Votre paiement a été reçu. Nous préparons votre commande.') }}
            </p>

            <div class="mt-8 space-y-4 rounded-2xl bg-[#FCF8F4] p-5">
                <div class="flex items-center justify-between gap-4 text-sm">
                    <span class="text-zinc-500">{{ __('Numéro de commande') }}</span>
                    <span class="font-medium text-zinc-900">{{ $order->order_number }}</span>
                </div>
                <div class="flex items-center justify-between gap-4 text-sm">
                    <span class="text-zinc-500">{{ __('Total payé') }}</span>
                    <span class="font-semibold text-zinc-900">{{ number_format((float) $order->total_amount, 2, ',', ' ') }} €</span>
                </div>
            </div>

            <a href="{{ route('shop.index') }}"
                class="mt-8 inline-flex rounded-full bg-zinc-900 px-6 py-3 text-sm font-medium text-white transition hover:bg-zinc-800">
                {{ __('Continuer mes achats') }}
            </a>
        </div>
    </main>
</x-layout>
