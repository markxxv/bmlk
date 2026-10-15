<x-layout :title="__('Ma commande | BLACK MILK')" robots="noindex, nofollow">
    <main class="mx-auto max-w-3xl px-4 py-12 sm:py-20">
        <section class="rounded-3xl bg-white p-7 sm:p-10">
            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-bmlk-soft text-bmlk-brand">
                <x-lucide-package-check class="h-6 w-6" />
            </span>
            <p class="mt-6 text-sm font-medium text-bmlk-brand">{{ __('Paiement confirmé') }}</p>
            <h1 class="mt-2 font-serif text-3xl text-zinc-900 sm:text-4xl">{{ __('Votre commande') }}</h1>
            <p class="mt-3 text-sm leading-6 text-zinc-500">
                {{ __('Votre commande est enregistrée. Retrouvez ses détails ici, sans refaire votre panier.') }}
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-bmlk-page px-5 py-4">
                <div>
                    <span class="block text-xs text-zinc-500">{{ __('Numéro de commande') }}</span>
                    <strong class="mt-1 block text-sm text-zinc-900">{{ $order->order_number }}</strong>
                </div>
                <strong class="text-lg text-zinc-900">{{ number_format((float) $order->total_amount, 2, ',', ' ') }} €</strong>
            </div>

            <div class="mt-6 divide-y divide-zinc-100">
                @foreach ($order->items as $item)
                    <div class="flex items-center gap-4 py-4">
                        @if (data_get($item, 'image'))
                            <img src="{{ data_get($item, 'image') }}" alt="" class="h-16 w-16 rounded-xl object-cover">
                        @endif
                        <span class="flex-1 text-sm text-zinc-800">
                            {{ data_get($item, 'name') }} × {{ data_get($item, 'quantity') }}
                        </span>
                        <span class="text-sm font-medium text-zinc-900">
                            {{ number_format((float) data_get($item, 'line_total'), 2, ',', ' ') }} €
                        </span>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 rounded-2xl bg-bmlk-soft p-6">
                <h2 class="font-serif text-xl text-zinc-900">{{ __('Retrouvez votre espace personnel') }}</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-600">
                    {{ __('Si ce compte existe déjà, connectez-vous. Si vous n’avez pas encore de mot de passe, créez-le par le lien sécurisé envoyé à votre adresse e-mail.') }}
                </p>
                <div class="mt-5 flex flex-wrap gap-4 text-sm">
                    <a href="{{ route('password.request') }}?email={{ urlencode((string) $order->email) }}"
                       class="inline-flex items-center gap-2 rounded-full bg-bmlk-brand px-5 py-3 font-medium text-white">
                        <x-lucide-key-round class="h-4 w-4" />
                        {{ __('Créer mon mot de passe') }}
                    </a>
                    <a href="{{ route('account.login') }}" class="inline-flex items-center rounded-full px-3 py-3 font-medium text-bmlk-brand">
                        {{ __('J’ai déjà un compte') }}
                    </a>
                </div>
            </div>
        </section>
    </main>
</x-layout>
