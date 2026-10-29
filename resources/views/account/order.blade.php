<x-layout :title="__('Détails de commande | BLACK MILK')" robots="noindex, nofollow">
<main class="mx-auto max-w-6xl px-4 py-10 sm:py-16">
    @include('account.partials.nav')

<div class="max-w-3xl rounded-3xl bg-white p-6 sm:p-9">
    <a href="{{ route('account.index') }}" class="inline-flex items-center gap-2 text-sm text-zinc-500 hover:text-bmlk-brand"><x-lucide-arrow-left class="h-4 w-4" />{{ __('Mes commandes') }}</a>
    <h1 class="mt-5 font-serif text-3xl text-zinc-900">{{ $order->order_number }}</h1>
    <p class="mt-2 text-sm text-zinc-500">{{ $order->payment_status === 'paid' ? __('Paiement confirmé') : __('Paiement en attente') }}</p>
    @if (filled($order->shipping_option_name))
        <p class="mt-3 text-xs text-zinc-500"><x-lucide-truck class="mr-1 inline h-3.5 w-3.5 text-bmlk-brand" />{{ $order->shipping_option_name }}</p>
        @if ($order->service_point_name)
            <p class="mt-1 text-xs text-zinc-500">{{ $order->service_point_name }} · {{ $order->service_point_address }}</p>
        @endif
    @endif
    @if (filled($order->tracking_number))
        <div class="mt-4 rounded-xl bg-zinc-50 p-4 text-sm">
            <div class="flex items-center gap-2"><x-lucide-package-check class="h-4 w-4 text-bmlk-brand" />{{ __('Numéro de suivi') }} : <strong>{{ $order->tracking_number }}</strong></div>
            @if (filled($order->tracking_url))
                <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex items-center gap-1 text-bmlk-brand hover:underline">{{ __('Suivre mon colis') }}<x-lucide-external-link class="h-3 w-3" /></a>
            @endif
        </div>
    @endif
    <div class="mt-6 divide-y divide-zinc-100">
        @foreach ($order->items as $item)
            <div class="flex items-center gap-4 py-4">
                @if (data_get($item, 'image'))<img src="{{ data_get($item, 'image') }}" alt="" class="h-16 w-16 rounded-xl object-cover">@endif
                <p class="flex-1 text-sm">{{ data_get($item, 'name') }} <span class="text-zinc-500">× {{ data_get($item, 'quantity') }}</span></p>
                <p class="text-sm font-medium">{{ number_format((float) data_get($item, 'line_total'), 2, ',', ' ') }} €</p>
            </div>
        @endforeach
    </div>
    @if ((float) $order->discount_amount > 0)
    <div class="flex justify-between text-sm text-bmlk-brand"><span>{{ __('Réduction') }} · {{ $order->promo_code }}</span><span>− {{ number_format($order->discount_amount, 2, ',', ' ') }} €</span></div>
    @endif
    <div class="mt-5 flex justify-between border-t border-zinc-100 pt-5 font-medium"><span>{{ __('Total') }}</span><span>{{ number_format($order->total_amount, 2, ',', ' ') }} €</span></div>
    @if ($order->payment_status !== 'paid' && $order->payment_status !== 'processing')
        <form action="{{ route('order.pay', ['orderNumber' => $order->order_number, 'token' => $order->access_token]) }}" method="POST" class="mt-8">@csrf
            <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-bmlk-brand px-6 py-3 text-sm font-medium text-white"><x-lucide-credit-card class="h-4 w-4" aria-hidden="true" />{{ __('Reprendre le paiement') }}</button>
        </form>
    @endif
</div>

</main>
</x-layout>
