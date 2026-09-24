<x-layout
    :title="$metaTitle"
    :meta-description="$metaDescription"
    :canonical="$canonical"
>
    <main>
        <section class="px-3 pb-5 pt-5 sm:px-5 sm:pb-10 lg:px-8">
            <div class="mx-auto max-w-[1560px] overflow-hidden rounded-3xl bg-[#DDA1AA]">
                <div class="grid gap-12 px-7 py-12 sm:px-10 sm:py-16 lg:grid-cols-12 lg:px-14 lg:py-20 xl:px-16">
                    <div class="lg:col-span-8">
                        <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-widest text-zinc-700" data-reveal data-reveal-y="10" data-reveal-duration="0.7">
                            <x-lucide-map-pin class="h-4 w-4" />
                            <span>{{ __('Réseau officiel BLACK MILK') }}</span>
                        </div>

                        <h1 class="mt-6 max-w-5xl font-serif text-5xl font-medium leading-none tracking-tight text-zinc-900 sm:text-6xl lg:text-7xl xl:text-8xl" data-reveal-title data-reveal-title-duration="1.05" data-reveal-title-stagger="0.08">
                            <span class="inline-block">{{ __('Trouve') }}</span>
                            <span class="inline-block italic text-white">{{ __('BLACK MILK') }}</span>
                            <span class="inline-block">{{ __('près de toi') }}</span>
                        </h1>
                    </div>

                    <div class="flex items-end lg:col-span-4">
                        <div class="max-w-md">
                            <p class="text-base leading-7 text-zinc-800 sm:text-lg" data-reveal data-reveal-y="16" data-reveal-duration="0.8" data-reveal-delay="0.14">
                                {{ __('Commande dans ta langue, paie dans ta monnaie et reçois ta commande plus vite auprès de nos représentants et revendeurs officiels.') }}
                            </p>

                            <div class="mt-8 flex flex-wrap gap-x-8 gap-y-3 border-t border-[#C98792] pt-5" data-blur-reveal data-blur-stagger="0.08" data-blur-duration="0.7" data-blur-y="10" data-blur-pixels="5">
                                <span class="text-xs font-semibold uppercase tracking-widest text-zinc-800" data-blur-reveal-item>
                                    {{ __('50+ pays') }}
                                </span>
                                <span class="text-xs font-semibold uppercase tracking-widest text-zinc-800" data-blur-reveal-item>
                                    {{ __('Réseau officiel') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="px-3 pb-14 sm:px-5 sm:pb-16 lg:px-8 lg:pb-20">
            <div class="mx-auto max-w-[1560px]">
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3" data-blur-reveal data-blur-stagger="0.05" data-blur-duration="0.75" data-blur-y="18" data-blur-pixels="7">
                    @foreach ($representatives as $representative)
                        @php
                            $primaryUrl = $representative->url ?: 'mailto:office.blackmilk@gmail.com';
                        @endphp

                        <article class="group flex min-h-72 flex-col justify-between rounded-3xl bg-white p-6 transition hover:-translate-y-1 hover:shadow-sm sm:p-7" data-blur-reveal-item>
                            <div>
                                <div class="flex items-start justify-between gap-5">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                                            {{ $representative->country }}
                                        </p>

                                        <p class="mt-3 text-xs font-semibold uppercase tracking-widest text-zinc-400">
                                            {{ $representative->tag }}
                                        </p>
                                    </div>

                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#F3E4E7] text-[#A9636F]">
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
                                        class="flex h-9 w-9 items-center justify-center rounded-full text-zinc-400 transition hover:bg-[#F3E4E7] hover:text-[#A9636F]"
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
                    <div class="px-7 py-10 sm:px-10 sm:py-12 lg:col-span-8 lg:px-14 lg:py-14 xl:px-16">
                        <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]" data-reveal data-reveal-y="10" data-reveal-duration="0.7">
                            {{ __('Développe BLACK MILK dans ta région') }}
                        </p>

                        <h2 class="mt-4 max-w-3xl font-serif text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl" data-reveal-title data-reveal-title-duration="0.95" data-reveal-title-stagger="0.08">
                            <span class="inline-block">{{ __('Ton pays') }}</span>
                            <span class="inline-block italic text-[#A9636F]">{{ __('n’est pas') }}</span>
                            <span class="inline-block">{{ __('dans la liste ?') }}</span>
                        </h2>
                    </div>

                    <div class="flex flex-col justify-center px-7 pb-10 sm:px-10 sm:pb-12 lg:col-span-4 lg:px-14 lg:py-14" data-blur-reveal data-blur-stagger="0.1" data-blur-duration="0.8" data-blur-y="14" data-blur-pixels="6">
                        <p class="text-base leading-7 text-zinc-600" data-blur-reveal-item>
                            {{ __('Deviens représentant officiel BLACK MILK dans ta région et développe ton activité avec une marque utilisée dans plus de 50 pays.') }}
                        </p>

                        <a
                            href="mailto:office.blackmilk@gmail.com?subject={{ rawurlencode(__('Candidature représentant BLACK MILK')) }}"
                            class="mt-7 inline-flex w-fit items-center gap-2 rounded-full bg-zinc-900 px-6 py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-zinc-800"
                            data-blur-reveal-item
                        >
                            {{ __('Devenir représentant') }}
                            <x-lucide-arrow-up-right class="h-4 w-4" />
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
</x-layout>
