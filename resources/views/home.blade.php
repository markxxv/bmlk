<x-layout>
    <section class="px-3 pb-6 pt-3 sm:px-5 sm:pb-8 sm:pt-5 lg:px-8 lg:pb-10">
        <div class="mx-auto max-w-[1560px]">
            <div class="grid overflow-hidden rounded-3xl bg-[#DDA1AA] lg:grid-cols-2">
                <div class="flex flex-col justify-between p-7 sm:p-10 lg:p-14 xl:p-16">
                    <div class="flex items-center gap-3">
                        <span class="h-px w-10 bg-zinc-800"></span>
                        <p class="text-xs font-semibold uppercase tracking-widest text-zinc-800">
                            {{ __('Créé par une maître, pour les maîtres') }}
                        </p>
                    </div>

                    <div class="py-14 lg:py-20">
                        <h1 class="max-w-3xl font-['Playfair_Display'] text-5xl font-medium leading-none tracking-tight text-zinc-900 sm:text-6xl lg:text-7xl xl:text-8xl">
                            {{ __('Élève') }}
                            <span class="italic text-white">{{ __('ton') }}</span>
                            {{ __('niveau.') }}
                        </h1>

                        <p class="mt-7 max-w-xl text-base leading-7 text-zinc-800 sm:text-lg">
                            {{ __('La technique révolutionnaire à la cire et des produits professionnels HEMA-free pensés pour celles et ceux qui exigent plus de leur travail.') }}
                        </p>

                        <div class="mt-9 flex flex-wrap gap-3">
                            <a
                                href="#coffrets"
                                class="inline-flex h-12 items-center justify-center rounded-full bg-zinc-900 px-6 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-zinc-800"
                            >
                                {{ __('Découvrir la boutique') }}
                            </a>

                            <a
                                href="#"
                                class="inline-flex h-12 items-center justify-center rounded-full border border-zinc-800 px-6 text-xs font-semibold uppercase tracking-widest text-zinc-900 transition hover:bg-white"
                            >
                                {{ __('Voir les cours') }}
                            </a>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-x-8 gap-y-3 border-t border-[#C98792] pt-5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-zinc-800">
                            {{ __('HEMA-free') }}
                        </p>
                        <p class="text-xs font-semibold uppercase tracking-widest text-zinc-800">
                            {{ __('50+ pays') }}
                        </p>
                        <p class="text-xs font-semibold uppercase tracking-widest text-zinc-800">
                            {{ __('Pour les professionnels') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-center px-5 pb-8 sm:px-10 lg:px-8 lg:py-10 xl:px-14">
                    <div class="aspect-square w-full max-w-2xl overflow-hidden rounded-full bg-[#FCF8F4]">
                        <x-cow />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="coffrets" class="overflow-hidden px-3 py-16 sm:px-5 sm:py-20 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-[1560px]">
            <div class="grid gap-6 lg:grid-cols-2 lg:items-end">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                        {{ __('Coffrets BLACK MILK') }}
                    </p>

                    <h2 class="mt-4 font-['Playfair_Display'] text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl">
                        {{ __('Compose') }}
                        <span class="italic text-[#A9636F]">{{ __('ton') }}</span>
                        {{ __('rituel') }}
                    </h2>
                </div>

                <p class="max-w-xl text-base leading-7 text-zinc-600 lg:justify-self-end lg:text-lg">
                    {{ __('Trois coffrets pensés pour chaque besoin — débuter, se réassortir avec les favoris, ou profiter d’une offre exceptionnelle.') }}
                </p>
            </div>

            <div class="-mx-3 mt-12 flex snap-x snap-mandatory gap-5 overflow-x-auto px-3 pb-4 sm:-mx-5 sm:px-5 lg:mx-0 lg:grid lg:grid-cols-3 lg:gap-8 lg:overflow-visible lg:px-0 lg:pb-0">
                @foreach ($coffrets as $product)
                    @php
                        $image = $product->getFirstMediaUrl('images');
                    @endphp

                    <article class="group w-4/5 shrink-0 snap-start sm:w-1/2 lg:w-auto @if ($loop->iteration === 2) lg:pt-12 @endif">
                        <a href="#" class="block">
                            <div class="overflow-hidden rounded-3xl bg-stone-100">
                                @if ($image)
                                    <img
                                        src="{{ $image }}"
                                        alt="{{ $product->name }}"
                                        class="block h-auto w-full transition duration-500 group-hover:scale-105"
                                        loading="lazy"
                                    >
                                @endif
                            </div>

                            <div class="pt-5">
                                <div class="flex items-start justify-between gap-5">
                                    <div class="min-w-0">
                                        @if ($product->tag)
                                            <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                                                {{ $product->tag }}
                                            </p>
                                        @endif

                                        <h3 class="mt-2 font-['Playfair_Display'] text-2xl font-medium leading-tight text-zinc-900 sm:text-3xl">
                                            {{ $product->name }}
                                        </h3>
                                    </div>

                                    <div class="shrink-0 pt-1 text-sm font-semibold text-zinc-900">
                                        @if ($product->price)
                                            {{ number_format((float) $product->price, 2, ',', ' ') }} €
                                        @elseif ($product->price_min && $product->price_max)
                                            {{ number_format((float) $product->price_min, 2, ',', ' ') }}–{{ number_format((float) $product->price_max, 2, ',', ' ') }} €
                                        @elseif ($product->price_min)
                                            {{ __('Dès :price €', ['price' => number_format((float) $product->price_min, 2, ',', ' ')]) }}
                                        @endif
                                    </div>
                                </div>

                                @if ($product->description)
                                    <p class="mt-3 max-w-md line-clamp-2 text-sm leading-6 text-zinc-600">
                                        {{ $product->description }}
                                    </p>
                                @endif

                                <span class="mt-5 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-zinc-900">
                                    {{ __('Découvrir') }}

                                    <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M5 12h14M14 7l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</x-layout>
