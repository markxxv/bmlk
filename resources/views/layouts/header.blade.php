<header
    x-data="{ open: false }"
    class="relative z-50 px-3 pt-3 sm:px-5 sm:pt-5 lg:px-8 lg:pt-6"
>
    <div class="mx-auto max-w-[1560px]">
        <div class="relative rounded-[24px] border border-[#3A2E30]/10 bg-[#FFFDFB]/92 px-4 shadow-[0_12px_40px_rgba(58,46,48,0.05)] backdrop-blur-xl sm:px-5 lg:px-7">
            <div class="grid min-h-[76px] grid-cols-[1fr_auto] items-center gap-4 lg:grid-cols-[1fr_auto_1fr]">
                <nav class="hidden items-center gap-7 xl:gap-9 lg:flex">
                    <a href="#" class="group relative py-7 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#3A2E30]">
                        Boutique
                        <span class="absolute inset-x-0 bottom-[20px] h-px origin-left scale-x-0 bg-[#A9636F] transition-transform duration-300 group-hover:scale-x-100"></span>
                    </a>

                    <a href="#" class="group relative py-7 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#3A2E30]">
                        Produits
                        <span class="absolute inset-x-0 bottom-[20px] h-px origin-left scale-x-0 bg-[#A9636F] transition-transform duration-300 group-hover:scale-x-100"></span>
                    </a>

                    <a href="#" class="group relative py-7 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#3A2E30]">
                        Cours
                        <span class="absolute inset-x-0 bottom-[20px] h-px origin-left scale-x-0 bg-[#A9636F] transition-transform duration-300 group-hover:scale-x-100"></span>
                    </a>
                </nav>

                <a
                    href="{{ url('/') }}"
                    class="flex min-w-0 items-center gap-3 justify-self-start lg:justify-self-center"
                    aria-label="BLACK MILK"
                >
                    <img
                        src="/img/logo.png"
                        alt=""
                        class="h-12 w-12 shrink-0 object-contain sm:h-14 sm:w-14"
                    >

                    <span class="min-w-0">
                        <span class="block whitespace-nowrap font-['Playfair_Display'] text-[21px] font-medium leading-none tracking-[0.12em] text-[#2D2729] sm:text-[24px]">
                            BLACK MILK
                        </span>
                        <span class="mt-1 block font-['Playfair_Display'] text-[12px] italic leading-none text-[#A9636F] sm:text-[13px]">
                            Le Premier Choix
                        </span>
                    </span>
                </a>

                <div class="hidden items-center justify-end gap-7 xl:gap-9 lg:flex">
                    <a href="#" class="group relative py-7 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#3A2E30]">
                        Où acheter
                        <span class="absolute inset-x-0 bottom-[20px] h-px origin-left scale-x-0 bg-[#A9636F] transition-transform duration-300 group-hover:scale-x-100"></span>
                    </a>

                    <a href="#" class="group relative py-7 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#3A2E30]">
                        Ambassadeur
                        <span class="absolute inset-x-0 bottom-[20px] h-px origin-left scale-x-0 bg-[#A9636F] transition-transform duration-300 group-hover:scale-x-100"></span>
                    </a>

                    <a
                        href="#"
                        class="inline-flex h-11 items-center gap-2 rounded-full bg-[#A9636F] px-5 text-[11px] font-semibold uppercase tracking-[0.14em] text-white transition hover:bg-[#945763]"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 9V7a5 5 0 0 1 10 0v2M5.5 9h13l1 11h-15l1-11Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Panier
                        <span class="opacity-70">·</span>
                        <span>0</span>
                    </a>
                </div>

                <div class="flex items-center justify-self-end gap-2 lg:hidden">
                    <a
                        href="#"
                        class="inline-flex h-10 min-w-10 items-center justify-center gap-1.5 rounded-full border border-[#3A2E30]/10 px-3 text-[11px] font-semibold"
                        aria-label="Panier, 0 article"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 9V7a5 5 0 0 1 10 0v2M5.5 9h13l1 11h-15l1-11Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>0</span>
                    </a>

                    <button
                        type="button"
                        @click="open = ! open"
                        :aria-expanded="open"
                        aria-controls="mobile-navigation"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#3A2E30] text-white"
                    >
                        <span class="sr-only">Menu</span>

                        <svg x-show="! open" class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 8h14M5 16h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>

                        <svg x-cloak x-show="open" class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m7 7 10 10M17 7 7 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div
                id="mobile-navigation"
                x-cloak
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                @click.outside="open = false"
                @keydown.escape.window="open = false"
                class="border-t border-[#3A2E30]/10 pb-3 pt-2 lg:hidden"
            >
                <nav class="grid">
                    <a href="#" class="flex items-center justify-between border-b border-[#3A2E30]/8 px-1 py-4 text-[12px] font-semibold uppercase tracking-[0.14em]">
                        Boutique
                        <span class="text-[#A9636F]">01</span>
                    </a>
                    <a href="#" class="flex items-center justify-between border-b border-[#3A2E30]/8 px-1 py-4 text-[12px] font-semibold uppercase tracking-[0.14em]">
                        Produits
                        <span class="text-[#A9636F]">02</span>
                    </a>
                    <a href="#" class="flex items-center justify-between border-b border-[#3A2E30]/8 px-1 py-4 text-[12px] font-semibold uppercase tracking-[0.14em]">
                        Cours
                        <span class="text-[#A9636F]">03</span>
                    </a>
                    <a href="#" class="flex items-center justify-between border-b border-[#3A2E30]/8 px-1 py-4 text-[12px] font-semibold uppercase tracking-[0.14em]">
                        Où acheter
                        <span class="text-[#A9636F]">04</span>
                    </a>
                    <a href="#" class="flex items-center justify-between px-1 py-4 text-[12px] font-semibold uppercase tracking-[0.14em]">
                        Ambassadeur
                        <span class="text-[#A9636F]">05</span>
                    </a>
                </nav>
            </div>
        </div>
    </div>
</header>
