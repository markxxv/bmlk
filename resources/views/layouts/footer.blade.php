<footer class="mt-16 bg-white sm:mt-20 lg:mt-24">
    <div class="mx-auto max-w-[1560px] px-5 py-14 sm:px-8 sm:py-16 lg:px-12 lg:py-20">
        <div class="grid gap-12 md:grid-cols-2 lg:grid-cols-12 lg:gap-8">
            <div class="lg:col-span-4">
                <a
                    href="{{ url('/') }}"
                    class="inline-flex items-center gap-4"
                    aria-label="{{ __('BLACK MILK') }}"
                >
                    <img
                        src="/img/logo.png"
                        alt=""
                        class="h-16 w-16 object-contain"
                    >

                    <span>
                        <span class="block font-['Playfair_Display'] text-2xl font-medium leading-none tracking-widest text-zinc-900">
                            {{ __('BLACK MILK') }}
                        </span>
                        <span class="mt-2 block font-['Playfair_Display'] text-sm italic text-[#A9636F]">
                            {{ __('Le Premier Choix') }}
                        </span>
                    </span>
                </a>

                <p class="mt-7 max-w-sm text-base leading-7 text-zinc-600">
                    {{ __('Cosmétique professionnelle pour la manucure. Créée par une maître, pour les maîtres.') }}
                </p>

                <a
                    href="mailto:office.blackmilk@gmail.com"
                    class="mt-6 inline-block text-sm font-medium text-zinc-900 transition hover:text-[#A9636F]"
                >
                    {{ __('office.blackmilk@gmail.com') }}
                </a>
            </div>

            <div class="lg:col-span-3 lg:col-start-6">
                <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                    {{ __('Boutique') }}
                </p>

                <nav class="mt-5 flex flex-col gap-3">
                    <a href="#" class="text-base text-zinc-600 transition hover:text-zinc-900">
                        {{ __('Base & Top camouflage') }}
                    </a>
                    <a href="#" class="text-base text-zinc-600 transition hover:text-zinc-900">
                        {{ __('Gel Corex & Poly gel') }}
                    </a>
                    <a href="#" class="text-base text-zinc-600 transition hover:text-zinc-900">
                        {{ __('Cire & sérums') }}
                    </a>
                    <a href="#" class="text-base text-zinc-600 transition hover:text-zinc-900">
                        {{ __('Coupe & accessoires') }}
                    </a>
                    <a href="#coffrets" class="text-base text-zinc-600 transition hover:text-zinc-900">
                        {{ __('Coffrets') }}
                    </a>
                </nav>
            </div>

            <div class="lg:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                    {{ __('Marque') }}
                </p>

                <nav class="mt-5 flex flex-col gap-3">
                    <a href="#" class="text-base text-zinc-600 transition hover:text-zinc-900">
                        {{ __('Cours') }}
                    </a>
                    <a href="#" class="text-base text-zinc-600 transition hover:text-zinc-900">
                        {{ __('Représentants') }}
                    </a>
                    <a href="#" class="text-base text-zinc-600 transition hover:text-zinc-900">
                        {{ __('Ambassadeur') }}
                    </a>
                    <a href="#" class="text-base text-zinc-600 transition hover:text-zinc-900">
                        {{ __('Contact') }}
                    </a>
                    <a href="#" class="text-base text-zinc-600 transition hover:text-zinc-900">
                        {{ __('Suivi de commande') }}
                    </a>
                </nav>
            </div>

            <div class="lg:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                    {{ __('Suivez-nous') }}
                </p>

                <div class="mt-5 flex flex-col gap-3">
                    <a href="#" class="text-base text-zinc-600 transition hover:text-zinc-900">
                        {{ __('Instagram — black_milk_fr') }}
                    </a>
                    <a href="#" class="text-base text-zinc-600 transition hover:text-zinc-900">
                        {{ __('Instagram — smirnova_school_') }}
                    </a>
                </div>

                <div class="mt-8">
                    <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                        {{ __('Langue') }}
                    </p>

                    <div class="mt-4 flex gap-5 text-sm font-medium text-zinc-500">
                        <a href="#" class="text-zinc-900">{{ __('FR') }}</a>
                        <a href="#" class="transition hover:text-zinc-900">{{ __('EN') }}</a>
                        <a href="#" class="transition hover:text-zinc-900">{{ __('RO') }}</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-14 border-t border-zinc-200 pt-6 sm:mt-16 lg:flex lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-baseline gap-x-4 gap-y-2">
                <span class="font-['Playfair_Display'] text-xl italic text-[#A9636F]">
                    {{ __('made with love · Paris') }}
                </span>

                <span class="text-sm text-zinc-500">
                    {{ __('© 2026 BLACK MILK France · Tous droits réservés') }}
                </span>
            </div>

            <nav class="mt-6 flex flex-wrap gap-x-6 gap-y-3 text-sm text-zinc-500 lg:mt-0 lg:justify-end">
                <a href="#" class="transition hover:text-zinc-900">{{ __('CGV') }}</a>
                <a href="#" class="transition hover:text-zinc-900">{{ __('Mentions légales') }}</a>
                <a href="#" class="transition hover:text-zinc-900">{{ __('Confidentialité') }}</a>
                <a href="#" class="transition hover:text-zinc-900">{{ __('Cookies') }}</a>
                <a href="#" class="transition hover:text-zinc-900">{{ __('Paramètres cookies') }}</a>
            </nav>
        </div>
    </div>
</footer>
