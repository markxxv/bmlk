<x-layout
    :title="$metaTitle"
    robots="noindex, nofollow"
>
    <main class="px-3 py-14 sm:px-5 sm:py-20 lg:px-8">
        <div class="mx-auto max-w-3xl rounded-3xl bg-white p-7 sm:p-10">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#EFDDE0] text-[#A9636F]">
                <x-lucide-check class="h-5 w-5" />
            </div>

            <p class="mt-6 text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                {{ __('Commande enregistrée') }}
            </p>

            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl">
                {{ __('Merci pour votre commande') }}
            </h1>

            <p class="mt-3 text-sm leading-6 text-zinc-500">
                {{ __('Numéro de commande') }}:
                <span class="font-semibold text-zinc-900">{{ $order->order_number }}</span>
            </p>

            <div class="mt-8 divide-y divide-zinc-100 border-y border-zinc-100">
                @foreach ($order->items as $item)
                    <article class="grid grid-cols-[64px_1fr_auto] gap-4 py-4">
                        @if (filled(data_get($item, 'image')))
                            <img
                                src="{{ data_get($item, 'image') }}"
                                alt="{{ data_get($item, 'name') }}"
                                class="h-16 w-16 rounded-lg object-cover"
                            >
                        @else
                            <div class="h-16 w-16 rounded-lg bg-zinc-100"></div>
                        @endif

                        <div>
                            <p class="text-sm font-medium text-zinc-900">
                                {{ data_get($item, 'name') }}
                            </p>

                            @foreach (data_get($item, 'options', []) as $option)
                                <p class="mt-0.5 text-[11px] text-zinc-400">
                                    {{ data_get($option, 'label') }} · {{ data_get($option, 'display') }}
                                </p>
                            @endforeach

                            <p class="mt-1 text-xs text-zinc-500">
                                {{ __('Quantité') }}: {{ data_get($item, 'quantity') }}
                            </p>
                        </div>

                        <p class="text-sm font-medium text-zinc-900">
                            {{ number_format((float) data_get($item, 'line_total'), 2, ',', ' ') }} €
                        </p>
                    </article>
                @endforeach
            </div>

            <div class="mt-6 flex items-center justify-between">
                <span class="text-sm text-zinc-500">{{ __('Total') }}</span>
                <span class="text-lg font-semibold text-zinc-900">
                    {{ number_format((float) $order->total_amount, 2, ',', ' ') }} €
                </span>
            </div>

            <a
                href="{{ route('shop.index') }}"
                class="mt-8 inline-flex rounded-full bg-zinc-900 px-6 py-3 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-zinc-800"
            >
                {{ __('Continuer mes achats') }}
            </a>
        </div>
    </main>
</x-layout>
