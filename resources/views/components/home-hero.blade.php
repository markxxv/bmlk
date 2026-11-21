@props(['settings', 'copy', 'preview' => false])

<section data-home-hero data-theme="{{ $settings['theme'] }}" data-campaign="{{ $settings['badge_enabled'] ? 'true' : 'false' }}" class="home-hero {{ $preview ? 'home-hero-preview' : '' }} relative z-[41] -mt-[92px] overflow-hidden pb-6 pt-[92px] sm:-mt-[100px] sm:pb-8 sm:pt-[100px] lg:-mt-[104px] lg:pb-10 lg:pt-[104px]">
    <div class="home-hero-content relative z-10 mx-auto max-w-[1560px] px-5 pt-3 sm:px-5 sm:pt-5 lg:px-8 lg:pt-6">
        <div class="relative lg:min-h-[720px] xl:min-h-[760px]">
            <div class="relative z-10 grid h-full lg:min-h-[720px] lg:grid-cols-12 xl:min-h-[760px]">
                <div class="home-hero-copy-column flex min-w-0 flex-col pb-7 pt-7 sm:px-10 sm:pb-10 sm:pt-12 lg:col-span-6 lg:px-14 lg:pb-10 lg:pt-14 xl:px-16 xl:pt-16">
                    <div class="flex items-center gap-2 sm:gap-3">
                        <span class="h-px w-8 shrink-0 bg-[var(--bmc-accent)] sm:w-10" data-line-reveal data-line-reveal-duration="0.8"></span>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-[color:var(--bmc-accent)] sm:text-xs sm:tracking-widest" data-reveal data-reveal-y="10" data-reveal-duration="0.7" data-reveal-delay="0.08">
                            <span data-home-field="eyebrow">{{ $copy['eyebrow'] }}</span>
                        </p>
                    </div>

                    <div class="home-hero-copy-flow flex flex-1 items-center sm:py-16 lg:py-10">
                        <div class="min-w-0 w-full">
                            <h1 class="max-w-2xl break-words pt-10 font-serif text-4xl font-medium leading-[1.06] tracking-tight text-[color:var(--bmc-accent)] sm:text-6xl md:pt-0" data-reveal-title data-reveal-title-duration="1.05" data-reveal-title-stagger="0.08">
                                <span class="block" data-reveal-title-skip>
                                    <span class="home-hero-script" data-home-field="headline_script">{{ $copy['headline_script'] }}</span>
                                </span>
                                <span class="block" data-home-field="headline">{{ $copy['headline'] }}</span>
                            </h1>

                            @if ($preview || $settings['badge_enabled'])
                                <div data-home-badge @if (! $settings['badge_enabled']) hidden @endif class="home-hero-offer">
                                    <span data-home-field="badge" class="home-hero-offer-caption">{{ $copy['badge'] }}</span>
                                    <span data-home-field="badge_percent" class="home-hero-offer-value">−{{ $settings['badge_percent'] }}&nbsp;%</span>
                                </div>
                            @endif

                            <p class="mt-5 max-w-lg text-sm leading-6 text-[color:var(--bmc-accent)] sm:mt-7 sm:text-lg sm:leading-7" data-reveal data-reveal-y="18" data-reveal-duration="0.85" data-reveal-delay="0.18">
                                <span data-home-field="description">{{ $copy['description'] }}</span>
                            </p>

                            @if ($preview || ($settings['badge_enabled'] && $copy['badge_note'] !== ''))
                                <p data-home-offer-note @if (! $settings['badge_enabled'] || $copy['badge_note'] === '') hidden @endif class="home-hero-offer-note">
                                    <span data-home-field="badge_note">{{ $copy['badge_note'] }}</span>
                                </p>
                            @endif

                            <div class="relative z-10 mt-6 flex flex-col gap-2 sm:mt-9 sm:flex-row sm:flex-wrap sm:gap-3" data-blur-reveal data-blur-duration="0.75" data-blur-delay="0.28" data-blur-y="14" data-blur-pixels="8">
                                <a href="{{ route('shop.index') }}" class="group inline-flex min-h-12 max-w-full w-full items-center justify-between gap-3 home-hero-primary rounded-full px-5 py-2 text-xs font-semibold uppercase tracking-wider transition hover:opacity-90 sm:w-auto sm:justify-center sm:px-6 sm:tracking-widest">
                                    <span data-home-field="primary_button">{{ $copy['primary_button'] }}</span>
                                    <x-lucide-arrow-right class="h-4 w-4 shrink-0 transition-transform group-hover:translate-x-1" />
                                </a>

                                <a href="{{ route('where-to-buy') }}" class="inline-flex min-h-12 max-w-full w-full items-center justify-center home-hero-secondary rounded-full px-5 py-2 text-xs font-semibold uppercase tracking-wider transition hover:opacity-90 sm:w-auto sm:px-6 sm:tracking-widest">
                                    <span data-home-field="secondary_button">{{ $copy['secondary_button'] }}</span>
                                </a>
                            </div>

                            <div class="relative mx-auto mt-2 w-full lg:hidden">
                                <x-home-hero-visual :settings="$settings" :copy="$copy" :preview="$preview" class="max-w-[400px] sm:max-w-[500px]" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 md:border-t md:border-[var(--hero-rule)] md:pt-5 sm:gap-5 lg:gap-4" data-blur-reveal data-blur-stagger="0.1" data-blur-duration="0.75" data-blur-y="12" data-blur-pixels="7">
                        <div class="flex items-start gap-3" data-blur-reveal-item>
                            <span data-home-icon="feature_one" data-icon-name="{{ $settings['benefit_icons']['feature_one'] }}" class="mt-0.5 h-4 w-4 shrink-0 text-[color:var(--bmc-accent)] sm:h-5 sm:w-5" @unless ($preview) aria-hidden="true" @endunless>{{ svg('lucide-'.$settings['benefit_icons']['feature_one'], 'h-full w-full', ['stroke-width' => 1.4]) }}</span>
                            <div>
                                <p class="text-[9px] font-semibold text-[color:var(--bmc-accent)] sm:text-[11px] sm:tracking-widest"><span data-home-field="feature_one">{{ $copy['feature_one'] }}</span></p>
                                <p class="mt-1 hidden text-[10px] text-[color:var(--bmc-accent)] sm:block"><span data-home-field="feature_one_detail">{{ $copy['feature_one_detail'] }}</span></p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3" data-blur-reveal-item>
                            <span data-home-icon="feature_two" data-icon-name="{{ $settings['benefit_icons']['feature_two'] }}" class="mt-0.5 h-4 w-4 shrink-0 text-[color:var(--bmc-accent)] sm:h-5 sm:w-5" @unless ($preview) aria-hidden="true" @endunless>{{ svg('lucide-'.$settings['benefit_icons']['feature_two'], 'h-full w-full', ['stroke-width' => 1.4]) }}</span>
                            <div>
                                <p class="text-[9px] font-semibold text-[color:var(--bmc-accent)] sm:text-[11px] sm:tracking-widest"><span data-home-field="feature_two">{{ $copy['feature_two'] }}</span></p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3" data-blur-reveal-item>
                            <span data-home-icon="feature_three" data-icon-name="{{ $settings['benefit_icons']['feature_three'] }}" class="mt-0.5 h-4 w-4 shrink-0 text-[color:var(--bmc-accent)] sm:h-5 sm:w-5" @unless ($preview) aria-hidden="true" @endunless>{{ svg('lucide-'.$settings['benefit_icons']['feature_three'], 'h-full w-full', ['stroke-width' => 1.4]) }}</span>
                            <div>
                                <p class="text-[9px] font-semibold text-[color:var(--bmc-accent)] sm:text-[11px] sm:tracking-widest"><span data-home-field="feature_three">{{ $copy['feature_three'] }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="home-hero-desktop-visual pointer-events-none absolute inset-y-0 left-1/2 z-0 hidden w-[clamp(700px,54vw,1100px)] items-center lg:flex">
        <x-home-hero-visual :settings="$settings" :copy="$copy" :preview="$preview" class="max-w-[620px]" />
    </div>
    <div class="bmlk-packaging-stripe pointer-events-none absolute inset-x-0 bottom-0 z-20" aria-hidden="true"></div>
</section>
