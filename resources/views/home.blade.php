<x-layout>
    <section class="px-3 pb-6 pt-3 sm:px-5 sm:pb-8 sm:pt-5 lg:px-8 lg:pb-10">
        <div class="mx-auto max-w-[1560px]">
            <div class="grid overflow-hidden rounded-3xl bg-[#DDA1AA] lg:grid-cols-2">
                <div class="flex flex-col justify-between p-7 sm:p-10 lg:p-14 xl:p-16">
                    <div class="flex items-center gap-3">
                        <span class="h-px w-10 bg-zinc-800"></span>
                        <p class="text-xs font-semibold uppercase tracking-widest text-zinc-800">
                            {{ __('Créé par une maître, pour les maîtres') }}
                        </p>
                    </div>

                    <div class="py-14 lg:py-20">
                        <h1 class="max-w-3xl font-['Playfair_Display'] text-5xl font-medium leading-none tracking-tight text-zinc-900 sm:text-6xl lg:text-7xl xl:text-8xl">
                            {{ __('Élève') }}
                            <span class="italic text-white">{{ __('ton') }}</span>
                            {{ __('niveau.') }}
                        </h1>

                        <p class="mt-7 max-w-xl text-base leading-7 text-zinc-800 sm:text-lg">
                            {{ __('La technique révolutionnaire à la cire et des produits professionnels HEMA-free pensés pour celles et ceux qui exigent plus de leur travail.') }}
                        </p>

                        <div class="mt-9 flex flex-wrap gap-3">
                            <a
                                href="#"
                                class="inline-flex h-12 items-center justify-center rounded-full bg-zinc-900 px-6 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-zinc-800"
                            >
                                {{ __('Découvrir la boutique') }}
                            </a>

                            <a
                                href="#"
                                class="inline-flex h-12 items-center justify-center rounded-full border border-zinc-800 px-6 text-xs font-semibold uppercase tracking-widest text-zinc-900 transition hover:bg-white"
                            >
                                {{ __('Voir les cours') }}
                            </a>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-x-8 gap-y-3 border-t border-[#C98792] pt-5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-zinc-800">
                            {{ __('HEMA-free') }}
                        </p>
                        <p class="text-xs font-semibold uppercase tracking-widest text-zinc-800">
                            {{ __('50+ pays') }}
                        </p>
                        <p class="text-xs font-semibold uppercase tracking-widest text-zinc-800">
                            {{ __('Pour les professionnels') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-center px-5 pb-8 sm:px-10 lg:px-8 lg:py-10 xl:px-14">
                    <div class="aspect-square w-full max-w-2xl overflow-hidden rounded-full bg-[#FCF8F4]">
                        <x-cow />
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layout>
