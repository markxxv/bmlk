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
                                href="{{ route('shop.index') }}"
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
                    <div class="aspect-square w-full max-w-2xl overflow-hidden rounded-full bg-[#FCF8F4] lg:p-24">
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

    <section class="px-3 pb-16 sm:px-5 sm:pb-20 lg:px-8 lg:pb-24">
        <div class="mx-auto max-w-[1560px] overflow-hidden rounded-3xl bg-[#DDA1AA]">
            <div class="grid lg:grid-cols-2">
                <div>
                    <img
                        src="/img/elena.webp"
                        alt="{{ __('Elena Smirnova, fondatrice de BLACK MILK') }}"
                        class="block h-auto w-full"
                        loading="lazy"
                    >
                </div>

                <div class="flex items-center p-8 sm:p-10 lg:p-14 xl:p-16">
                    <div class="max-w-xl">
                        <p class="text-xs font-semibold uppercase tracking-widest text-zinc-700">
                            {{ __('La maître derrière la marque') }}
                        </p>

                        <h2 class="mt-5 font-['Playfair_Display'] text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl">
                            {{ __('Née') }}
                            <span class="italic text-white">{{ __('de la') }}</span>
                            {{ __('pratique') }}
                        </h2>

                        <div class="mt-7 space-y-5 text-base leading-7 text-zinc-700 sm:text-lg">
                            <p>
                                {{ __('Elena Smirnova a développé une méthode révolutionnaire de manucure à la cire et formulé chaque produit pour qu’il tienne ses promesses en cabine. Ses formules HEMA-free sont utilisées par des centaines de maîtres dans plus de 50 pays.') }}
                            </p>

                            <p>
                                {{ __('Chaque référence est testée en conditions réelles avant d’arriver dans ta commande.') }}
                            </p>
                        </div>

                        <a
                            href="#"
                            class="mt-8 inline-flex h-12 items-center justify-center rounded-full border border-zinc-900 px-6 text-xs font-semibold uppercase tracking-widest text-zinc-900 transition hover:bg-zinc-900 hover:text-white"
                        >
                            {{ __('Notre histoire') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @foreach ($categories as $category)
        <section class="overflow-hidden border-t border-zinc-200 px-3 py-16 sm:px-5 sm:py-20 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-[1560px]">
                <div class="grid gap-6 lg:grid-cols-2 lg:items-end">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                            {{ __('Collection') }}
                        </p>

                        <h2 class="mt-4 max-w-3xl font-['Playfair_Display'] text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl">
                            {{ $category->name }}
                        </h2>
                    </div>

                    @if ($category->description)
                        <p class="max-w-xl text-base leading-7 text-zinc-600 lg:justify-self-end lg:text-lg">
                            {{ $category->description }}
                        </p>
                    @endif
                </div>

                <div class="-mx-3 mt-10 flex snap-x snap-mandatory gap-5 overflow-x-auto px-3 pb-4 sm:-mx-5 sm:mt-12 sm:px-5 lg:mx-0 lg:grid lg:grid-cols-4 lg:gap-6 lg:overflow-visible lg:px-0 lg:pb-0">
                    @foreach ($category->products as $product)
                        @php
                            $image = $product->getFirstMediaUrl('images');
                        @endphp

                        <article class="group w-3/4 shrink-0 snap-start sm:w-2/5 lg:w-auto">
                            <a href="{{ route('shop.category', ['slug' => $category->slug]) }}" class="block">
                                @if ($image)
                                    <div class="overflow-hidden rounded-3xl bg-stone-100">
                                        <img
                                            src="{{ $image }}"
                                            alt="{{ $product->name }}"
                                            class="block h-auto w-full transition duration-500 group-hover:scale-105"
                                            loading="lazy"
                                        >
                                    </div>
                                @endif

                                <div class="pt-4">
                                    @if ($product->tag)
                                        <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                                            {{ $product->tag }}
                                        </p>
                                    @endif

                                    <div class="mt-2 flex items-start justify-between gap-4">
                                        <h3 class="font-['Playfair_Display'] text-xl font-medium leading-tight text-zinc-900 sm:text-2xl">
                                            {{ $product->name }}
                                        </h3>

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
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach


    <section class="px-3 pb-6 pt-8 sm:px-5 sm:pb-8 sm:pt-12 lg:px-8 lg:pb-10 lg:pt-16">
        <div class="mx-auto max-w-[1560px] rounded-3xl bg-[#EFDDE0] px-6 py-20 text-center sm:px-10 sm:py-24 lg:px-16 lg:py-28">
            <div class="mx-auto max-w-4xl">
                <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                    {{ __('BLACK MILK Journal') }}
                </p>

                <h2 class="mt-5 font-['Playfair_Display'] text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl">
                    {{ __('Reste') }}
                    <span class="italic text-[#A9636F]">{{ __('au') }}</span>
                    {{ __('courant') }}
                </h2>

                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-zinc-600 sm:text-lg">
                    {{ __('Nouvelles teintes, éditions limitées et conseils de la maître, directement dans ta boîte mail.') }}
                </p>

                <form
                    x-data
                    @submit.prevent
                    class="mx-auto mt-10 flex max-w-2xl items-center bg-white rounded-full py-2 px-6 text-left sm:mt-12"
                >
                    <label for="newsletter-email" class="sr-only">
                        {{ __('Votre adresse e-mail') }}
                    </label>

                    <input
                        id="newsletter-email"
                        type="email"
                        name="email"
                        placeholder="{{ __('Votre adresse e-mail') }}"
                        class="min-w-0 flex-1 bg-transparent text-sm font-medium uppercase tracking-widest text-zinc-900 outline-none placeholder:text-zinc-500"
                    >

                    <button
                        type="submit"
                        class="ml-4 -mr-4 flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition bg-zinc-800 hover:bg-zinc-900 text-white"
                        aria-label="{{ __('S’inscrire à la newsletter') }}"
                    >
                        <x-lucide-arrow-right class="h-5 w-5" />
                    </button>
                </form>
            </div>
        </div>
    </section>

</x-layout>
