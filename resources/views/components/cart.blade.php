<div x-data x-cloak>
    <div
        x-show="$store.cart.isOpen"
        @click="$store.cart.close()"
        class="fixed inset-0 z-[90] bg-zinc-950/25 backdrop-blur-sm"
    ></div>

    <aside
        x-show="$store.cart.isOpen"
        @keydown.escape.window="$store.cart.close()"
        class="fixed inset-y-0 right-0 z-[100] flex w-full max-w-md flex-col bg-[#FCF8F4] shadow-2xl"
        aria-label="{{ __('Panier') }}"
    >
        <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                    {{ __('Black Milk') }}
                </p>
                <h2 class="mt-1 font-['Playfair_Display'] text-3xl font-medium text-zinc-900">
                    {{ __('Panier') }}
                </h2>
            </div>

            <button
                type="button"
                @click="$store.cart.close()"
                class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-zinc-700 transition hover:text-zinc-950"
                aria-label="{{ __('Fermer le panier') }}"
            >
                <x-lucide-x class="h-4 w-4" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6">
            <template x-if="$store.cart.items.length === 0">
                <div class="flex h-full flex-col items-center justify-center py-16 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-[#EFDDE0] text-[#A9636F]">
                        <x-lucide-shopping-bag class="h-5 w-5" />
                    </span>
                    <p class="mt-5 font-['Playfair_Display'] text-2xl text-zinc-900">
                        {{ __('Votre panier est vide') }}
                    </p>
                </div>
            </template>

            <div>
                <template x-for="item in $store.cart.items" :key="item.id">
                    <article class="grid grid-cols-[72px_1fr_auto] gap-4 border-b border-zinc-200 py-5">
                        <a :href="item.url" @click="$store.cart.close()" class="block aspect-[3/4] overflow-hidden rounded-xl bg-white">
                            <img :src="item.image" :alt="item.name" class="h-full w-full object-cover">
                        </a>

                        <div class="min-w-0">
                            <a
                                :href="item.url"
                                @click="$store.cart.close()"
                                class="font-['Playfair_Display'] text-lg font-medium leading-tight text-zinc-900"
                                x-text="item.name"
                            ></a>

                            <template x-if="item.options?.length">
                                <div class="mt-2 space-y-1">
                                    <template x-for="option in item.options" :key="option.id">
                                        <p class="text-xs text-zinc-500">
                                            <span x-text="option.label"></span>
                                            <span> · </span>
                                            <span class="text-zinc-800" x-text="option.display"></span>
                                        </p>
                                    </template>
                                </div>
                            </template>

                            <p
                                class="mt-3 text-sm font-semibold text-zinc-900"
                                x-text="new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(item.unit_price)"
                            ></p>

                            <div class="mt-3 inline-flex items-center rounded-full bg-white p-1">
                                <button
                                    type="button"
                                    @click="$store.cart.updateQuantity(item.id, item.quantity - 1)"
                                    class="flex h-7 w-7 items-center justify-center rounded-full text-[#A9636F]"
                                >
                                    <x-lucide-minus class="h-3 w-3" />
                                </button>

                                <span class="min-w-7 text-center text-xs font-semibold" x-text="item.quantity"></span>

                                <button
                                    type="button"
                                    @click="$store.cart.updateQuantity(item.id, item.quantity + 1)"
                                    class="flex h-7 w-7 items-center justify-center rounded-full text-[#A9636F]"
                                >
                                    <x-lucide-plus class="h-3 w-3" />
                                </button>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="$store.cart.removeItem(item.id)"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-zinc-400 transition hover:text-[#A9636F]"
                            aria-label="{{ __('Supprimer') }}"
                        >
                            <x-lucide-x class="h-4 w-4" />
                        </button>
                    </article>
                </template>
            </div>
        </div>

        <div x-show="$store.cart.items.length > 0" class="border-t border-zinc-200 bg-white px-6 py-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-widest text-zinc-500">
                    {{ __('Total') }}
                </span>
                <span
                    class="font-['Playfair_Display'] text-2xl font-medium text-zinc-900"
                    x-text="new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format($store.cart.totalPrice)"
                ></span>
            </div>
        </div>
    </aside>
</div>
