<x-layout
    :title="$metaTitle"
    :meta-description="$metaDescription"
    :canonical="$canonical"
    robots="noindex, nofollow"
>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('checkoutPage', () => ({
                form: {
                    first_name: '',
                    last_name: '',
                    phone: '',
                    email: '',
                    country: '',
                    zip: '',
                    city: '',
                    address: '',
                    message: '',
                    privacy: false,
                },
                errors: {},
                isSubmitting: false,
                deliveryCost: 0,
                delivery: null,

                get isFreeDelivery() {
                    return this.delivery?.free_from !== null
                        && this.delivery?.free_from !== undefined
                        && Number(this.$store.cart.totalPrice) >= Number(this.delivery.free_from);
                },

                get totalWithDelivery() {
                    return Number(this.$store.cart.totalPrice)
                        + (this.isFreeDelivery ? 0 : Number(this.deliveryCost || 0));
                },

                get deliveryCostDisplay() {
                    if (! this.form.country) {
                        return '—';
                    }

                    if (this.isFreeDelivery) {
                        return @js(__('Offerte'));
                    }

                    return this.formatPrice(this.deliveryCost);
                },

                formatPrice(value) {
                    return new Intl.NumberFormat('fr-FR', {
                        style: 'currency',
                        currency: 'EUR',
                    }).format(Number(value || 0));
                },

                async updateDeliveryCost() {
                    this.errors.country = null;
                    this.delivery = null;
                    this.deliveryCost = 0;

                    if (! this.form.country) {
                        return;
                    }

                    try {
                        const response = await fetch(`/api/delivery-cost/${this.form.country}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const data = await response.json();

                        if (! response.ok || data.success !== true) {
                            this.errors.country = [data.message || @js(__('Livraison indisponible.'))];

                            return;
                        }

                        this.delivery = data;
                        this.deliveryCost = Number(data.price || 0);
                    } catch (error) {
                        this.errors.country = [@js(__('Impossible de calculer la livraison.'))];
                    }
                },

                async submit() {
                    if (this.isSubmitting || this.$store.cart.items.length === 0) {
                        return;
                    }

                    this.errors = {};
                    this.isSubmitting = true;

                    const body = new FormData();

                    Object.entries(this.form).forEach(([key, value]) => {
                        body.append(key, key === 'privacy' ? (value ? '1' : '0') : value);
                    });

                    body.append('cart_items', JSON.stringify(this.$store.cart.items));

                    try {
                        const response = await fetch(@js(route('checkout.store')), {
                            method: 'POST',
                            body,
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const data = await response.json();

                        if (! response.ok) {
                            this.errors = data.errors || {
                                general: @js(__('Impossible de créer la commande.')),
                            };

                            return;
                        }

                        this.$store.cart.clearCart();
                        window.location.href = data.redirect;
                    } catch (error) {
                        this.errors = {
                            general: @js(__('Impossible de créer la commande.')),
                        };
                    } finally {
                        this.isSubmitting = false;
                    }
                },
            }));
        });
    </script>

    <main class="px-3 py-10 sm:px-5 sm:py-14 lg:px-8 lg:py-16">
        <div class="mx-auto max-w-[1280px]" x-data="checkoutPage()">
            <div class="mb-8">
                <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                    {{ __('Commande') }}
                </p>

                <h1 class="mt-3 font-['Playfair_Display'] text-4xl font-medium tracking-tight text-zinc-900 sm:text-5xl">
                    {{ __('Finaliser la commande') }}
                </h1>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_420px] lg:items-start">
                <form @submit.prevent="submit()" class="space-y-5">
                    <section class="rounded-2xl bg-white p-6 sm:p-8">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#F6ECEE] text-[#A9636F]">
                                <x-lucide-user class="h-4 w-4" />
                            </span>

                            <h2 class="text-base font-semibold text-zinc-900">
                                {{ __('Coordonnées') }}
                            </h2>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <input
                                    type="text"
                                    x-model="form.first_name"
                                    placeholder="{{ __('Prénom') }}"
                                    class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[#A9636F]"
                                    required
                                >
                                <p x-show="errors.first_name" x-text="errors.first_name?.[0]" class="mt-1 text-xs text-red-500"></p>
                            </div>

                            <div>
                                <input
                                    type="text"
                                    x-model="form.last_name"
                                    placeholder="{{ __('Nom') }}"
                                    class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[#A9636F]"
                                >
                                <p x-show="errors.last_name" x-text="errors.last_name?.[0]" class="mt-1 text-xs text-red-500"></p>
                            </div>

                            <div>
                                <input
                                    type="tel"
                                    x-model="form.phone"
                                    placeholder="{{ __('Téléphone') }}"
                                    class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[#A9636F]"
                                    required
                                >
                                <p x-show="errors.phone" x-text="errors.phone?.[0]" class="mt-1 text-xs text-red-500"></p>
                            </div>

                            <div>
                                <input
                                    type="email"
                                    x-model="form.email"
                                    placeholder="{{ __('E-mail') }}"
                                    class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[#A9636F]"
                                >
                                <p x-show="errors.email" x-text="errors.email?.[0]" class="mt-1 text-xs text-red-500"></p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl bg-white p-6 sm:p-8">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#F6ECEE] text-[#A9636F]">
                                <x-lucide-map-pin class="h-4 w-4" />
                            </span>

                            <h2 class="text-base font-semibold text-zinc-900">
                                {{ __('Adresse de livraison') }}
                            </h2>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div class="relative sm:col-span-2">
                                <select
                                    x-model="form.country"
                                    @change="updateDeliveryCost()"
                                    class="w-full appearance-none rounded-xl border border-zinc-200 bg-white px-4 py-3 pr-10 text-sm text-zinc-700 outline-none transition focus:border-[#A9636F]"
                                    required
                                >
                                    <option value="" disabled>{{ __('Pays / zone de livraison') }}</option>

                                    @foreach ($deliveries as $delivery)
                                        <option value="{{ $delivery->code }}">
                                            {{ $delivery->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <x-lucide-chevron-down class="pointer-events-none absolute right-4 top-3.5 h-4 w-4 text-zinc-400" />

                                <p x-show="errors.country" x-text="errors.country?.[0]" class="mt-1 text-xs text-red-500"></p>

                                <div x-show="delivery" class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-zinc-400">
                                    <span x-text="delivery?.carrier"></span>
                                    <span x-show="delivery?.delay">·</span>
                                    <span x-text="delivery?.delay"></span>
                                </div>
                            </div>

                            <div>
                                <input
                                    type="text"
                                    x-model="form.city"
                                    placeholder="{{ __('Ville') }}"
                                    class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[#A9636F]"
                                    required
                                >
                                <p x-show="errors.city" x-text="errors.city?.[0]" class="mt-1 text-xs text-red-500"></p>
                            </div>

                            <div>
                                <input
                                    type="text"
                                    x-model="form.zip"
                                    placeholder="{{ __('Code postal') }}"
                                    class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[#A9636F]"
                                >
                                <p x-show="errors.zip" x-text="errors.zip?.[0]" class="mt-1 text-xs text-red-500"></p>
                            </div>

                            <div class="sm:col-span-2">
                                <textarea
                                    x-model="form.address"
                                    rows="3"
                                    placeholder="{{ __('Adresse complète') }}"
                                    class="w-full resize-none rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[#A9636F]"
                                    required
                                ></textarea>
                                <p x-show="errors.address" x-text="errors.address?.[0]" class="mt-1 text-xs text-red-500"></p>
                            </div>

                            <div class="sm:col-span-2">
                                <textarea
                                    x-model="form.message"
                                    rows="3"
                                    placeholder="{{ __('Commentaire pour la commande') }}"
                                    class="w-full resize-none rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[#A9636F]"
                                ></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl bg-white p-6 sm:p-8">
                        <label class="flex items-start gap-3 text-sm leading-6 text-zinc-600">
                            <input
                                type="checkbox"
                                x-model="form.privacy"
                                class="mt-1 h-4 w-4 rounded border-zinc-300"
                                required
                            >
                            <span>{{ __('J’accepte le traitement de mes données pour la gestion de cette commande.') }}</span>
                        </label>

                        <p x-show="errors.privacy" x-text="errors.privacy?.[0]" class="mt-2 text-xs text-red-500"></p>
                        <p x-show="errors.cart_items" x-text="errors.cart_items?.[0]" class="mt-2 text-xs text-red-500"></p>
                        <p x-show="errors.general" x-text="errors.general" class="mt-2 text-xs text-red-500"></p>

                        <button
                            type="submit"
                            :disabled="isSubmitting || $store.cart.items.length === 0"
                            class="mt-5 flex w-full items-center justify-center rounded-xl bg-[#A9636F] px-5 py-4 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-[#945763] disabled:cursor-not-allowed disabled:bg-zinc-300"
                        >
                            <span x-show="! isSubmitting">{{ __('Créer la commande') }}</span>
                            <span x-show="isSubmitting">{{ __('Traitement…') }}</span>
                        </button>

                        <div class="mt-6 border-t border-zinc-100 pt-5">
                            <div class="flex items-center justify-center gap-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                                    <x-lucide-lock-keyhole class="h-3.5 w-3.5" />
                                </span>

                                <div>
                                    <div class="text-xs font-medium text-zinc-900">
                                        {{ __('Secure payments') }}
                                    </div>
                                    <div class="mt-0.5 text-[10px] text-zinc-400">
                                        {{ __('Your payment information is protected') }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center justify-center gap-x-3 gap-y-3">
                                <img src="/img/visa.svg" alt="Visa" class="w-8">
                                <img src="/img/mastercard.svg" alt="Mastercard" class="w-8">
                                <img src="/img/amex.svg" alt="American Express" class="w-8">
                                <img src="/img/paypal.svg" alt="PayPal" class="w-8">
                                <img src="/img/apple-pay.svg" alt="Apple Pay" class="w-8">
                            </div>
                        </div>
                    </section>
                </form>

                <aside class="rounded-2xl bg-[#EFDDE0] p-6 lg:sticky lg:top-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold text-zinc-900">
                            {{ __('Votre commande') }}
                        </h2>
                        <span class="text-xs text-zinc-400" x-text="$store.cart.count + ' ×'"></span>
                    </div>

                    <template x-if="$store.cart.items.length === 0">
                        <div class="py-12 text-center">
                            <p class="text-sm text-zinc-500">{{ __('Votre panier est vide') }}</p>
                            <a href="{{ route('shop.index') }}" class="mt-3 inline-block text-sm font-medium text-[#A9636F]">
                                {{ __('Retour à la boutique') }}
                            </a>
                        </div>
                    </template>

                    <div class="mt-5 divide-y divide-[#D8C4C6]">
                        <template x-for="item in $store.cart.items" :key="item.id">
                            <article class="grid grid-cols-[64px_1fr_auto] gap-3 py-4 first:pt-0">
                                <img :src="item.image" :alt="item.name" class="h-16 w-16 rounded-lg object-cover">

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-zinc-900" x-text="item.name"></p>

                                    <template x-if="item.options?.length">
                                        <div class="mt-1 space-y-0.5">
                                            <template x-for="option in item.options" :key="option.id">
                                                <p class="text-[11px] text-zinc-400">
                                                    <span x-text="option.label"></span>
                                                    <span> · </span>
                                                    <span x-text="option.display"></span>
                                                </p>
                                            </template>
                                        </div>
                                    </template>

                                    <p class="mt-1 text-xs text-zinc-500">
                                        {{ __('Quantité') }}: <span x-text="item.quantity"></span>
                                    </p>
                                </div>

                                <p
                                    class="text-sm font-medium text-zinc-900"
                                    x-text="formatPrice(item.unit_price * item.quantity)"
                                ></p>
                            </article>
                        </template>
                    </div>

                    <div x-show="$store.cart.items.length > 0" class="mt-4 space-y-3 border-t border-[#D8C4C6] pt-4 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-600">{{ __('Sous-total') }}</span>
                            <span class="font-medium text-zinc-900" x-text="formatPrice($store.cart.totalPrice)"></span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-zinc-600">{{ __('Livraison') }}</span>
                            <span
                                class="font-medium"
                                :class="isFreeDelivery ? 'text-emerald-700' : 'text-zinc-900'"
                                x-text="deliveryCostDisplay"
                            ></span>
                        </div>

                        <div x-show="delivery?.free_from && ! isFreeDelivery" class="text-[11px] leading-5 text-[#7A5A60]">
                            <span>{{ __('Livraison offerte dès') }}</span>
                            <span x-text="formatPrice(delivery?.free_from)"></span>
                        </div>

                        <div class="flex items-center justify-between border-t border-[#D8C4C6] pt-3">
                            <span class="font-semibold text-zinc-900">{{ __('Total') }}</span>
                            <span class="text-base font-semibold text-zinc-900" x-text="formatPrice(totalWithDelivery)"></span>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-center gap-2 text-emerald-500">
                        <p class="bg-white py-2 px-6 rounded-full">
                            <x-lucide-shield-check class="h-4 w-4" />
                            <span class="text-xs font-medium">{{ __('Secure Checkout') }}</span>
                        </p>
                    </div>
                </aside>
            </div>
        </div>
    </main>
</x-layout>
