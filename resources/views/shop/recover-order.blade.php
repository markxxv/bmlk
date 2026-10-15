<x-layout :title="$metaTitle" robots="noindex, nofollow">
    <main class="px-4 py-16 sm:py-24">
        <div class="mx-auto max-w-lg rounded-3xl bg-white p-7 sm:p-10">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-[#F6ECEE] text-[#A9636F]">
                <x-lucide-receipt-text class="h-5 w-5" />
            </span>

            <h1 class="mt-6 font-serif text-3xl font-medium tracking-tight text-zinc-900 sm:text-4xl">
                {{ __('Reprendre votre commande') }}
            </h1>
            <p class="mt-3 text-sm leading-6 text-zinc-500">
                {{ __('Indiquez votre numéro de commande. Si vous avez renseigné un e-mail lors de l’achat, saisissez-le pour retrouver votre commande et reprendre le paiement.') }}
            </p>

            <form method="POST" action="{{ route('order.recover.submit') }}" class="mt-8 space-y-4">
                @csrf

                <div>
                    <label for="order-number" class="mb-2 block text-sm font-medium text-zinc-800">{{ __('Numéro de commande') }}</label>
                    <input id="order-number" name="order_number" type="text" maxlength="24"
                        value="{{ old('order_number', $orderNumber) }}"
                        placeholder="BM-20261015-000001" autocomplete="off" required
                        class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-900 outline-none focus:border-[#A9636F]">
                    @error('order_number')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="order-email" class="mb-2 block text-sm font-medium text-zinc-800">{{ __('E-mail (si renseigné)') }}</label>
                    <input id="order-email" name="email" type="email" maxlength="255" value="{{ old('email') }}"
                        autocomplete="email"
                        class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-900 outline-none focus:border-[#A9636F]">
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl bg-[#A9636F] px-5 py-4 text-sm font-medium text-white transition hover:bg-[#945763]">
                    <x-lucide-arrow-right class="h-4 w-4" />
                    {{ __('Retrouver ma commande') }}
                </button>
            </form>

            <a href="{{ route('shop.index') }}" class="mt-6 inline-block text-sm text-zinc-500 hover:text-zinc-900">
                {{ __('Retour à la boutique') }}
            </a>
        </div>
    </main>
</x-layout>
