@props(['course', 'mode' => 'online', 'owned' => false])

@php
    $isLive = $mode === 'live';
    $subject = rawurlencode('Formation BLACK MILK — '.$course->name);
    $price = $course->price !== null
        ? number_format((float) $course->price, 0, ',', "\u{202F}")."\u{00A0}€"
        : __('Sur demande');
    $pdfUrl = $course->infoPdfUrl();
    $points = $course->points ?: [];
@endphp

<article id="{{ $mode }}-{{ $course->slug }}" class="group flex h-full flex-col overflow-hidden rounded-3xl bg-white" data-blur-reveal-item>
    <div class="relative aspect-[5/4] overflow-hidden bg-[#EFDDE0]">
        @if ($course->image)
            <img
                src="{{ asset('img/formations/'.$course->image) }}"
                alt="{{ $course->name }}"
                width="1000"
                height="800"
                loading="lazy"
                decoding="async"
                class="h-full w-full object-cover object-[50%_22%] transition duration-500 group-hover:scale-105"
            >
        @else
            <div class="flex h-full flex-col items-center justify-center px-8 text-center">
                <x-lucide-sparkles class="mb-4 h-7 w-7 text-bmlk-brand" stroke-width="1.2" />
                <span class="font-serif text-3xl font-medium leading-tight text-bmlk-brand">{{ $course->name }}</span>
            </div>
        @endif

        <span class="absolute left-3 top-3 rounded-full bg-[#DDA1AA] px-3 py-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-900 sm:left-4 sm:top-4 sm:px-4 sm:py-2 sm:text-xs">
            {{ $isLive ? __('En présentiel') : __('En ligne') }}
        </span>
    </div>

    <div class="flex flex-1 flex-col p-6 sm:p-7">
        <p class="text-xs font-semibold uppercase tracking-widest text-bmlk-brand">{{ $course->tag }}</p>

        <h3 class="mt-3 font-serif text-2xl font-medium leading-tight text-zinc-900 sm:text-3xl">{{ $course->name }}</h3>

        <p class="mt-3 text-sm leading-6 text-zinc-600">{{ $course->description }}</p>

        @if ($points)
            <ul class="mt-5 space-y-2.5 border-t border-zinc-100 pt-5">
                @foreach ($points as $point)
                    <li class="flex items-start gap-2.5 text-sm leading-5 text-zinc-600">
                        <x-lucide-check class="mt-0.5 h-4 w-4 shrink-0 text-bmlk-brand" stroke-width="1.7" />
                        <span>{{ $point }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-auto pt-7">
            <div class="flex items-end justify-between gap-4 border-t border-zinc-100 pt-5">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-zinc-400">{{ __('Tarif de la formation') }}</p>
                    <p class="mt-1.5 font-serif text-3xl font-medium leading-none text-zinc-900">{{ $price }}</p>
                </div>

                @if ($course->programmeData() && ! $isLive)
                    <a href="#programme" class="shrink-0 text-xs font-semibold uppercase tracking-widest text-bmlk-brand transition hover:text-bmlk-brand-hover">
                        {{ __('Programme') }}
                    </a>
                @endif
            </div>

            @if ($owned)
                <a href="{{ route('account.courses') }}" class="mt-5 inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-bmlk-brand px-5 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#945763]">
                    <x-lucide-circle-check class="h-4 w-4" />{{ __('Accéder à ma formation') }}
                </a>
            @elseif ($course->isPurchasable())
                <form method="POST" action="{{ route('courses.buy', ['slug' => $course->slug]) }}" class="mt-5">
                    @csrf
                    <button type="submit" class="group/cta inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-accent px-5 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-accent/90">
                        {{ __('Acheter maintenant') }}
                        <x-lucide-arrow-right class="h-4 w-4 transition-transform group-hover/cta:translate-x-1" />
                    </button>
                </form>
            @else
                <a href="mailto:office.blackmilk@gmail.com?subject={{ $subject }}" class="group/cta mt-5 inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-accent px-5 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-accent/90">
                    {{ __('Demander des informations') }}
                    <x-lucide-arrow-up-right class="h-4 w-4 transition-transform group-hover/cta:-translate-y-0.5 group-hover/cta:translate-x-0.5" />
                </a>
            @endif

            @if ($pdfUrl)
                <a href="{{ $pdfUrl }}" target="_blank" rel="noopener" class="mt-3 inline-flex w-full items-center justify-center gap-2 text-xs font-semibold uppercase tracking-widest text-bmlk-brand transition hover:text-bmlk-brand-hover">
                    <x-lucide-file-text class="h-4 w-4" />{{ __('Fiche de la formation (PDF)') }}
                </a>
            @endif
        </div>
    </div>
</article>
