@props(['course', 'mode' => 'online'])

@php
    $isLive = $mode === 'live';
    $subject = rawurlencode('Formation BLACK MILK — '.$course['name']);
@endphp

<article id="{{ $mode }}-{{ $course['slug'] }}" class="group flex h-full flex-col overflow-hidden rounded-3xl bg-white" data-blur-reveal-item>
    <div class="relative aspect-[16/11] overflow-hidden bg-[#F6ECEE]">
        @if ($course['image'])
            <img
                src="{{ asset('img/formations/'.$course['image']) }}"
                alt="{{ __($course['name']) }}"
                loading="lazy"
                decoding="async"
                class="h-full w-full object-contain p-5 transition-transform duration-700 group-hover:scale-105 sm:p-7"
            >
        @else
            <div class="flex h-full flex-col items-center justify-center bg-[#EFDDE0] px-6 text-center">
                <x-lucide-sparkles class="mb-4 h-7 w-7 text-bmlk-brand" stroke-width="1.2" />
                <span class="max-w-56 font-serif text-3xl leading-tight text-bmlk-brand">{{ __($course['name']) }}</span>
            </div>
        @endif
        <span class="absolute left-4 top-4 rounded-full bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-bmlk-brand">
            {{ $isLive ? __('En présentiel') : __('En ligne') }}
        </span>
    </div>

    <div class="flex flex-1 flex-col p-6 sm:p-7">
        <p class="text-xs font-semibold uppercase tracking-wider text-bmlk-brand">{{ __($course['tag']) }}</p>
        <h3 class="mt-3 font-serif text-2xl font-medium leading-tight text-zinc-900 sm:text-3xl">{{ __($course['name']) }}</h3>
        <p class="mt-4 text-sm leading-6 text-zinc-600">{{ __($course['description']) }}</p>

        @if ($course['points'])
            <ul class="mt-6 space-y-2.5">
                @foreach (array_slice($course['points'], 0, 4) as $point)
                    <li class="flex items-start gap-2.5 text-sm leading-5 text-zinc-600">
                        <x-lucide-check class="mt-0.5 h-4 w-4 shrink-0 text-bmlk-brand" stroke-width="1.7" />
                        <span>{{ __($point) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-auto pt-7">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-medium text-zinc-500">{{ __('Tarif de la formation') }}</p>
                    <p class="mt-1 font-serif text-3xl text-zinc-900">
                        {{ $course['price'] !== null ? number_format($course['price'], 0, ',', ' ').' €' : __('Sur demande') }}
                    </p>
                </div>
                @if ($course['slug'] === 'wax-manicure' && ! $isLive)
                    <a href="#programme" class="text-xs font-semibold text-bmlk-brand underline underline-offset-4">{{ __('Voir le programme') }}</a>
                @endif
            </div>

            <a href="mailto:office.blackmilk@gmail.com?subject={{ $subject }}" class="group/cta mt-5 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-bmlk-brand px-5 text-center text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#945763]">
                {{ __('Demander des informations') }}
                <x-lucide-arrow-up-right class="h-4 w-4 transition-transform group-hover/cta:-translate-y-0.5 group-hover/cta:translate-x-0.5" />
            </a>
        </div>
    </div>
</article>
