<footer class="px-3 pb-3 pt-16 sm:px-5 sm:pb-5 sm:pt-20 lg:px-8 lg:pb-8 lg:pt-24">
    <div class="mx-auto max-w-[1560px] overflow-hidden rounded-3xl bg-zinc-950 text-white">
        <div class="grid gap-14 px-7 py-10 sm:px-10 sm:py-12 lg:grid-cols-12 lg:px-14 lg:py-16 xl:px-16">
            <div class="lg:col-span-7">
                <a
                    href="{{ url('/') }}"
                    class="inline-flex items-center gap-3"
                    aria-label="{{ __('BLACK MILK') }}"
                >
                    <img
                        src="/img/logo.png"
                        alt=""
                        class="h-12 w-12 object-contain"
                    >

                    <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400">
                        {{ __('Paris · France') }}
                    </span>
                </a>

                <div class="mt-12 sm:mt-16">
                    <p class="font-['Playfair_Display'] text-5xl font-medium leading-none tracking-tight sm:text-6xl lg:text-7xl xl:text-8xl">
                        {{ __('BLACK MILK') }}
                    </p>

                    <p class="mt-3 font-['Playfair_Display'] text-2xl italic text-[#DDA1AA] sm:text-3xl">
                        {{ __('Le Premier Choix') }}
                    </p>
                </div>

                <p class="mt-8 max-w-xl text-base leading-7 text-zinc-400 sm:text-lg">
                    {{ __('Cosmétique professionnelle pour la manucure, développée par des maîtres pour celles et ceux qui placent la technique au premier plan.') }}
                </p>
            </div>

            <div class="flex flex-col justify-between gap-10 lg:col-span-5 lg:pl-10">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-zinc-500">
                        {{ __('Parlons-nous') }}
                    </p>

                    <div class="mt-5 space-y-3">
                        <a
                            href="mailto:office.blackmilk@gmail.com"
                            class="group flex items-center justify-between rounded-2xl bg-zinc-900 px-5 py-4 transition hover:bg-zinc-800"
                        >
                            <span class="flex items-center gap-4">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#DDA1AA] text-zinc-950">
                                    <x-lucide-mail class="h-4 w-4" />
                                </span>

                                <span>
                                    <span class="block text-xs uppercase tracking-widest text-zinc-500">
                                        {{ __('Email') }}
                                    </span>
                                    <span class="mt-1 block text-sm text-white">
                                        {{ __('office.blackmilk@gmail.com') }}
                                    </span>
                                </span>
                            </span>

                            <x-lucide-arrow-up-right class="h-4 w-4 text-zinc-500 transition group-hover:text-white" />
                        </a>

                        <a
                            href="#"
                            class="group flex items-center justify-between rounded-2xl bg-zinc-900 px-5 py-4 transition hover:bg-zinc-800"
                        >
                            <span class="flex items-center gap-4">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#DDA1AA] text-zinc-950">
                                    <x-lucide-instagram class="h-4 w-4" />
                                </span>

                                <span>
                                    <span class="block text-xs uppercase tracking-widest text-zinc-500">
                                        {{ __('Instagram') }}
                                    </span>
                                    <span class="mt-1 block text-sm text-white">
                                        {{ __('@black_milk_fr') }}
                                    </span>
                                </span>
                            </span>

                            <x-lucide-arrow-up-right class="h-4 w-4 text-zinc-500 transition group-hover:text-white" />
                        </a>
                    </div>
                </div>

                <div class="flex items-center gap-3 text-sm text-zinc-400">
                    <x-lucide-map-pin class="h-4 w-4 text-[#DDA1AA]" />
                    <span>{{ __('Créé à Paris. Utilisé dans plus de 50 pays.') }}</span>
                </div>
            </div>
        </div>

        <div class="grid gap-10 border-t border-zinc-800 px-7 py-10 sm:px-10 lg:grid-cols-12 lg:px-14 xl:px-16">
            <div class="lg:col-span-7">
                <nav class="grid grid-cols-2 gap-x-8 gap-y-4 sm:grid-cols-3">
                    <a href="{{ route('shop.index') }}" class="text-sm text-zinc-400 transition hover:text-white">
                        {{ __('Produits') }}
                    </a>
                    <a href="#coffrets" class="text-sm text-zinc-400 transition hover:text-white">
                        {{ __('Coffrets') }}
                    </a>
                    <a href="#" class="text-sm text-zinc-400 transition hover:text-white">
                        {{ __('Cours') }}
                    </a>
                    <a href="#" class="text-sm text-zinc-400 transition hover:text-white">
                        {{ __('Notre histoire') }}
                    </a>
                    <a href="{{ route('where-to-buy') }}" class="text-sm text-zinc-400 transition hover:text-white">
                        {{ __('Représentants') }}
                    </a>
                    <a href="#" class="text-sm text-zinc-400 transition hover:text-white">
                        {{ __('Contact') }}
                    </a>
                </nav>
            </div>

            <div class="flex items-start justify-between gap-6 lg:col-span-5 lg:pl-10">
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-zinc-500">
                        <x-lucide-languages class="h-4 w-4" />
                        <span>{{ __('Langue') }}</span>
                    </div>

                    <div class="mt-4 flex gap-5 text-sm">
                        <a href="#" class="text-white">{{ __('FR') }}</a>
                        <a href="#" class="text-zinc-500 transition hover:text-white">{{ __('EN') }}</a>
                        <a href="#" class="text-zinc-500 transition hover:text-white">{{ __('RO') }}</a>
                    </div>
                </div>

                <a
                    href="#"
                    class="flex h-11 w-11 items-center justify-center rounded-full border border-zinc-700 text-zinc-400 transition hover:border-zinc-500 hover:text-white"
                    aria-label="{{ __('Retour en haut') }}"
                    onclick="window.scrollTo({ top: 0, behavior: 'smooth' }); return false;"
                >
                    <x-lucide-arrow-up class="h-4 w-4" />
                </a>
            </div>
        </div>

        <div class="flex flex-col gap-5 bg-[#DDA1AA] px-7 py-5 text-zinc-900 sm:px-10 lg:flex-row lg:items-center lg:justify-between lg:px-14 xl:px-16">
            <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1">
                <span class="font-['Playfair_Display'] text-xl italic">
                    {{ __('made with love · Paris') }}
                </span>

                <span class="text-xs">
                    {{ __('© 2026 BLACK MILK France') }}
                </span>
            </div>

            <nav class="flex flex-wrap gap-x-5 gap-y-2 text-xs">
                <a href="/conditions-generales-de-vente" class="transition hover:text-white">{{ __('CGV') }}</a>
                <a href="#" class="transition hover:text-white">{{ __('Mentions légales') }}</a>
                <a href="#" class="transition hover:text-white">{{ __('Confidentialité') }}</a>
                <a href="#" class="transition hover:text-white">{{ __('Cookies') }}</a>
            </nav>
        </div>
    </div>
</footer>
