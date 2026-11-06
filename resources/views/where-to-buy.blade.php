<x-layout
    :title="$metaTitle"
    :meta-description="$metaDescription"
    :canonical="$canonical"
>
    <main>
        <section class="px-3 pb-5 pt-5 sm:px-5 sm:pb-10 lg:px-8" aria-labelledby="where-to-buy-title">
            <div class="relative mx-auto max-w-[1560px] overflow-hidden rounded-3xl bg-[#DDA1AA]">
                <div class="grid gap-9 px-5 pt-9 sm:gap-12 sm:px-10 sm:pt-12 lg:min-h-[620px] lg:grid-cols-12 lg:gap-0 lg:px-0 lg:pt-0">
                    <div class="flex flex-col justify-between lg:col-span-7 lg:px-14 lg:pb-16 lg:pt-16 xl:px-16 xl:pt-20">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="h-px w-8 shrink-0 bg-accent sm:w-10" data-line-reveal data-line-reveal-duration="0.8"></span>
                                <p class="text-[10px] font-semibold uppercase tracking-widest text-accent sm:text-xs" data-reveal data-reveal-y="10" data-reveal-duration="0.7" data-reveal-delay="0.08">
                                    {{ __('Réseau officiel BLACK MILK') }}
                                </p>
                            </div>

                            <h1 id="where-to-buy-title" class="mt-7 max-w-3xl font-serif text-[2.625rem] font-medium leading-none tracking-tight text-bmlk-ink sm:text-6xl lg:text-5xl xl:text-7xl 2xl:text-[5.25rem]" data-reveal-title data-reveal-title-duration="1.05" data-reveal-title-stagger="0.08">
                                <span class="block">{{ __('Trouvez') }}</span>
                                <span class="block italic text-white">{{ __('BLACK MILK') }}</span>
                                <span class="block">{{ __('près de chez vous') }}</span>
                            </h1>

                            <p class="mt-6 max-w-lg text-sm leading-6 text-[#46343A] sm:mt-8 sm:text-lg sm:leading-8" data-reveal data-reveal-y="16" data-reveal-duration="0.8" data-reveal-delay="0.16">
                                {{ __('Profitez d\'une livraison plus rapide et d\'un service local auprès de nos revendeurs et partenaires agréés.') }}
                            </p>

                            <a href="#points-de-vente" class="group mt-7 inline-flex h-12 items-center justify-center gap-3 rounded-full bg-accent px-6 text-xs font-semibold uppercase tracking-widest text-white transition-colors hover:bg-accent/90 sm:mt-9" data-reveal data-reveal-y="12" data-reveal-duration="0.7" data-reveal-delay="0.25">
                                {{ __('Découvrir les points de vente') }}
                                <x-lucide-arrow-down class="h-4 w-4 transition-transform group-hover:translate-y-0.5" />
                            </a>
                        </div>

                        <div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-3 border-t border-[#C98792] pt-5 lg:mt-14" data-blur-reveal data-blur-stagger="0.1" data-blur-duration="0.75" data-blur-y="10" data-blur-pixels="5">
                            <div class="flex items-center gap-2.5 text-xs font-semibold text-bmlk-ink" data-blur-reveal-item>
                                <x-lucide-globe-2 class="h-4 w-4 shrink-0" stroke-width="1.5" />
                                <span>{{ __('50+ pays') }}</span>
                            </div>
                            <div class="flex items-center gap-2.5 text-xs font-semibold text-bmlk-ink" data-blur-reveal-item>
                                <x-lucide-badge-check class="h-4 w-4 shrink-0" stroke-width="1.5" />
                                <span>{{ __('Partenaires agréés') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="-mx-5 flex min-w-0 items-end sm:-mx-10 lg:col-span-5 lg:mx-0 lg:pl-2 lg:pt-14">
                        <div class="w-full rounded-t-[28px] bg-[#FCF8F4] px-6 pb-9 pt-7 shadow-sm sm:px-8 sm:pt-9 lg:rounded-tl-[32px] lg:rounded-tr-none lg:px-9 lg:pb-12 lg:pt-11 xl:px-12" data-reveal data-reveal-y="20" data-reveal-duration="0.85" data-reveal-delay="0.2">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-bmlk-brand sm:text-xs">{{ __('Un aperçu du réseau') }}</p>
                                    <h2 class="mt-3 font-serif text-2xl font-medium tracking-tight text-bmlk-ink sm:text-3xl">{{ __('Nos partenaires') }}</h2>
                                </div>
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#F3E4E7] text-accent" aria-hidden="true">
                                    <x-lucide-map-pin class="h-5 w-5" stroke-width="1.5" />
                                </span>
                            </div>

                            <div class="mt-6 divide-y divide-[#E9DDDF]">
                                @forelse ($representatives->take(4) as $representative)
                                    <a href="#point-de-vente-{{ $representative->id }}" class="group items-center justify-between gap-4 py-4 {{ $loop->iteration <= 2 ? 'flex' : ($loop->iteration === 3 ? 'hidden sm:flex' : 'hidden lg:flex') }}">
                                        <div class="min-w-0">
                                            <p class="text-[10px] font-semibold uppercase tracking-widest text-bmlk-brand">{{ $representative->country }}</p>
                                            <p class="mt-1 font-serif text-lg font-medium leading-snug text-bmlk-ink transition-colors group-hover:text-accent sm:text-xl">{{ $representative->name }}</p>
                                            @if ($representative->city)
                                                <p class="mt-1 text-xs text-zinc-500">{{ $representative->city }}</p>
                                            @endif
                                        </div>
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#F3E4E7] text-accent transition-colors group-hover:bg-accent group-hover:text-white" aria-hidden="true">
                                            <x-lucide-arrow-down-right class="h-4 w-4" stroke-width="1.5" />
                                        </span>
                                    </a>
                                @empty
                                    <p class="py-8 text-sm leading-6 text-zinc-500">{{ __('Les points de vente seront bientôt disponibles.') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bmlk-packaging-stripe pointer-events-none absolute inset-x-0 bottom-0 z-20" aria-hidden="true"></div>
            </div>
        </section>

        <section id="points-de-vente" class="scroll-mt-24 px-3 pb-14 sm:px-5 sm:pb-16 lg:px-8 lg:pb-20">
            <div class="mx-auto max-w-[1560px]">
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3" data-blur-reveal data-blur-duration="0.75" data-blur-y="18" data-blur-pixels="7" data-blur-amount="0.15" data-blur-once="true" data-blur-per-item="true">
                    @foreach ($representatives as $representative)
                        @php
                            $primaryUrl = $representative->url ?: 'mailto:office.blackmilk@gmail.com';
                        @endphp

                        <article id="point-de-vente-{{ $representative->id }}" class="group scroll-mt-24 flex min-h-72 flex-col justify-between rounded-3xl bg-white p-6 transition hover:-translate-y-1 hover:shadow-sm sm:p-7" data-blur-reveal-item>
                            <div>
                                <div class="flex items-start justify-between gap-5">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-widest text-bmlk-brand">
                                            {{ $representative->country }}
                                        </p>

                                        <p class="mt-3 text-xs font-semibold uppercase tracking-widest text-zinc-400">
                                            {{ $representative->tag }}
                                        </p>
                                    </div>

                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#F3E4E7] text-bmlk-brand">
                                        <x-lucide-store class="h-5 w-5" />
                                    </span>
                                </div>

                                <h2 class="mt-8 font-serif text-3xl font-medium leading-tight text-zinc-900">
                                    {{ $representative->name }}
                                </h2>

                                @if ($representative->city)
                                    <div class="mt-4 flex items-start gap-2 text-sm leading-6 text-zinc-500">
                                        <x-lucide-map-pin class="mt-1 h-4 w-4 shrink-0 text-zinc-400" />
                                        <span>{{ $representative->city }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-10 flex items-center justify-between border-t border-zinc-100 pt-5">
                                <a
                                    href="{{ $primaryUrl }}"
                                    @if (str_starts_with($primaryUrl, 'http')) target="_blank" rel="noopener" @endif
                                    class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-zinc-900"
                                >
                                    {{ $representative->cta ?: __('Nous contacter') }}
                                    <x-lucide-arrow-up-right class="h-4 w-4 transition-transform group-hover:translate-x-1 group-hover:-translate-y-1" />
                                </a>

                                @if ($representative->instagram && $representative->instagram !== $representative->url)
                                    <a
                                        href="{{ $representative->instagram }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="flex h-9 w-9 items-center justify-center rounded-full text-zinc-400 transition hover:bg-[#F3E4E7] hover:text-bmlk-brand"
                                        aria-label="{{ __('Instagram de :name', ['name' => $representative->name]) }}"
                                    >
                                        <x-lucide-instagram class="h-4 w-4" />
                                    </a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="px-3 pb-8 pt-4 sm:px-5 sm:pb-10 lg:px-8 lg:pb-12">
            <div class="mx-auto max-w-[1560px]">
                <div class="grid overflow-hidden rounded-3xl bg-[#EFDDE0] lg:grid-cols-12">
                    <div class="px-5 pb-4 pt-7 sm:px-10 sm:py-12 lg:col-span-7 lg:px-14 lg:py-14 xl:px-16">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-bmlk-brand sm:text-xs" data-reveal data-reveal-y="10" data-reveal-duration="0.7">
                            {{ __('Devenez distributeur local') }}
                        </p>

                        <h2 class="mt-3 max-w-3xl font-serif text-3xl font-medium leading-none tracking-tight text-zinc-900 sm:mt-4 sm:text-4xl lg:text-5xl" data-reveal-title data-reveal-title-duration="0.95" data-reveal-title-stagger="0.08">
                            <span class="inline-block">{{ __('Votre pays') }}</span>
                            <span class="inline-block italic text-bmlk-brand">{{ __('ne figure pas') }}</span>
                            <span class="inline-block">{{ __('dans la liste ?') }}</span>
                        </h2>
                    </div>

                    <div class="flex flex-col justify-center px-5 pb-7 sm:px-10 sm:pb-12 lg:col-span-5 lg:px-14 lg:py-14" data-blur-reveal data-blur-stagger="0.1" data-blur-duration="0.8" data-blur-y="14" data-blur-pixels="6">
                        <p class="text-sm leading-6 text-zinc-600 sm:text-base sm:leading-7" data-blur-reveal-item>
                            {{ __('Rejoignez le réseau BLACK MILK en tant que revendeur agréé et développez votre activité avec une marque présente dans plus de 50 pays') }}
                        </p>

                        <a
                            href="mailto:office.blackmilk@gmail.com?subject={{ rawurlencode(__('Candidature représentant BLACK MILK')) }}"
                            class="mt-5 inline-flex w-fit items-center gap-2 rounded-full bg-zinc-900 px-6 py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-zinc-800 sm:mt-7"
                            data-blur-reveal-item
                        >
                            {{ __('Devenir partenaire') }}
                            <x-lucide-arrow-up-right class="h-4 w-4" />
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
</x-layout>
