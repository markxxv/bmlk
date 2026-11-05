<x-layout
    :title="$metaTitle"
    :meta-description="$metaDescription"
    :canonical="$canonical"
    :og-image="asset('img/elena.webp')"
>
    <main>
        {{-- Hero --}}
        <section class="px-3 pb-5 pt-5 sm:px-5 sm:pb-10 lg:px-8">
            <div class="relative mx-auto max-w-[1560px] overflow-hidden rounded-3xl bg-[#EFDDE0]">
                <div class="grid lg:min-h-[640px] lg:grid-cols-12">
                    <div class="flex flex-col justify-between px-7 pb-14 pt-12 sm:px-10 sm:pt-16 lg:col-span-7 lg:px-14 lg:pb-16 lg:pt-20 xl:px-16">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="h-px w-8 shrink-0 bg-zinc-800 sm:w-10" data-line-reveal data-line-reveal-duration="0.8"></span>
                                <p class="text-xs font-semibold uppercase tracking-widest text-zinc-800" data-reveal data-reveal-y="10" data-reveal-duration="0.7" data-reveal-delay="0.08">
                                    {{ __('Smirnova School · BLACK MILK') }}
                                </p>
                            </div>

                            <h1 class="mt-7 max-w-3xl font-serif text-5xl font-medium leading-[0.95] tracking-tight text-zinc-900 sm:text-6xl lg:text-7xl xl:text-8xl" data-reveal-title data-reveal-title-duration="1.05" data-reveal-title-stagger="0.08">
                                <span class="block">{{ __('Apprenez') }}</span>
                                <span class="block italic text-white">{{ __('à la source') }}</span>
                            </h1>

                            <p class="mt-6 max-w-xl text-base leading-7 text-zinc-800 sm:text-lg sm:leading-8" data-reveal data-reveal-y="18" data-reveal-duration="0.85" data-reveal-delay="0.18">
                                {{ __('Les formations d’Elena Smirnova pour maîtriser la technique à la cire : en ligne, à votre rythme, ou en présentiel, directement auprès d’elle.') }}
                            </p>

                            <div class="mt-8 flex flex-wrap gap-3" data-blur-reveal data-blur-duration="0.75" data-blur-delay="0.28" data-blur-y="14" data-blur-pixels="8">
                                <a href="#online" class="group inline-flex h-12 items-center justify-center gap-3 rounded-full bg-accent px-6 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-accent/90">
                                    {{ __('Cours en ligne') }}
                                    <x-lucide-chevron-down class="h-4 w-4 transition-transform group-hover:translate-y-0.5" />
                                </a>

                                <a href="#presentiel" class="inline-flex h-12 items-center justify-center rounded-full bg-white px-6 text-xs font-semibold uppercase tracking-widest text-zinc-900 transition hover:bg-zinc-50">
                                    {{ __('Formations en présentiel') }}
                                </a>
                            </div>
                        </div>

                        <div class="mt-12 grid grid-cols-1 gap-4 border-t border-[#C98792] pt-6 sm:grid-cols-3 sm:gap-5" data-blur-reveal data-blur-stagger="0.1" data-blur-duration="0.75" data-blur-y="12" data-blur-pixels="7">
                            <div class="flex items-center gap-3" data-blur-reveal-item>
                                <x-lucide-graduation-cap class="h-5 w-5 shrink-0 text-zinc-800" stroke-width="1.4" />
                                <p class="text-[11px] font-semibold tracking-widest text-zinc-800">{{ __('Accès à vie aux cours en ligne') }}</p>
                            </div>

                            <div class="flex items-center gap-3" data-blur-reveal-item>
                                <x-lucide-badge-check class="h-5 w-5 shrink-0 text-zinc-800" stroke-width="1.4" />
                                <p class="text-[11px] font-semibold tracking-widest text-zinc-800">{{ __('Certificat de fin de formation') }}</p>
                            </div>

                            <div class="flex items-center gap-3" data-blur-reveal-item>
                                <x-lucide-map-pin class="h-5 w-5 shrink-0 text-zinc-800" stroke-width="1.4" />
                                <p class="text-[11px] font-semibold tracking-widest text-zinc-800">{{ __('Sessions en présentiel avec Elena') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="relative min-h-[420px] overflow-hidden sm:min-h-[520px] lg:col-span-5 lg:min-h-0" data-parallax data-parallax-distance="10" data-parallax-scale="1.12">
                        <img
                            src="{{ asset('img/elena.webp') }}"
                            alt="{{ __('Elena Smirnova, fondatrice de BLACK MILK') }}"
                            width="1000"
                            height="1502"
                            fetchpriority="high"
                            class="absolute inset-0 h-full w-full object-cover object-[50%_20%]"
                            data-parallax-image
                        >
                    </div>
                </div>

                <div class="bmlk-packaging-stripe pointer-events-none absolute inset-x-0 bottom-0 z-20" aria-hidden="true"></div>
            </div>
        </section>

        {{-- Online Education --}}
        <section id="online" class="scroll-mt-8 overflow-hidden px-3 py-16 sm:px-5 sm:py-20 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-[1560px]">
                <div class="grid gap-6 lg:grid-cols-2 lg:items-end">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-bmlk-brand" data-reveal data-reveal-y="10" data-reveal-duration="0.7">
                            {{ __('À votre rythme') }}
                        </p>

                        <h2 class="mt-4 font-serif text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl" data-reveal-title data-reveal-title-duration="1" data-reveal-title-stagger="0.08">
                            <span class="inline-block">{{ __('Online') }}</span>
                            <span class="inline-block italic text-bmlk-brand">{{ __('Education') }}</span>
                        </h2>
                    </div>

                    <p class="max-w-xl text-base leading-7 text-zinc-600 lg:justify-self-end lg:text-lg" data-reveal data-reveal-y="16" data-reveal-duration="0.8" data-reveal-delay="0.16">
                        {{ __('Les cours en ligne d’Elena Smirnova, accessibles partout et à vie. Idéal pour maîtriser la technique à la cire depuis votre cabine.') }}
                    </p>
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-2 sm:gap-5 lg:mt-12 lg:grid-cols-3 lg:gap-8" data-blur-reveal data-blur-stagger="0.1" data-blur-duration="0.8" data-blur-y="20" data-blur-pixels="9">
                    @foreach ($onlineCourses as $course)
                        <x-formation-card :course="$course" />
                    @endforeach

                    @if (count($onlineCourses) % 6 === 5)
                        <div class="flex flex-col justify-between gap-10 rounded-3xl bg-accent p-7 text-white sm:p-9" data-blur-reveal-item>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-[#EFDDE0]">{{ __('Votre parcours BLACK MILK') }}</p>
                                <h3 class="mt-4 font-serif text-3xl font-medium leading-tight sm:text-4xl">
                                    {{ __('Quelle formation') }} <span class="italic text-[#DDA1AA]">{{ __('choisir ?') }}</span>
                                </h3>
                                <p class="mt-4 max-w-sm text-sm leading-6 text-[#F6ECEE]">{{ __('Écrivez-nous : nous vous aidons à choisir le programme adapté à votre expérience et à vos objectifs.') }}</p>
                            </div>

                            <a href="mailto:office.blackmilk@gmail.com?subject={{ rawurlencode('Informations formations BLACK MILK') }}" class="group inline-flex h-12 items-center justify-center gap-3 self-start rounded-full bg-white px-6 text-xs font-semibold uppercase tracking-widest text-zinc-900 transition hover:bg-zinc-50">
                                {{ __('Nous contacter') }}
                                <x-lucide-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- Wax Manicure : programme détaillé --}}
        <section id="programme" class="scroll-mt-8 px-3 pb-16 sm:px-5 sm:pb-20 lg:px-8 lg:pb-24">
            <div class="mx-auto max-w-[1560px] rounded-3xl bg-white px-6 py-12 sm:px-10 sm:py-14 lg:px-14 lg:py-16 xl:px-16">
                <div class="grid gap-6 lg:grid-cols-2 lg:items-end">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-bmlk-brand" data-reveal data-reveal-y="10" data-reveal-duration="0.7">
                            {{ __('Formation signature · En ligne') }}
                        </p>

                        <h2 class="mt-4 font-serif text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl" data-reveal-title data-reveal-title-duration="1" data-reveal-title-stagger="0.08">
                            <span class="inline-block">{{ __('Manucure') }}</span>
                            <span class="inline-block italic text-bmlk-brand">{{ __('à la cire') }}</span>
                        </h2>
                    </div>

                    <p class="max-w-xl text-base leading-7 text-zinc-600 lg:justify-self-end lg:text-lg" data-reveal data-reveal-y="16" data-reveal-duration="0.8" data-reveal-delay="0.16">
                        {{ __('Pour les prothésistes ongulaires et les formatrices qui veulent une zone de cuticule constamment nette sur tous les types de peau, et gagner au moins 20 minutes par prestation.') }}
                    </p>
                </div>

                <div class="mt-10 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-5" data-blur-reveal data-blur-stagger="0.08" data-blur-duration="0.75" data-blur-y="14" data-blur-pixels="7">
                    @foreach ($waxFacts as $fact)
                        <div class="rounded-2xl bg-[#FCF8F4] px-5 py-5 sm:px-6 sm:py-6" data-blur-reveal-item>
                            <p class="font-serif text-3xl font-medium leading-none text-bmlk-brand sm:text-4xl">{{ __($fact['value']) }}</p>
                            <p class="mt-3 text-[11px] font-semibold uppercase tracking-widest text-zinc-500">{{ __($fact['label']) }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-12 lg:mt-16">
                    <h3 class="font-serif text-2xl font-medium text-zinc-900 sm:text-3xl" data-reveal data-reveal-y="12" data-reveal-duration="0.75">{{ __('Les types de peau, avant et après') }}</h3>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-zinc-500">{{ __('Cuticule adhérente, hyperkératose des bourrelets, peau fine facilement traumatisée, peau sèche et déshydratée, peau réfractaire au traitement standard — et l’évolution de la peau d’un rendez-vous à l’autre.') }}</p>

                    <div class="mt-6 grid grid-cols-5 gap-2 sm:grid-cols-7 sm:gap-3 lg:grid-cols-[repeat(13,minmax(0,1fr))]" data-blur-reveal data-blur-stagger="0.04" data-blur-duration="0.7" data-blur-y="12" data-blur-pixels="6" data-blur-once="true">
                        @foreach ($waxSkins as $skin)
                            <div class="aspect-[3/4] overflow-hidden rounded-xl bg-[#EFDDE0]" data-blur-reveal-item>
                                <img
                                    src="{{ asset('img/formations/skin-'.$skin.'.webp') }}"
                                    alt="{{ __('Peau périunguéale étudiée en formation') }} {{ $loop->iteration }}"
                                    width="465"
                                    height="620"
                                    loading="lazy"
                                    decoding="async"
                                    class="h-full w-full object-cover"
                                >
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-12 grid gap-10 lg:mt-16 lg:grid-cols-12 lg:gap-16">
                    <div class="lg:col-span-5">
                        <h3 class="font-serif text-2xl font-medium text-zinc-900 sm:text-3xl" data-reveal data-reveal-y="12" data-reveal-duration="0.75">{{ __('À propos de la formation') }}</h3>

                        <div class="mt-5 space-y-4 text-sm leading-7 text-zinc-600 sm:text-base" data-blur-reveal data-blur-stagger="0.1" data-blur-duration="0.8" data-blur-y="14" data-blur-pixels="7">
                            @foreach ($waxAbout as $paragraph)
                                <p data-blur-reveal-item>{{ __($paragraph) }}</p>
                            @endforeach
                        </div>
                    </div>

                    <div class="lg:col-span-7">
                        <h3 class="font-serif text-2xl font-medium text-zinc-900 sm:text-3xl" data-reveal data-reveal-y="12" data-reveal-duration="0.75">{{ __('Objectifs de la formation') }}</h3>
                        <p class="mt-3 text-sm leading-6 text-zinc-500">{{ __('À l’issue de la formation, vous serez en mesure de :') }}</p>

                        <ul class="mt-5 space-y-3" data-blur-reveal data-blur-stagger="0.07" data-blur-duration="0.75" data-blur-y="12" data-blur-pixels="6">
                            @foreach ($waxGoals as $goal)
                                <li class="flex items-start gap-3 text-sm leading-6 text-zinc-700 sm:text-base sm:leading-7" data-blur-reveal-item>
                                    <x-lucide-check class="mt-1 h-4 w-4 shrink-0 text-bmlk-brand" stroke-width="1.7" />
                                    <span>{{ __($goal) }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-12 flex items-end justify-between gap-4">
                            <h3 class="font-serif text-2xl font-medium text-zinc-900 sm:text-3xl" data-reveal data-reveal-y="12" data-reveal-duration="0.75">{{ __('Programme') }}</h3>
                            <span class="rounded-full bg-[#EFDDE0] px-3 py-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-900 sm:px-4 sm:py-2 sm:text-xs">{{ count($waxModules) }} {{ __('modules') }}</span>
                        </div>

                        <div class="mt-5 space-y-2.5" data-creator-faq data-blur-reveal data-blur-stagger="0.07" data-blur-duration="0.75" data-blur-y="12" data-blur-pixels="6" data-blur-once="true">
                            @foreach ($waxModules as $index => $module)
                                <details class="group/module rounded-2xl bg-[#FCF8F4] transition-colors duration-300 open:bg-[#F6ECEE]" data-blur-reveal-item @if ($index === 0) open @endif>
                                    <summary class="flex cursor-pointer list-none items-center gap-4 px-5 py-5 sm:px-6 [&::-webkit-details-marker]:hidden">
                                        <span class="w-8 shrink-0 font-serif text-xl text-bmlk-brand">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="min-w-0 flex-1 font-serif text-lg font-medium leading-snug text-zinc-900 sm:text-xl">{{ __($module['title']) }}</span>
                                        <x-lucide-plus class="h-4 w-4 shrink-0 text-bmlk-brand transition-transform duration-300 group-data-[faq-expanded=true]/module:rotate-45" stroke-width="1.6" />
                                    </summary>

                                    <div class="overflow-hidden" data-creator-faq-panel>
                                        <div class="space-y-3 px-5 pb-6 text-sm leading-7 text-zinc-600 sm:pl-[4.5rem] sm:pr-6">
                                            @foreach (explode("\n\n", __($module['description'])) as $paragraph)
                                                <p>{{ $paragraph }}</p>
                                            @endforeach
                                        </div>
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-12 grid gap-4 sm:gap-5 lg:mt-16 lg:grid-cols-12" data-blur-reveal data-blur-stagger="0.12" data-blur-duration="0.85" data-blur-y="18" data-blur-pixels="8">
                    <div class="rounded-3xl bg-[#FCF8F4] p-6 sm:p-9 lg:col-span-7" data-blur-reveal-item>
                        <h3 class="font-serif text-2xl font-medium text-zinc-900 sm:text-3xl">{{ __('Ce qui est inclus') }}</h3>

                        <ul class="mt-6 space-y-3">
                            @foreach ($waxIncluded as $item)
                                <li class="flex items-start gap-3 text-sm leading-6 text-zinc-700">
                                    <x-lucide-check class="mt-0.5 h-4 w-4 shrink-0 text-bmlk-brand" stroke-width="1.7" />
                                    <span>{{ __($item) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="flex flex-col justify-between rounded-3xl bg-[#DDA1AA] p-6 sm:p-9 lg:col-span-5" data-blur-reveal-item>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-zinc-800">{{ __('Tarif') }}</p>
                            <p class="mt-3 font-serif text-6xl font-medium leading-none text-zinc-900">150&nbsp;€</p>
                            <p class="mt-5 text-sm leading-6 text-zinc-800">{{ __('Cire signature offerte, certificat envoyé par la poste avec la cire. Commencez dès aujourd’hui : dès votre prochaine cliente, vous travaillerez autrement.') }}</p>
                        </div>

                        <a href="mailto:office.blackmilk@gmail.com?subject={{ rawurlencode('Formation BLACK MILK — WAX MANICURE') }}" class="group mt-8 inline-flex h-12 items-center justify-center gap-3 rounded-full bg-accent px-6 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-accent/90">
                            {{ __('Réserver ma place') }}
                            <x-lucide-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- Live Education --}}
        <section id="presentiel" class="scroll-mt-8 px-3 pb-6 sm:px-5 sm:pb-8 lg:px-8 lg:pb-10">
            <div class="mx-auto max-w-[1560px] rounded-3xl bg-[#EFDDE0] px-6 py-12 sm:px-10 sm:py-14 lg:px-14 lg:py-16 xl:px-16">
                <div class="grid gap-6 lg:grid-cols-2 lg:items-end">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-bmlk-brand" data-reveal data-reveal-y="10" data-reveal-duration="0.7">
                            {{ __('En présentiel') }}
                        </p>

                        <h2 class="mt-4 font-serif text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl" data-reveal-title data-reveal-title-duration="1" data-reveal-title-stagger="0.08">
                            <span class="inline-block">{{ __('Live') }}</span>
                            <span class="inline-block italic text-white">{{ __('Education') }}</span>
                        </h2>
                    </div>

                    <div class="lg:justify-self-end" data-blur-reveal data-blur-stagger="0.1" data-blur-duration="0.8" data-blur-y="14" data-blur-pixels="7">
                        <p class="max-w-xl text-base leading-7 text-zinc-700 lg:text-lg" data-blur-reveal-item>
                            {{ __('Travaillez directement avec Elena Smirnova. Places limitées : les prochaines sessions sont à retrouver dans le calendrier.') }}
                        </p>

                        <a href="{{ route('events') }}" class="group mt-5 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-bmlk-brand transition hover:text-bmlk-brand-hover" data-blur-reveal-item>
                            {{ __('Voir le calendrier') }}
                            <x-lucide-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </a>
                    </div>
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-2 sm:gap-5 lg:mt-12 lg:grid-cols-3 lg:gap-8" data-blur-reveal data-blur-stagger="0.1" data-blur-duration="0.8" data-blur-y="20" data-blur-pixels="9">
                    @foreach ($liveCourses as $course)
                        <x-formation-card :course="$course" mode="live" />
                    @endforeach

                    @if (count($liveCourses) % 6 === 5)
                        <div class="flex flex-col justify-between gap-10 rounded-3xl bg-accent p-7 text-white sm:p-9" data-blur-reveal-item>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-[#EFDDE0]">{{ __('Calendrier') }}</p>
                                <h3 class="mt-4 font-serif text-3xl font-medium leading-tight sm:text-4xl">
                                    {{ __('Les prochaines') }} <span class="italic text-[#DDA1AA]">{{ __('sessions') }}</span>
                                </h3>
                                <p class="mt-4 max-w-sm text-sm leading-6 text-[#F6ECEE]">{{ __('Dates, lieux et places disponibles : retrouvez toutes les formations confirmées dans le calendrier BLACK MILK.') }}</p>
                            </div>

                            <a href="{{ route('events') }}" class="group inline-flex h-12 items-center justify-center gap-3 self-start rounded-full bg-white px-6 text-xs font-semibold uppercase tracking-widest text-zinc-900 transition hover:bg-zinc-50">
                                {{ __('Voir le calendrier') }}
                                <x-lucide-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </main>
</x-layout>
