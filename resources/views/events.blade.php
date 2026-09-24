<x-layout
    :title="$metaTitle"
    :meta-description="$metaDescription"
    :canonical="$canonical"
>
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

    <main>
        <section class="px-3 pb-5 pt-5 sm:px-5 sm:pb-10 lg:px-8">
            <div class="mx-auto max-w-[1560px] overflow-hidden rounded-3xl bg-[#EFDDE0]">
                <div class="grid gap-12 px-7 py-12 sm:px-10 sm:py-16 lg:grid-cols-12 lg:px-14 lg:py-20 xl:px-16">
                    <div class="lg:col-span-8">
                        <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]" data-reveal data-reveal-y="10" data-reveal-duration="0.7">
                            {{ __('Événements BLACK MILK') }}
                        </p>

                        <h1 class="mt-6 max-w-5xl font-serif text-5xl font-medium leading-none tracking-tight text-zinc-900 sm:text-6xl lg:text-7xl xl:text-8xl" data-reveal-title data-reveal-title-duration="1.05" data-reveal-title-stagger="0.08">
                            <span class="inline-block">{{ __('Les prochains') }}</span>
                            <span class="inline-block italic text-[#A9636F]">{{ __('rendez-vous') }}</span>
                        </h1>
                    </div>

                    <div class="flex items-end lg:col-span-4">
                        <div class="max-w-md">
                            <p class="text-base leading-7 text-zinc-700 sm:text-lg" data-reveal data-reveal-y="16" data-reveal-duration="0.8" data-reveal-delay="0.14">
                                {{ __('Formations, workshops et rencontres BLACK MILK en France et à l’international.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="px-3 pb-20 sm:px-5 sm:pb-24 lg:px-8 lg:pb-28">
            <div
                x-data="{ filter: 'all' }"
                class="mx-auto max-w-[1560px] rounded-3xl bg-white px-6 py-10 sm:px-10 sm:py-12 lg:px-14 lg:py-14 xl:px-16"
            >
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]" data-reveal data-reveal-y="10" data-reveal-duration="0.7">
                            {{ __('Calendrier') }}
                        </p>

                        <h2 class="mt-3 font-serif text-3xl font-medium leading-none tracking-tight text-zinc-900 sm:text-4xl" data-reveal-title data-reveal-title-duration="0.9">
                            {{ __('Choisis ton prochain rendez-vous') }}
                        </h2>
                    </div>

                    <p class="text-sm leading-6 text-zinc-500" data-reveal data-reveal-y="12" data-reveal-duration="0.75" data-reveal-delay="0.12">
                        {{ __('Les événements les plus proches sont affichés en premier.') }}
                    </p>
                </div>

                @if ($events->isNotEmpty())
                    <div class="-mx-2 mt-8 flex gap-2 overflow-x-auto px-2 pb-2" data-blur-reveal data-blur-stagger="0.07" data-blur-duration="0.7" data-blur-y="10" data-blur-pixels="6">
                        <button
                            type="button"
                            @click="filter = 'all'; $nextTick(() => $dispatch('motion-filter-reveal'))"
                            :class="filter === 'all' ? 'bg-[#DDA1AA] text-zinc-900' : 'bg-[#F6ECEE] text-zinc-600 hover:bg-[#EFDDE0]'"
                            class="shrink-0 rounded-full px-5 py-3 text-xs font-semibold uppercase tracking-widest transition"
                            data-blur-reveal-item
                        >
                            {{ __('Tous') }}
                        </button>

                        @foreach ($eventTypes as $type)
                            <button
                                type="button"
                                @click="filter = '{{ $type }}'; $nextTick(() => $dispatch('motion-filter-reveal'))"
                                :class="filter === '{{ $type }}' ? 'bg-[#DDA1AA] text-zinc-900' : 'bg-[#F6ECEE] text-zinc-600 hover:bg-[#EFDDE0]'"
                                class="shrink-0 rounded-full px-5 py-3 text-xs font-semibold uppercase tracking-widest transition"
                                data-blur-reveal-item
                            >
                                {{ $eventTypeLabels[$type] ?? $type }}
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-8 space-y-3" data-filter-reveal data-filter-reveal-stagger="0.08" data-filter-reveal-duration="0.78" data-filter-reveal-y="18" data-filter-reveal-blur="8" data-filter-reveal-amount="0.12" data-filter-reveal-per-item="true">
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
                                class="grid gap-7 rounded-2xl bg-[#FCF8F4] p-6 sm:p-8 lg:grid-cols-12 lg:items-center"
                                data-filter-reveal-item
                            >
                                <div class="lg:col-span-2">
                                    @if ($event->starts_at)
                                        <div class="flex items-end gap-3">
                                            <span class="font-serif text-6xl font-medium leading-none tracking-tight text-[#DDA1AA] sm:text-7xl">
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
                                    <h3 class="font-serif text-2xl font-medium leading-tight text-zinc-900 sm:text-3xl">
                                        {{ $eventName }}
                                    </h3>

                                    @if ($eventDescription)
                                        <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-600 sm:text-base sm:leading-7">
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
                                            {{ number_format((float) $event->price, 2, ',', ' ') }} €
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
                @else
                    <div class="mt-10 rounded-2xl bg-[#FCF8F4] px-6 py-16 text-center sm:px-10">
                        <p class="font-serif text-3xl font-medium text-zinc-900">
                            {{ __('De nouvelles dates arrivent bientôt') }}
                        </p>

                        <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-zinc-500">
                            {{ __('Le prochain calendrier BLACK MILK sera publié ici.') }}
                        </p>
                    </div>
                @endif
            </div>
        </section>
    </main>
</x-layout>
