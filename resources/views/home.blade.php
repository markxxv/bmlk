<x-layout>
    <section class="relative z-[41] -mt-[92px] overflow-hidden bg-[#DDA1AA] pb-6 pt-[92px] sm:-mt-[100px] sm:pb-8 sm:pt-[100px] lg:-mt-[104px] lg:pb-10 lg:pt-[104px]">
        <div class="relative z-10 mx-auto max-w-[1560px] px-5 pt-3 sm:px-5 sm:pt-5 lg:px-8 lg:pt-6">
            <div class="relative lg:min-h-[720px] xl:min-h-[760px]">
                <div class="relative z-10 grid h-full lg:min-h-[720px] lg:grid-cols-12 xl:min-h-[760px]">
                    <div class="flex flex-col pb-7 pt-7 sm:px-10 sm:pb-10 sm:pt-12 lg:col-span-6 lg:px-14 lg:pb-10 lg:pt-14 xl:px-16 xl:pt-16">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="h-px w-8 shrink-0 bg-zinc-800 sm:w-10"></span>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-zinc-800 sm:text-xs sm:tracking-widest">
                                {{ __('Créé par une maître, pour les maîtres') }}
                            </p>
                        </div>

                        <div class="flex flex-1 items-center sm:py-16 lg:py-10">
                            <div>
                                <h1 class="max-w-2xl font-serif text-4xl font-medium leading-[0.92] tracking-tight text-zinc-900 sm:text-6xl lg:text-6xl pt-10 md:pt-0">
                                    <span class="block">{{ __('Gels professionnels') }}</span>
                                    <span class="block italic text-white">{{ __('Hema-free') }}</span>
                                    <span class="block">{{ __('pour les maîtres') }}</span>
                                </h1>

                                <p class="mt-5 max-w-lg text-sm leading-6 text-zinc-800 sm:mt-7 sm:text-lg sm:leading-7">
                                    {{ __('Bases, builder gels et soins pensés pour une pose précise, régulière et durable') }}
                                </p>

                                <div class="mt-6 flex gap-2 sm:mt-9 sm:flex-wrap sm:gap-3 relative z-10">
                                    <a href="{{ route('shop.index') }}" class="group inline-flex h-12 min-w-0 flex-1 items-center justify-center gap-2 rounded-full bg-zinc-900 px-5 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-zinc-800 sm:flex-none sm:gap-3 sm:px-6 sm:tracking-widest">
                                        {{ __('Découvrir la boutique') }}
                                        <x-lucide-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                                    </a>

                                    <a href="{{ route('where-to-buy') }}" class="inline-flex h-12 shrink-0 items-center justify-center rounded-full bg-white px-5 text-xs font-semibold uppercase tracking-wider text-zinc-900 transition hover:bg-zinc-50 sm:px-6 sm:tracking-widest">
                                        {{ __('Où acheter') }}
                                    </a>
                                </div>

                                <div class="relative -mx-5 -mt-9 sm:-mt-1 sm:hidden">
                                    <div class="relative left-1/2 w-[128%] -translate-x-1/2">
                                        <x-hero-mascot class="w-full" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 md:border-t md:border-[#C98792] md:pt-5 sm:gap-5 lg:gap-4">
                            <div class="flex items-start gap-3">
                                <x-lucide-leaf class="mt-0.5 h-4 w-4 shrink-0 text-zinc-800 sm:h-5 sm:w-5" stroke-width="1.4" />
                                <div>
                                    <p class="text-[9px] font-semibold uppercase tracking-wider text-zinc-800 sm:text-[11px] sm:tracking-widest">{{ __('HEMA-free') }}</p>
                                    <p class="mt-1 hidden text-[10px] uppercase tracking-[0.16em] text-zinc-700 sm:block">{{ __('Sans compromis') }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <x-lucide-globe-2 class="mt-0.5 h-4 w-4 shrink-0 text-zinc-800 sm:h-5 sm:w-5" stroke-width="1.4" />
                                <div>
                                    <p class="text-[9px] font-semibold uppercase tracking-wider text-zinc-800 sm:text-[11px] sm:tracking-widest">{{ __('50+ pays') }}</p>
                                    <p class="mt-1 hidden text-[10px] uppercase tracking-[0.16em] text-zinc-700 sm:block">{{ __('Dans le monde') }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <x-lucide-graduation-cap class="mt-0.5 h-4 w-4 shrink-0 text-zinc-800 sm:h-5 sm:w-5" stroke-width="1.4" />
                                <div>
                                    <p class="text-[9px] font-semibold uppercase tracking-wider text-zinc-800 sm:text-[11px] sm:tracking-widest">{{ __('Pour les professionnels') }}</p>
                                    <p class="mt-1 hidden text-[10px] uppercase tracking-[0.16em] text-zinc-700 sm:block">{{ __('Résultats d’exception') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pointer-events-none absolute bottom-[-2%] right-[-6%] z-0 hidden w-[112%] sm:block sm:right-[-2%] sm:w-[94%] lg:bottom-auto lg:left-1/2 lg:right-auto lg:top-24 lg:w-[clamp(700px,54vw,1100px)]">
            <x-hero-mascot class="w-full" />
        </div>
    </section>

    <section id="coffrets" class="overflow-hidden px-3 py-16 sm:px-5 sm:py-20 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-[1560px]">
            <div class="grid gap-6 lg:grid-cols-2 lg:items-end">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                        {{ __('Coffrets BLACK MILK') }}
                    </p>

                    <h2 class="mt-4 font-serif text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl">
                        {{ __('Compose') }}
                        <span class="italic text-[#A9636F]">{{ __('ton') }}</span>
                        {{ __('rituel') }}
                    </h2>
                </div>

                <p class="max-w-xl text-base leading-7 text-zinc-600 lg:justify-self-end lg:text-lg">
                    {{ __('Trois coffrets pensés pour chaque besoin — débuter, se réassortir avec les favoris, ou profiter d’une offre exceptionnelle.') }}
                </p>
            </div>

            <div class="-mx-3 mt-12 flex snap-x snap-mandatory gap-2 overflow-x-auto px-5 pb-4 sm:-mx-5 sm:gap-5 sm:px-5 lg:mx-0 lg:grid lg:grid-cols-3 lg:gap-8 lg:overflow-visible lg:px-0 lg:pb-0">
                @foreach ($coffrets as $product)
                    @php
                        $image = $product->getFirstMediaUrl('images');
                        $coffretLocale = in_array(app()->getLocale(), ['fr', 'en', 'ro'], true)
                            ? app()->getLocale()
                            : 'fr';
                        $coffretSlug = $product->getTranslation('slug', $coffretLocale, false)
                            ?: $product->getTranslation('slug', 'fr', false);
                        $coffretUrl = $coffretSlug
                            ? route('shop.product', ['slug' => $coffretSlug])
                            : route('shop.index');
                    @endphp

                    <article class="group w-4/5 shrink-0 snap-start sm:w-1/2 lg:w-auto @if ($loop->iteration === 2) lg:pt-12 @endif">
                        <a href="{{ $coffretUrl }}" class="block">
                            <div class="relative aspect-[3/4] overflow-hidden rounded-3xl">
                                    @if ($product->is_new)
                                        <span class="absolute left-3 top-3 z-10 rounded-full bg-[#DDA1AA] px-3 py-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-900 sm:left-4 sm:top-4 sm:px-4 sm:py-2 sm:text-xs">
                                            {{ __('Nouveau') }}
                                        </span>
                                    @endif
                                @if ($image)
                                    <img
                                        src="{{ $image }}"
                                        alt="{{ $product->name }}"
                                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
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

                                        <h3 class="mt-2 font-serif text-xl font-medium leading-tight text-zinc-900 sm:text-3xl">
                                            {{ $product->name }}
                                        </h3>
                                    </div>

                                    <div class="shrink-0 text-sm font-semibold text-zinc-900">
                                        @if ($product->price)
                                            {{ $product->formatted_price }} €
                                        @elseif ($product->price_min && $product->price_max)
                                            {{ $product->formatted_price_min }}–{{ $product->formatted_price_max }} €
                                        @elseif ($product->price_min)
                                            {{ __('Dès :price €', ['price' => $product->formatted_price_min]) }}
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

                        <h2 class="mt-5 font-serif text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl">
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
        @php
            $locale = in_array(app()->getLocale(), ['fr', 'en', 'ro'], true)
                ? app()->getLocale()
                : 'fr';

            $categorySlug = $category->getTranslation('slug', $locale, false)
                ?: $category->getTranslation('slug', 'fr', false);

            $categoryUrl = $categorySlug
                ? route('shop.category', ['slug' => $categorySlug])
                : route('shop.index');
        @endphp

        <section class="overflow-hidden border-t border-zinc-200 px-3 py-16 sm:px-5 sm:py-20 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-[1560px]">
                <div class="grid gap-6 lg:grid-cols-2 lg:items-end">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                            {{ __('Collection') }}
                        </p>

                        <h2 class="mt-4 max-w-3xl font-serif text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl">
                            <a href="{{ $categoryUrl }}" class="transition hover:text-[#A9636F]">
                                {{ $category->name }}
                            </a>
                        </h2>
                    </div>

                    <div class="lg:justify-self-end">
                        @if ($category->description)
                            <p class="max-w-xl text-base leading-7 text-zinc-600 lg:text-lg">
                                {{ $category->description }}
                            </p>
                        @endif

                        <a
                            href="{{ $categoryUrl }}"
                            class="group mt-5 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-[#A9636F] transition hover:text-[#945763]"
                        >
                            {{ __('Voir toute la collection') }}
                            <x-lucide-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </a>
                    </div>
                </div>

                <div class="-mx-3 mt-10 flex snap-x snap-mandatory gap-2 overflow-x-auto px-5 pb-4 sm:-mx-5 sm:mt-12 sm:gap-5 sm:px-5 lg:mx-0 lg:grid lg:grid-cols-4 lg:gap-6 lg:overflow-visible lg:px-0 lg:pb-0">
                    @foreach ($category->products as $product)
                        @php
                            $image = $product->getFirstMediaUrl('images');
                            $productSlug = $product->getTranslation('slug', $locale, false)
                                ?: $product->getTranslation('slug', 'fr', false);
                            $productUrl = $productSlug
                                ? route('shop.product', ['slug' => $productSlug])
                                : $categoryUrl;
                        @endphp

                        <article class="group w-[40%] shrink-0 snap-start sm:w-2/5 lg:w-auto">
                            <a href="{{ $productUrl }}" class="block">
                                @if ($image)
                                    <div class="relative aspect-[3/4] overflow-hidden rounded-3xl">
                                        @if ($product->is_new)
                                            <span class="absolute left-3 top-3 z-10 rounded-full bg-[#DDA1AA] px-3 py-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-900 sm:left-4 sm:top-4 sm:px-4 sm:py-2 sm:text-xs">
                                                {{ __('Nouveau') }}
                                            </span>
                                        @endif

                                        <img
                                            src="{{ $image }}"
                                            alt="{{ $product->name }}"
                                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                            loading="lazy"
                                        >
                                    </div>
                                @endif

                                <div class="pt-4">
                                    @if ($product->tag)
                                        <p class="text-[9px] font-semibold uppercase tracking-widest text-[#A9636F]">
                                            {{ $product->tag }}
                                        </p>
                                    @endif

                                    <div class="mt-2 flex items-center justify-between gap-4">
                                        <h3 class="font-medium text-sm md:text-base leading-tight text-zinc-900">
                                            {{ $product->name }}
                                        </h3>

                                        <div class="shrink-0 text-xs md:text-sm font-semibold text-zinc-900">
                                            @if ($product->price)
                                                {{ $product->formatted_price }} €
                                            @elseif ($product->price_min && $product->price_max)
                                                {{ $product->formatted_price_min }}–{{ $product->formatted_price_max }} €
                                            @elseif ($product->price_min)
                                                {{ __('Dès :price €', ['price' => $product->formatted_price_min]) }}
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

     @if ($events->isNotEmpty())
        @php
            $eventLocale = in_array(app()->getLocale(), ['fr', 'en', 'ro'], true)
                ? app()->getLocale()
                : 'fr';

            $eventTypes = $events->pluck('type')->filter()->unique()->values();

            $eventTypeLabels = [
                'training' => __('Formations'),
                'workshop' => __('Workshops'),
                'international_tour' => __('Tournée internationale'),
                'event' => __('Événements'),
            ];
        @endphp

        <section id="events" class="pb-16 sm:px-5 sm:pb-20 lg:px-8 lg:pb-24">
            <div
                x-data="{ filter: 'all' }"
                class="mx-auto max-w-[1560px] rounded-3xl bg-white px-6 py-12 sm:px-10 sm:py-14 lg:px-14 lg:py-16 xl:px-16"
            >
                <div class="grid gap-8 lg:grid-cols-12 lg:items-end">
                    <div class="lg:col-span-7">
                        <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                            {{ __('Événements BLACK MILK') }}
                        </p>

                        <h2 class="mt-4 max-w-4xl font-serif text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl">
                            {{ __('Les prochains') }}
                            <span class="italic text-[#DDA1AA]">{{ __('rendez-vous') }}</span>
                        </h2>
                    </div>

                    <p class="max-w-xl text-base leading-7 text-zinc-600 lg:col-span-5 lg:justify-self-end lg:text-lg">
                        {{ __('Formations, workshops et rencontres BLACK MILK en France et à l’international.') }}
                    </p>
                </div>

                <div class="-mx-2 mt-9 flex gap-2 overflow-x-auto px-2 pb-2">
                    <button
                        type="button"
                        @click="filter = 'all'"
                        :class="filter === 'all' ? 'bg-[#DDA1AA] text-zinc-900' : 'bg-[#F6ECEE] text-zinc-600 hover:bg-[#EFDDE0]'"
                        class="shrink-0 rounded-full px-5 py-3 text-xs font-semibold uppercase tracking-widest transition"
                    >
                        {{ __('Tous') }}
                    </button>

                    @foreach ($eventTypes as $type)
                        <button
                            type="button"
                            @click="filter = '{{ $type }}'"
                            :class="filter === '{{ $type }}' ? 'bg-[#DDA1AA] text-zinc-900' : 'bg-[#F6ECEE] text-zinc-600 hover:bg-[#EFDDE0]'"
                            class="shrink-0 rounded-full px-5 py-3 text-xs font-semibold uppercase tracking-widest transition"
                        >
                            {{ $eventTypeLabels[$type] ?? $type }}
                        </button>
                    @endforeach
                </div>

                <div class="mt-8 space-y-3">
                    @foreach ($events as $event)
                        @php
                            $eventName = $event->getTranslation('name', $eventLocale, false)
                                ?: $event->getTranslation('name', 'fr', false);

                            $eventDescription = $event->getTranslation('description', $eventLocale, false)
                                ?: $event->getTranslation('description', 'fr', false);

                            $eventLocation = collect([$event->city, $event->country])
                                ->filter(fn ($value) => filled($value))
                                ->reject(fn ($value) => in_array(mb_strtolower(trim($value)), [
                                    'ville à venir',
                                    'city to come',
                                    'oraș de anunțat',
                                    'lieu à venir',
                                    'venue to come',
                                    'loc de anunțat',
                                    '—',
                                ], true))
                                ->join(', ');

                            $startDay = null;
                            $startMonth = null;
                            $startYear = null;
                            $endLabel = null;

                            if ($event->starts_at) {
                                $start = $event->starts_at->copy()->locale($eventLocale);
                                $startDay = $start->format('d');
                                $startMonth = $start->translatedFormat('M');
                                $startYear = $start->format('Y');

                                if ($event->ends_at && ! $event->ends_at->isSameDay($event->starts_at)) {
                                    $end = $event->ends_at->copy()->locale($eventLocale);

                                    $endLabel = $end->isSameMonth($event->starts_at)
                                        ? $end->translatedFormat('d M')
                                        : $end->translatedFormat('d M Y');
                                }
                            }
                        @endphp

                        <article
                            x-cloak
                            x-show="filter === 'all' || filter === '{{ $event->type }}'"
                            class="grid gap-6 rounded-2xl bg-[#FCF8F4] p-6 sm:p-7 lg:grid-cols-12 lg:items-center"
                        >
                            <div class="lg:col-span-2">
                                @if ($event->starts_at)
                                    <div class="flex items-end gap-3">
                                        <span class="font-serif text-6xl font-medium leading-none tracking-tight text-[#DDA1AA]">
                                            {{ $startDay }}
                                        </span>

                                        <div class="pb-1">
                                            <p class="text-sm font-semibold leading-none text-zinc-800">
                                                {{ $startMonth }}
                                            </p>

                                            <p class="mt-1 text-xs text-zinc-400">
                                                {{ $startYear }}
                                            </p>

                                            @if ($endLabel)
                                                <p class="mt-2 text-xs font-medium text-[#A9636F]">
                                                    — {{ $endLabel }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <p class="font-serif text-2xl font-medium leading-tight text-[#DDA1AA]">
                                        {{ __('Date à venir') }}
                                    </p>
                                @endif

                                <p class="mt-4 text-[11px] font-semibold uppercase tracking-widest text-zinc-400">
                                    {{ $eventTypeLabels[$event->type] ?? $event->type }}
                                </p>
                            </div>

                            <div class="lg:col-span-5">
                                <h3 class="font-serif text-xl font-medium leading-tight text-zinc-900 sm:text-2xl">
                                    {{ $eventName }}
                                </h3>

                                @if ($eventDescription)
                                    <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-600">
                                        {{ $eventDescription }}
                                    </p>
                                @endif
                            </div>

                            <div class="space-y-3 lg:col-span-3">
                                @if ($eventLocation)
                                    <div class="flex items-start gap-2 text-sm leading-6 text-zinc-600">
                                        <x-lucide-map-pin class="mt-1 h-4 w-4 shrink-0 text-[#A9636F]" />
                                        <span>{{ $eventLocation }}</span>
                                    </div>
                                @endif

                                @if ($event->address)
                                    <div class="flex items-start gap-2 text-sm leading-6 text-zinc-500">
                                        <x-lucide-navigation class="mt-1 h-4 w-4 shrink-0 text-[#A9636F]" />
                                        <span>{{ $event->address }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center justify-between gap-4 lg:col-span-2 lg:flex-col lg:items-end">
                                @if ($event->price !== null)
                                    <p class="text-sm font-semibold text-zinc-900">
                                        {{ $event->formatted_price }} €
                                    </p>
                                @endif

                                @if ($event->ticket_url)
                                    <a
                                        href="{{ $event->ticket_url }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="group inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-[#A9636F] transition hover:text-[#945763]"
                                    >
                                        {{ __('Billets') }}
                                        <x-lucide-arrow-up-right class="h-4 w-4 transition-transform group-hover:-translate-y-1 group-hover:translate-x-1" />
                                    </a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif


    <section class="px-3 pb-6 pt-8 sm:px-5 sm:pb-8 sm:pt-12 lg:px-8 lg:pb-10 lg:pt-16">
        <div class="mx-auto max-w-[1560px] rounded-3xl bg-[#EFDDE0] px-6 py-20 text-center sm:px-10 sm:py-24 lg:px-16 lg:py-28">
            <div class="mx-auto max-w-4xl">
                <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                    {{ __('BLACK MILK Journal') }}
                </p>

                <h2 class="mt-5 font-serif text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl">
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
