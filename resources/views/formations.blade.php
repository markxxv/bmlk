<x-layout :title="$metaTitle" :meta-description="$metaDescription" :canonical="$canonical" :og-image="asset('img/elena.webp')">
    <main>
        <section class="px-3 pb-8 pt-5 sm:px-5 sm:pb-12 lg:px-8">
            <div class="mx-auto max-w-[1560px] overflow-hidden rounded-3xl bg-[#EFDDE0]">
                <div class="grid lg:min-h-[590px] lg:grid-cols-12">
                    <div class="flex flex-col justify-center px-7 py-12 sm:px-10 sm:py-16 lg:col-span-7 lg:px-14 lg:py-20 xl:px-16">
                        <p class="text-xs font-semibold uppercase tracking-widest text-bmlk-brand" data-reveal data-reveal-y="10">{{ __('SMIRNOVA SCHOOL · BLACK MILK') }}</p>
                        <h1 class="mt-6 max-w-3xl font-serif text-5xl font-medium leading-none tracking-tight text-zinc-900 sm:text-6xl lg:text-7xl xl:text-8xl" data-reveal-title data-reveal-title-duration="1.05">
                            {{ __('Apprenez') }} <span class="italic text-bmlk-brand">{{ __('à la source') }}</span>
                        </h1>
                        <p class="mt-6 max-w-xl text-base leading-7 text-zinc-700 sm:text-lg sm:leading-8" data-reveal data-reveal-y="16">
                            {{ __('Des formations professionnelles pensées par Elena Smirnova pour approfondir votre technique, perfectionner vos gestes et transmettre votre savoir-faire.') }}
                        </p>
                        <div class="mt-8 flex flex-wrap gap-3" data-blur-reveal data-blur-reveal-stagger="0.09">
                            <a href="#online" class="group inline-flex min-h-12 items-center gap-3 rounded-full bg-bmlk-brand px-6 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#945763]">
                                {{ __('Cours en ligne') }} <x-lucide-arrow-down class="h-4 w-4 transition-transform group-hover:translate-y-1" />
                            </a>
                            <a href="#presentiel" class="inline-flex min-h-12 items-center gap-3 rounded-full bg-white px-6 text-xs font-semibold uppercase tracking-widest text-bmlk-brand transition hover:bg-[#FCF8F4]">
                                {{ __('Formations en présentiel') }}
                            </a>
                        </div>
                    </div>
                    <div class="relative min-h-64 overflow-hidden bg-[#DDA1AA] sm:min-h-80 lg:col-span-5">
                        <img src="{{ asset('img/elena.webp') }}" alt="{{ __('Elena Smirnova, fondatrice de BLACK MILK') }}" class="absolute inset-0 h-full w-full object-cover object-center" fetchpriority="high">
                        <div class="absolute bottom-5 left-5 rounded-full bg-white px-5 py-2 text-xs font-semibold uppercase tracking-widest text-bmlk-brand sm:bottom-8 sm:left-8">{{ __('Elena Smirnova') }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section id="online" class="scroll-mt-8 px-3 pb-16 pt-8 sm:px-5 sm:pb-20 sm:pt-12 lg:px-8 lg:pb-28">
            <div class="mx-auto max-w-[1560px]">
                <div class="grid gap-5 lg:grid-cols-12 lg:items-end">
                    <div class="lg:col-span-8">
                        <p class="text-xs font-semibold uppercase tracking-widest text-bmlk-brand" data-reveal data-reveal-y="10">{{ __('À votre rythme') }}</p>
                        <h2 class="mt-4 font-serif text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl" data-reveal-title>
                            {{ __('Online') }} <span class="italic text-bmlk-brand">{{ __('Education') }}</span>
                        </h2>
                    </div>
                    <p class="max-w-lg text-sm leading-7 text-zinc-600 sm:text-base lg:col-span-4" data-reveal data-reveal-y="14">
                        {{ __('Explorez les techniques BLACK MILK où que vous soyez. Les cours et tarifs présentés proviennent du catalogue Smirnova School.') }}
                    </p>
                </div>

                <div class="mt-9 grid gap-4 sm:grid-cols-2 lg:mt-12 lg:grid-cols-3 lg:gap-5" data-blur-reveal data-blur-stagger="0.08" data-blur-y="14">
                    @foreach ($onlineCourses as $course)
                        <x-formation-card :course="$course" />
                    @endforeach
                </div>
            </div>
        </section>

        <section id="programme" class="scroll-mt-8 px-3 pb-16 sm:px-5 sm:pb-20 lg:px-8 lg:pb-28">
            <div class="mx-auto max-w-[1560px] overflow-hidden rounded-3xl bg-[#F6ECEE]">
                <div class="grid gap-10 px-6 py-10 sm:px-10 sm:py-14 lg:grid-cols-12 lg:gap-16 lg:px-14 lg:py-20 xl:px-16">
                    <div class="lg:col-span-5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-bmlk-brand" data-reveal data-reveal-y="10">{{ __('Focus sur la méthode signature') }}</p>
                        <h2 class="mt-4 font-serif text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl" data-reveal-title>
                            {{ __('La manucure') }} <span class="italic text-bmlk-brand">{{ __('à la cire') }}</span>
                        </h2>
                        <p class="mt-6 text-sm leading-7 text-zinc-700 sm:text-base">
                            {{ __('Une méthode qui part de l’observation des tissus pour choisir l’outil, la vitesse et le geste adaptés à chaque type de peau. Elle associe diagnostic, pratique et protocole de finition à la cire.') }}
                        </p>
                        <p class="mt-4 text-sm leading-7 text-zinc-700 sm:text-base">
                            {{ __('Le programme détaillé ci-contre présente les notions étudiées et les cas pratiques abordés pendant la formation.') }}
                        </p>
                        <div class="mt-7 grid grid-cols-2 gap-3">
                            @foreach (['skin-14.jpg', 'skin-05.jpg', 'skin-06.jpg', 'skin-02.jpg'] as $image)
                                <div class="aspect-square overflow-hidden rounded-2xl bg-white">
                                    <img src="{{ asset('img/formations/'.$image) }}" loading="lazy" decoding="async" alt="{{ __('Exemple de peau périunguéale étudiée en formation') }}" class="h-full w-full object-cover">
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-3 text-xs text-zinc-500">{{ __('Exemples visuels issus des supports de formation.') }}</p>
                    </div>

                    <div class="lg:col-span-7">
                        <div class="rounded-3xl bg-white p-6 sm:p-9">
                            <div class="flex items-center justify-between gap-4">
                                <h3 class="font-serif text-3xl text-zinc-900 sm:text-4xl">{{ __('Programme de formation') }}</h3>
                                <span class="shrink-0 rounded-full bg-[#F6ECEE] px-3 py-2 text-xs font-semibold text-bmlk-brand">{{ count($waxModules) }} {{ __('modules') }}</span>
                            </div>
                            <div class="mt-6 divide-y divide-zinc-100">
                                @foreach ($waxModules as $index => $module)
                                    <details class="group/module py-1" {{ $index === 0 ? 'open' : '' }}>
                                        <summary class="flex cursor-pointer list-none items-center gap-4 py-4 [&::-webkit-details-marker]:hidden">
                                            <span class="w-7 shrink-0 font-serif text-xl text-bmlk-brand">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                            <span class="flex-1 text-sm font-semibold leading-6 text-zinc-900 sm:text-base">{{ __($module['title']) }}</span>
                                            <x-lucide-plus class="h-4 w-4 shrink-0 text-bmlk-brand transition-transform group-open/module:rotate-45" />
                                        </summary>
                                        <p class="pb-5 pl-11 text-sm leading-7 text-zinc-600">{{ __($module['description']) }}</p>
                                    </details>
                                @endforeach
                            </div>
                            <a href="mailto:office.blackmilk@gmail.com?subject={{ rawurlencode('Formation BLACK MILK — WAX MANICURE') }}" class="group mt-6 inline-flex min-h-12 items-center gap-3 rounded-full bg-bmlk-brand px-6 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#945763]">
                                {{ __('Découvrir cette formation') }} <x-lucide-arrow-up-right class="h-4 w-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="presentiel" class="scroll-mt-8 px-3 pb-16 sm:px-5 sm:pb-20 lg:px-8 lg:pb-28">
            <div class="mx-auto max-w-[1560px] overflow-hidden rounded-3xl bg-[#EFDDE0] px-6 py-10 sm:px-10 sm:py-14 lg:px-14 lg:py-20 xl:px-16">
                <div class="grid gap-6 lg:grid-cols-12 lg:items-end">
                    <div class="lg:col-span-8">
                        <p class="text-xs font-semibold uppercase tracking-widest text-bmlk-brand" data-reveal data-reveal-y="10">{{ __('Apprendre ensemble') }}</p>
                        <h2 class="mt-4 font-serif text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl" data-reveal-title>
                            {{ __('Live') }} <span class="italic text-bmlk-brand">{{ __('Education') }}</span>
                        </h2>
                    </div>
                    <div class="lg:col-span-4">
                        <p class="text-sm leading-7 text-zinc-700 sm:text-base">{{ __('Des sessions en présentiel, en groupe ou en individuel, pour apprendre directement auprès des formatrices.') }}</p>
                        <a href="{{ route('events') }}" class="group mt-4 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-bmlk-brand">
                            {{ __('Consulter le calendrier') }} <x-lucide-arrow-up-right class="h-4 w-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                        </a>
                    </div>
                </div>

                <div class="mt-9 grid gap-4 sm:grid-cols-2 lg:mt-12 lg:grid-cols-3 lg:gap-5" data-blur-reveal data-blur-stagger="0.08" data-blur-y="14">
                    @foreach ($liveCourses as $course)
                        <x-formation-card :course="$course" mode="live" />
                    @endforeach
                </div>

                <p class="mt-8 text-xs leading-6 text-zinc-600">{{ __('Les dates, les lieux et les places disponibles sont communiqués séparément. Consultez le calendrier des événements pour les prochaines sessions confirmées.') }}</p>
            </div>
        </section>

        <section class="px-3 pb-20 sm:px-5 sm:pb-24 lg:px-8 lg:pb-28">
            <div class="mx-auto max-w-[1560px] rounded-3xl bg-bmlk-brand px-7 py-12 text-white sm:px-10 sm:py-16 lg:px-14 xl:px-16">
                <div class="grid gap-8 lg:grid-cols-12 lg:items-center">
                    <div class="lg:col-span-8">
                        <p class="text-xs font-semibold uppercase tracking-widest text-[#EFDDE0]">{{ __('Votre parcours BLACK MILK') }}</p>
                        <h2 class="mt-4 font-serif text-4xl font-medium leading-none sm:text-5xl">{{ __('Quelle formation') }} <span class="italic text-[#EFDDE0]">{{ __('choisir ?') }}</span></h2>
                        <p class="mt-5 max-w-2xl text-sm leading-7 text-[#F6ECEE] sm:text-base">{{ __('Contactez l’équipe BLACK MILK pour choisir un programme adapté à votre expérience, à votre pratique et à vos objectifs.') }}</p>
                    </div>
                    <div class="lg:col-span-4 lg:text-right">
                        <a href="mailto:office.blackmilk@gmail.com?subject={{ rawurlencode('Informations formations BLACK MILK') }}" class="group inline-flex min-h-12 items-center gap-3 rounded-full bg-white px-6 text-xs font-semibold uppercase tracking-widest text-bmlk-brand transition hover:bg-[#F6ECEE]">
                            {{ __('Nous contacter') }} <x-lucide-arrow-up-right class="h-4 w-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
</x-layout>
