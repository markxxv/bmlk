@props(['settings', 'copy', 'preview' => false])

<div {{ $attributes->class(['home-hero-visual relative mx-auto flex aspect-square w-full items-center justify-center']) }}>
    <img src="{{ asset('img/blackmilk_medallion.svg') }}" alt="" aria-hidden="true" draggable="false" class="pointer-events-none absolute left-1/2 top-[54%] h-auto w-[79.8%] -translate-x-1/2 -translate-y-1/2 select-none">

    @if ($preview || $settings['badge_enabled'])
        <div data-home-badge @if (! $settings['badge_enabled']) hidden @endif class="home-hero-medallion-ring" aria-hidden="true"></div>
    @endif

    <div class="relative z-10 w-[80%]">
        @if ($preview)
            <img src="{{ asset('img/hero_cow_mascot.webp') }}" alt="" class="mx-auto block h-auto w-full max-w-[496px]">
        @else
            <x-hero-mascot class="w-full" />
        @endif
    </div>

    @if ($preview || $settings['badge_enabled'])
        <div data-home-badge @if (! $settings['badge_enabled']) hidden @endif class="home-hero-campaign-medallion" @unless ($preview) aria-hidden="true" @endunless>
            <span data-home-field="badge" class="home-hero-medallion-caption">{{ $copy['badge'] }}</span>
            <span data-home-field="badge_percent" class="home-hero-medallion-value">−{{ $settings['badge_percent'] }}&nbsp;%</span>
        </div>
    @endif
</div>
