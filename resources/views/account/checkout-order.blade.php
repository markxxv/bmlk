<x-layout :title="__('Ma commande | BLACK MILK')" robots="noindex, nofollow">
    <main class="mx-auto max-w-3xl px-4 py-12 sm:py-20">
        <section class="rounded-3xl bg-white p-7 sm:p-10">
            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-bmlk-soft text-bmlk-brand">
                @if ($order->payment_status === 'paid')
                    <x-lucide-package-check class="h-6 w-6" />
                @else
                    <x-lucide-receipt-text class="h-6 w-6" />
                @endif
            </span>
            <p class="mt-6 text-sm font-medium text-bmlk-brand">
                {{ $order->payment_status === 'paid' ? __('Paiement confirmé') :
                    ($order->payment_status === 'processing' ? __('Paiement en cours de confirmation') : __('Paiement non finalisé')) }}
            </p>
            <h1 class="mt-2 font-serif text-3xl text-zinc-900 sm:text-4xl">{{ __('Votre commande') }}</h1>
            <p class="mt-3 text-sm leading-6 text-zinc-500">
                {{ $order->payment_status === 'paid'
                    ? __('Votre paiement a été reçu. Nous préparons votre commande.')
                    : __('Votre commande est enregistrée. Vos articles sont conservés et vous pouvez reprendre le paiement sans refaire votre panier.') }}
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-bmlk-page px-5 py-4">
                <div>
                    <span class="block text-xs text-zinc-500">{{ __('Numéro de commande') }}</span>
                    <strong class="mt-1 block text-sm text-zinc-900">{{ $order->order_number }}</strong>
                </div>
                <strong class="text-lg text-zinc-900">{{ number_format((float) $order->total_amount, 2, ',', ' ') }} €</strong>
            </div>

            <h2 class="mt-9 font-serif text-2xl text-zinc-900">{{ __('Articles commandés') }}</h2>
            <div class="mt-3 divide-y divide-zinc-100">
                @foreach ($order->items ?? [] as $item)
                    <div class="flex items-start gap-4 py-5">
                        @if (data_get($item, 'image'))
                            <img src="{{ data_get($item, 'image') }}" alt="" class="h-16 w-16 shrink-0 rounded-xl object-cover">
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-zinc-900">{{ data_get($item, 'name') }}</p>
                            @foreach (data_get($item, 'options', []) as $option)
                                <p class="mt-1 text-xs text-zinc-500">
                                    {{ data_get($option, 'label') }} · {{ data_get($option, 'display') }}
                                </p>
                            @endforeach
                            <p class="mt-2 text-xs text-zinc-500">
                                {{ data_get($item, 'quantity') }} × {{ number_format((float) data_get($item, 'unit_price'), 2, ',', ' ') }} €
                            </p>
                        </div>
                        <p class="shrink-0 text-sm font-semibold text-zinc-900">
                            {{ number_format((float) data_get($item, 'line_total'), 2, ',', ' ') }} €
                        </p>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 space-y-3 border-t border-zinc-100 pt-6 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <span class="text-zinc-500">{{ __('Sous-total') }}</span>
                    <span class="text-zinc-900">{{ number_format((float) $order->subtotal, 2, ',', ' ') }} €</span>
                </div>
                @if ((float) $order->discount_amount > 0)
                    <div class="flex items-center justify-between gap-4 text-bmlk-brand">
                        <span>{{ __('Réduction') }}{{ $order->promo_code ? ' · '.$order->promo_code : '' }}</span>
                        <span>− {{ number_format((float) $order->discount_amount, 2, ',', ' ') }} €</span>
                    </div>
                @endif
                <div class="flex items-center justify-between gap-4">
                    <span class="text-zinc-500">{{ __('Livraison') }}</span>
                    <span class="text-zinc-900">
                        {{ (float) $order->delivery_cost === 0.0 ? __('Offerte') : number_format((float) $order->delivery_cost, 2, ',', ' ').' €' }}
                    </span>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-zinc-100 pt-4 font-semibold text-zinc-900">
                    <span>{{ __('Total') }}</span>
                    <span class="text-lg">{{ number_format((float) $order->total_amount, 2, ',', ' ') }} €</span>
                </div>
            </div>

            @if ($order->payment_status !== 'paid' && $order->payment_status !== 'processing')
                <form method="POST" action="{{ route('order.pay', ['orderNumber' => $order->order_number, 'token' => $order->access_token]) }}" class="mt-8">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-bmlk-brand px-6 py-3.5 text-sm font-semibold text-white transition hover:opacity-90">
                        <x-lucide-credit-card class="h-4 w-4" />
                        {{ __('Reprendre le paiement') }}
                    </button>
                </form>
            @endif

            @guest
                <div class="mt-9 rounded-2xl bg-bmlk-soft p-6">
                    <h2 class="font-serif text-xl text-zinc-900">{{ __('Retrouvez votre espace personnel') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-zinc-600">
                        {{ __('Vous pouvez consulter cette commande sans compte. Pour retrouver toutes vos commandes, créez un mot de passe ou connectez-vous.') }}
                    </p>
                    <div class="mt-5 flex flex-wrap items-center gap-4 text-sm">
                        <a href="{{ route('password.request', ['email' => $order->email]) }}"
                            class="inline-flex items-center gap-2 rounded-full bg-bmlk-brand px-5 py-3 font-medium text-white">
                            <x-lucide-key-round class="h-4 w-4" />
                            {{ __('Créer mon mot de passe') }}
                        </a>
                        <a href="{{ route('account.login') }}" class="font-medium text-bmlk-brand">
                            {{ __('J’ai déjà un compte') }}
                        </a>
                    </div>
                </div>
            @endguest
        </section>
    </main>
</x-layout>
