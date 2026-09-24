<div x-data x-cloak>
    <div
        x-show="$store.cart.isOpen"
        @click="$store.cart.close()"
        class="fixed inset-0 z-[90] bg-zinc-950/25 backdrop-blur-sm"
    ></div>

    <aside
        x-show="$store.cart.isOpen"
        x-effect="if ($store.cart.isOpen) $nextTick(() => $el.dispatchEvent(new CustomEvent('motion-toggle-reveal')))"
        @keydown.escape.window="$store.cart.close()"
        class="fixed inset-y-0 right-0 z-[100] flex w-full max-w-md flex-col bg-white shadow-2xl md:bottom-4 md:right-4 md:top-4 md:rounded-2xl md:border md:border-zinc-100"
        aria-label="{{ __('Panier') }}"
        data-toggle-reveal
        data-toggle-reveal-stagger="0.07"
        data-toggle-reveal-duration="0.68"
        data-toggle-reveal-y="16"
        data-toggle-reveal-blur="7"
    >
        <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-4" data-toggle-reveal-item>
            <h2 class="text-lg font-medium tracking-wide text-zinc-900">
                {{ __('Panier') }}
            </h2>

            <button
                type="button"
                @click="$store.cart.close()"
                class="flex h-9 w-9 items-center justify-center rounded-full text-zinc-400 transition hover:bg-zinc-50 hover:text-zinc-700"
                aria-label="{{ __('Fermer le panier') }}"
            >
                <x-lucide-x class="h-4 w-4" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            <template x-if="$store.cart.items.length === 0">
                <div class="flex h-full flex-col items-center justify-center px-6 py-16 text-center" data-toggle-reveal-item>
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-zinc-50 text-zinc-300">
                        <x-lucide-shopping-bag class="h-5 w-5" />
                    </span>

                    <p class="mt-4 text-sm font-medium text-zinc-900">
                        {{ __('Votre panier est vide') }}
                    </p>
                </div>
            </template>

            <div class="px-6 py-4">
                <template x-for="item in $store.cart.items" :key="item.id">
                    <article class="grid grid-cols-[72px_1fr_auto] gap-4 border-b border-zinc-100 py-4 last:border-b-0" data-toggle-reveal-item>
                        <a
                            :href="item.url"
                            @click="$store.cart.close()"
                            class="block h-[72px] w-[72px] overflow-hidden rounded-lg bg-zinc-50"
                        >
                            <img :src="item.image" :alt="item.name" class="h-full w-full object-cover">
                        </a>

                        <div class="min-w-0">
                            <a
                                :href="item.url"
                                @click="$store.cart.close()"
                                class="block truncate text-sm font-medium text-zinc-900 transition hover:text-zinc-600"
                                x-text="item.name"
                            ></a>

                            <template x-if="item.options?.length">
                                <div class="mt-1 space-y-0.5">
                                    <template x-for="option in item.options" :key="option.id">
                                        <p class="text-[11px] text-zinc-400">
                                            <span x-text="option.label"></span>
                                            <span> · </span>
                                            <span class="text-zinc-600" x-text="option.display"></span>
                                        </p>
                                    </template>
                                </div>
                            </template>

                            <p
                                class="mt-2 text-xs font-medium text-zinc-900"
                                x-text="new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(item.unit_price)"
                            ></p>

                            <div class="mt-2 inline-flex items-center gap-1 rounded-full border border-zinc-200 p-1">
                                <button
                                    type="button"
                                    @click="$store.cart.updateQuantity(item.id, item.quantity - 1)"
                                    class="flex h-6 w-6 items-center justify-center rounded-full text-zinc-500 transition hover:bg-zinc-50 hover:text-zinc-900"
                                    aria-label="{{ __('Réduire la quantité') }}"
                                >
                                    <x-lucide-minus class="h-3 w-3" />
                                </button>

                                <span class="min-w-6 text-center text-xs text-zinc-700" x-text="item.quantity"></span>

                                <button
                                    type="button"
                                    @click="$store.cart.updateQuantity(item.id, item.quantity + 1)"
                                    class="flex h-6 w-6 items-center justify-center rounded-full text-zinc-500 transition hover:bg-zinc-50 hover:text-zinc-900"
                                    aria-label="{{ __('Augmenter la quantité') }}"
                                >
                                    <x-lucide-plus class="h-3 w-3" />
                                </button>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="$store.cart.removeItem(item.id)"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-zinc-300 transition hover:bg-zinc-50 hover:text-zinc-600"
                            aria-label="{{ __('Supprimer') }}"
                        >
                            <x-lucide-x class="h-4 w-4" />
                        </button>
                    </article>
                </template>
            </div>
        </div>

        <div x-show="$store.cart.items.length > 0" class="border-t border-zinc-100 px-5 py-4" data-toggle-reveal-item>
            <div class="mb-3 flex items-center justify-between">
                <span class="text-xs font-medium uppercase tracking-wider text-zinc-500">
                    {{ __('Total') }}
                </span>

                <span
                    class="text-sm font-semibold text-zinc-900"
                    x-text="new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format($store.cart.totalPrice)"
                ></span>
            </div>

            <a
                href="{{ route('checkout') }}"
                @click="$store.cart.close()"
                class="block w-full rounded-lg bg-[#A9636F] px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-[#945763]"
            >
                {{ __('Passer commande') }}
            </a>
        </div>
    </aside>
</div>
