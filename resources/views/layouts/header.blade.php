<header
    x-data="{ open: false }"
    x-effect="document.body.classList.toggle('overflow-hidden', open)"
    class="relative z-50 px-3 pt-3 sm:px-5 sm:pt-5 lg:px-8 lg:pt-6"
>
    <div class="mx-auto max-w-[1560px]">
        <div class="relative rounded-full bg-white px-4 shadow-xl shadow-zinc-800/10 sm:px-5 lg:px-7">
            <div class="grid min-h-20 grid-cols-[1fr_auto] items-center gap-4 lg:grid-cols-[1fr_auto_1fr]">
                <nav class="hidden items-center gap-8 lg:flex">
                    <a href="{{ route('shop.index') }}" class="group relative py-7 text-xs font-semibold uppercase tracking-widest text-zinc-800">
                        {{ __('Produits') }}
                        <span class="absolute inset-x-0 bottom-5 h-px origin-left scale-x-0 bg-[#A9636F] transition-transform duration-300 group-hover:scale-x-100"></span>
                    </a>

                    <a href="{{ route('shop.category', ['slug' => 'soins-essentiels']) }}" class="group relative py-7 text-xs font-semibold uppercase tracking-widest text-zinc-800">
                        {{ __('Soins') }}
                        <span class="absolute inset-x-0 bottom-5 h-px origin-left scale-x-0 bg-[#A9636F] transition-transform duration-300 group-hover:scale-x-100"></span>
                    </a>

                    <a href="{{ route('shop.category', ['slug' => 'coupe-accessoires']) }}" class="group relative py-7 text-xs font-semibold uppercase tracking-widest text-zinc-800">
                        {{ __('Outils') }}
                        <span class="absolute inset-x-0 bottom-5 h-px origin-left scale-x-0 bg-[#A9636F] transition-transform duration-300 group-hover:scale-x-100"></span>
                    </a>
                </nav>

                <a
                    href="{{ url('/') }}"
                    class="flex min-w-0 items-center gap-3 justify-self-start lg:justify-self-center"
                    aria-label="{{ __('BLACK MILK') }}"
                >
                    <img src="/img/logo.png" alt="" class="h-12 w-12 shrink-0 object-contain sm:h-14 sm:w-14">

                    <span class="min-w-0">
                        <span class="block whitespace-nowrap font-['Playfair_Display'] text-xl font-medium leading-none tracking-widest text-zinc-900 sm:text-2xl">
                            {{ __('BLACK MILK') }}
                        </span>
                        <span class="mt-1 block font-['Playfair_Display'] text-xs italic leading-none text-[#A9636F] sm:text-sm">
                            {{ __('Le Premier Choix') }}
                        </span>
                    </span>
                </a>

                <div class="hidden items-center justify-end gap-8 lg:flex">
                    <a href="{{ route('where-to-buy') }}" class="group relative py-7 text-xs font-semibold uppercase tracking-widest text-zinc-800">
                        {{ __('Où acheter') }}
                        <span class="absolute inset-x-0 bottom-5 h-px origin-left scale-x-0 bg-[#A9636F] transition-transform duration-300 group-hover:scale-x-100"></span>
                    </a>

                    <a href="{{ route('events') }}" class="group relative py-7 text-xs font-semibold uppercase tracking-widest text-zinc-800">
                        {{ __('Événements') }}
                        <span class="absolute inset-x-0 bottom-5 h-px origin-left scale-x-0 bg-[#A9636F] transition-transform duration-300 group-hover:scale-x-100"></span>
                    </a>

                    <button
                        type="button"
                        @click="$store.cart.toggle()"
                        class="inline-flex h-11 items-center gap-2 rounded-full bg-[#A9636F] px-5 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#945763]"
                    >
                        <x-lucide-shopping-bag class="h-4 w-4" />
                        {{ __('Panier') }}
                        <span>·</span>
                        <span x-text="$store.cart.count">0</span>
                    </button>
                </div>

                <div class="flex items-center justify-self-end gap-2 lg:hidden">
                    <button
                        type="button"
                        @click="$store.cart.toggle()"
                        class="inline-flex h-10 min-w-10 items-center justify-center gap-2 rounded-full border border-zinc-200 px-3 text-xs font-semibold"
                        :aria-label="'{{ __('Panier') }} · ' + $store.cart.count"
                    >
                        <x-lucide-shopping-bag class="h-4 w-4" />
                        <span x-text="$store.cart.count">0</span>
                    </button>

                    <button
                        type="button"
                        @click="open = true"
                        :aria-expanded="open"
                        aria-controls="mobile-navigation"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-zinc-900 text-white"
                    >
                        <span class="sr-only">{{ __('Menu') }}</span>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 8h14M5 16h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div
        id="mobile-navigation"
        x-cloak
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @keydown.escape.window="open = false"
        class="fixed inset-0 z-50 bg-[#DDA1AA] lg:hidden"
    >
        <div class="flex h-full flex-col px-5 py-5">
            <div class="flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="{{ __('BLACK MILK') }}" @click="open = false">
                    <img src="/img/logo.png" alt="" class="h-12 w-12 object-contain">

                    <span>
                        <span class="block font-['Playfair_Display'] text-xl font-medium leading-none tracking-widest text-zinc-900">
                            {{ __('BLACK MILK') }}
                        </span>
                        <span class="mt-1 block font-['Playfair_Display'] text-xs italic text-zinc-700">
                            {{ __('Le Premier Choix') }}
                        </span>
                    </span>
                </a>

                <button
                    type="button"
                    @click="open = false"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-zinc-900 text-white"
                >
                    <span class="sr-only">{{ __('Fermer le menu') }}</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m7 7 10 10M17 7 7 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <nav class="flex flex-1 flex-col justify-center py-10">
                <a href="{{ route('shop.index') }}" @click="open = false" class="border-b border-[#C98792] py-4 font-['Playfair_Display'] text-4xl text-zinc-900">
                    {{ __('Produits') }}
                </a>
                <a href="{{ route('shop.category', ['slug' => 'soins-essentiels']) }}" @click="open = false" class="border-b border-[#C98792] py-4 font-['Playfair_Display'] text-4xl text-zinc-900">
                    {{ __('Soins') }}
                </a>
                <a href="{{ route('shop.category', ['slug' => 'coupe-accessoires']) }}" @click="open = false" class="border-b border-[#C98792] py-4 font-['Playfair_Display'] text-4xl text-zinc-900">
                    {{ __('Outils') }}
                </a>
                <a href="{{ route('where-to-buy') }}" @click="open = false" class="border-b border-[#C98792] py-4 font-['Playfair_Display'] text-4xl text-zinc-900">
                    {{ __('Où acheter') }}
                </a>
                <a href="{{ route('events') }}" @click="open = false" class="py-4 font-['Playfair_Display'] text-4xl text-zinc-900">
                    {{ __('Événements') }}
                </a>
            </nav>

            <div class="flex items-center justify-between border-t border-[#C98792] pt-5">
                <span class="text-xs font-semibold uppercase tracking-widest text-zinc-700">
                    {{ __('BLACK MILK France') }}
                </span>

                <button
                    type="button"
                    @click="open = false; $store.cart.toggle()"
                    class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-zinc-900"
                >
                    <x-lucide-shopping-bag class="h-4 w-4" />
                    {{ __('Panier') }} · <span x-text="$store.cart.count">0</span>
                </button>
            </div>
        </div>
    </div>
</header>
