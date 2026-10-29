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
                    promo_code: '',
                    shipping_option: '',
                    shipping_contract_id: '',
                    shipping_quoted_price: '',
                    service_point_id: '',
                },
                errors: {},
                isSubmitting: false,
                paymentRetryUrl: null,
                savedOrder: null,
                deliveryCost: 0,
                shippingOptions: [],
                servicePoints: [],
                isCalculating: false,
                isLoadingPoints: false,
                shippingError: '',
                shippingRequest: null,
                shippingRevision: 0,
                promoPercent: null,
                promoError: '',
                promoOpen: false,

                get discountPreview() {
                    if (this.promoPercent === null) return 0;
                    return this.$store.cart.items.reduce((total, item) => {
                        const cents = Math.round(Number(item.unit_price) * 100);
                        const discounted = Math.round(cents * (100 - this.promoPercent) / 100);
                        return total + (cents - discounted) * Number(item.quantity || 1) / 100;
                    }, 0);
                },

                async updatePromo() {
                    this.promoPercent = null;
                    this.promoError = '';
                    const code = this.form.promo_code.trim().toUpperCase();
                    if (!code) return;

                    try {
                        const response = await fetch('/api/promo/' + encodeURIComponent(code), {
                            headers: { Accept: 'application/json' },
                        });
                        if (!response.ok) {
                            this.promoError = @js(__('Code promotionnel invalide.'));
                            return;
                        }
                        const data = await response.json();
                        if (this.form.promo_code.trim().toUpperCase() !== code) return;
                        this.form.promo_code = data.code;
                        this.promoPercent = Number(data.percent);
                    } catch (error) {
                        this.promoError = @js(__('Impossible de vérifier le code.'));
                    }
                },

                init() {
                    this.$watch('$store.cart.items', () => this.updateDeliveryCost());
                    this.$nextTick(() => {
                        if (typeof Intl.DisplayNames === 'function') {
                            const names = new Intl.DisplayNames(['fr'], { type: 'region' });
                            this.$el.querySelectorAll('[data-country-option]').forEach(option => {
                                option.textContent = names.of(option.value) || option.value;
                            });
                        }
                    });
                    try {
                        const saved = JSON.parse(localStorage.getItem('bm_unpaid_order') || 'null');
                        if (saved?.number && saved?.url) this.savedOrder = saved;
                    } catch (error) {
                        this.savedOrder = null;
                    }
                },

                rememberOrder(data) {
                    if (! data.order_number || ! data.status_url) return;

                    this.savedOrder = { number: data.order_number, url: data.status_url };
                    try {
                        if (data.onboarding_token) {
                            sessionStorage.setItem('bm_checkout_activation_' + data.order_number, data.onboarding_token);
                        }
                        localStorage.setItem('bm_unpaid_order', JSON.stringify(this.savedOrder));
                        sessionStorage.setItem('bm_pending_order', data.order_number);
                    } catch (error) {
                        // Browser storage can be disabled. Recovery by order number still works.
                    }
                },
                delivery: null,

                get selectedShipping() {
                    return this.shippingOptions.find(option =>
                        option.code === this.form.shipping_option &&
                        Number(option.contract_id) === Number(this.form.shipping_contract_id)
                    ) || null;
                },

                get isFreeDelivery() {
                    return !!this.delivery?.free && this.selectedShipping?.type !== 'express';
                },

                get totalWithDelivery() {
                    return Number(this.$store.cart.totalPrice) - this.discountPreview + Number(this.deliveryCost || 0);
                },

                get deliveryCostDisplay() {
                    if (!this.selectedShipping) return '—';
                    return this.isFreeDelivery ? @js(__('Offerte')) : this.formatPrice(this.deliveryCost);
                },

                formatPrice(value) {
                    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(Number(value || 0));
                },

                selectShipping(option) {
                    if (!option) return;
                    const previouslySelected = this.form.shipping_option === option.code &&
                        Number(this.form.shipping_contract_id) === Number(option.contract_id);
                    this.form.shipping_option = option.code;
                    this.form.shipping_contract_id = option.contract_id;
                    this.form.shipping_quoted_price = option.price;
                    this.deliveryCost = Number(option.price);
                    if (option.type !== 'pickup') {
                        this.form.service_point_id = '';
                        this.servicePoints = [];
                    } else if (!previouslySelected || !this.servicePoints.length) {
                        this.form.service_point_id = '';
                        this.loadPoints();
                    }
                },

                async updateDeliveryCost() {
                    this.shippingRevision++;
                    const revision = this.shippingRevision;
                    this.shippingRequest?.abort();
                    this.shippingOptions = [];
                    this.delivery = null;
                    this.deliveryCost = 0;
                    this.shippingError = '';
                    this.form.shipping_option = '';
                    this.form.shipping_contract_id = '';
                    this.form.shipping_quoted_price = '';
                    this.form.service_point_id = '';
                    this.servicePoints = [];

                    if (!this.form.country || !this.form.zip.trim() || !this.form.city.trim() ||
                        !this.form.address.trim() || !this.$store.cart.items.length) {
                        this.isCalculating = false;
                        return;
                    }

                    this.isCalculating = true;
                    this.shippingRequest = new AbortController();
                    try {
                        const response = await fetch('/api/sendcloud/quote', {
                            method: 'POST',
                            signal: this.shippingRequest.signal,
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                country: this.form.country,
                                zip: this.form.zip,
                                city: this.form.city,
                                address: this.form.address,
                                cart_items: this.$store.cart.items,
                            }),
                        });
                        const data = await response.json();
                        if (revision !== this.shippingRevision) return;
                        if (!response.ok) {
                            this.shippingError = Object.values(data.errors || {})[0]?.[0] ||
                                data.message || @js(__('Impossible de calculer la livraison.'));
                            return;
                        }
                        this.shippingOptions = data.options || [];
                        this.delivery = { free_from: data.free_from, free: data.free };
                        this.selectShipping(this.shippingOptions.find(option => option.type === 'home') ||
                            this.shippingOptions[0]);
                    } catch (error) {
                        if (error.name !== 'AbortError' && revision === this.shippingRevision) {
                            this.shippingError = @js(__('Impossible de calculer la livraison.'));
                        }
                    } finally {
                        if (revision === this.shippingRevision) this.isCalculating = false;
                    }
                },

                async loadPoints() {
                    const option = this.selectedShipping;
                    if (!option || option.type !== 'pickup') return;
                    const revision = this.shippingRevision;
                    this.isLoadingPoints = true;
                    this.servicePoints = [];
                    try {
                        const query = new URLSearchParams({
                            country: this.form.country,
                            zip: this.form.zip,
                            city: this.form.city,
                            carrier: option.carrier_code,
                        });
                        const response = await fetch('/api/sendcloud/points?' + query, {
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await response.json();
                        if (revision !== this.shippingRevision || this.selectedShipping?.code !== option.code) return;
                        if (!response.ok) throw new Error();
                        this.servicePoints = data.points || [];
                        if (!this.servicePoints.length) {
                            this.shippingError = @js(__('Aucun point relais trouvé à proximité.'));
                        }
                    } catch (error) {
                        if (revision === this.shippingRevision) {
                            this.shippingError = @js(__('Impossible de charger les points relais.'));
                        }
                    } finally {
                        if (revision === this.shippingRevision) this.isLoadingPoints = false;
                    }
                },

                async submit() {
                    if (this.isSubmitting || (this.$store.cart.items.length === 0 && ! this.paymentRetryUrl)) {
                        return;
                    }

                    this.errors = {};
                    if (!this.paymentRetryUrl && (this.isCalculating || !this.selectedShipping ||
                        (this.selectedShipping.type === 'pickup' && !this.form.service_point_id))) {
                        this.errors.shipping_option = [@js(__('Choisissez un mode de livraison et un point relais si nécessaire.'))];
                        return;
                    }
                    this.isSubmitting = true;

                    if (this.paymentRetryUrl) {
                        try {
                            const response = await fetch(this.paymentRetryUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });
                            const data = await response.json();

                            if (! response.ok || ! data.redirect) {
                                this.errors.general = data.message || @js(__('Le paiement est temporairement indisponible. Veuillez réessayer.'));
                                return;
                            }

                            window.location.href = data.redirect;
                        } catch (error) {
                            this.errors.general = @js(__('Le paiement est temporairement indisponible. Veuillez réessayer.'));
                        } finally {
                            this.isSubmitting = false;
                        }
                        return;
                    }

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
                            if (data.retry_url && data.order_number) {
                                this.paymentRetryUrl = data.retry_url;
                                this.rememberOrder(data);
                            }

                            this.errors = data.errors || {
                                general: data.message || @js(__('Impossible de démarrer le paiement.')),
                            };

                            return;
                        }

                        this.rememberOrder(data);
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

    <main class="px-3 pb-16 pt-10 sm:px-5 sm:pb-20 sm:pt-14 lg:px-8 lg:pb-24 lg:pt-16">
        <div class="mx-auto max-w-[1360px]" x-data="checkoutPage()">
            <div class="mb-9 sm:mb-11">
                <p class="text-xs font-semibold uppercase tracking-widest text-bmlk-brand">
                    {{ __('Commande') }}
                </p>

                <h1 class="mt-4 font-serif text-5xl font-medium leading-none tracking-tight text-zinc-900 sm:text-6xl">
                    {{ __('Finaliser la commande') }}
                </h1>

                <div x-show="savedOrder?.url" x-cloak class="mt-5 flex flex-wrap items-center gap-2 text-sm">
                    <x-lucide-receipt-text class="h-4 w-4 text-bmlk-brand" />
                    <span class="text-zinc-500">{{ __('Commande non finalisée :') }}</span>
                    <a :href="savedOrder?.url" class="font-medium text-bmlk-brand underline-offset-4 hover:underline">
                        <span x-text="savedOrder?.number"></span> · {{ __('Reprendre le paiement') }}
                    </a>
                </div>

                <a href="{{ route('order.recover') }}" class="mt-3 inline-flex items-center gap-1.5 text-xs text-zinc-500 transition hover:text-bmlk-brand">
                    <x-lucide-refresh-ccw class="h-3.5 w-3.5" />
                    {{ __('Déjà passé commande ? Retrouvez-la ici') }}
                </a>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_390px] lg:items-start xl:grid-cols-[minmax(0,1fr)_420px] xl:gap-8">
                <form @submit.prevent="submit()" class="min-w-0 rounded-3xl bg-white px-5 py-7 sm:px-9 sm:py-9 lg:px-10 lg:py-10">
                    <section class="border-b border-zinc-100 pb-9 sm:pb-10">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#F6ECEE] text-bmlk-brand">
                                <x-lucide-user class="h-4 w-4" />
                            </span>

                            <h2 class="font-serif text-2xl font-medium tracking-tight text-zinc-900">
                                {{ __('Coordonnées') }}
                            </h2>
                        </div>

                        <div class="mt-7 grid gap-x-4 gap-y-5 sm:grid-cols-2">
                            <div>
                                <div class="group relative">
                                    <x-lucide-user-round class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400 transition-colors group-focus-within:text-bmlk-brand" aria-hidden="true" />
                                    <input
                                        type="text"
                                        x-model="form.first_name"
                                        placeholder="{{ __('Prénom') }}"
                                        class="w-full rounded-xl border border-transparent bg-zinc-50 pl-11 pr-4 py-3.5 text-sm text-zinc-800 placeholder:text-zinc-400 outline-none transition hover:bg-zinc-100/70 focus:border-[#DDA1AA] focus:bg-white focus:ring-2 focus:ring-[#DDA1AA]/15"
                                        required
                                    >
                                </div>
                                <p x-show="errors.first_name" x-text="errors.first_name?.[0]" class="mt-1 text-xs text-red-500"></p>
                            </div>

                            <div>
                                <div class="group relative">
                                    <x-lucide-signature class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400 transition-colors group-focus-within:text-bmlk-brand" aria-hidden="true" />
                                    <input
                                        type="text"
                                        x-model="form.last_name"
                                        placeholder="{{ __('Nom') }}"
                                        class="w-full rounded-xl border border-transparent bg-zinc-50 pl-11 pr-4 py-3.5 text-sm text-zinc-800 placeholder:text-zinc-400 outline-none transition hover:bg-zinc-100/70 focus:border-[#DDA1AA] focus:bg-white focus:ring-2 focus:ring-[#DDA1AA]/15"
                                    >
                                </div>
                                <p x-show="errors.last_name" x-text="errors.last_name?.[0]" class="mt-1 text-xs text-red-500"></p>
                            </div>

                            <div>
                                <div class="group relative">
                                    <x-lucide-phone class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400 transition-colors group-focus-within:text-bmlk-brand" aria-hidden="true" />
                                    <input
                                        type="tel"
                                        x-model="form.phone"
                                        placeholder="{{ __('Téléphone') }}"
                                        class="w-full rounded-xl border border-transparent bg-zinc-50 pl-11 pr-4 py-3.5 text-sm text-zinc-800 placeholder:text-zinc-400 outline-none transition hover:bg-zinc-100/70 focus:border-[#DDA1AA] focus:bg-white focus:ring-2 focus:ring-[#DDA1AA]/15"
                                        required
                                    >
                                </div>
                                <p x-show="errors.phone" x-text="errors.phone?.[0]" class="mt-1 text-xs text-red-500"></p>
                            </div>

                            <div>
                                <div class="group relative">
                                    <x-lucide-mail class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400 transition-colors group-focus-within:text-bmlk-brand" aria-hidden="true" />
                                    <input
                                        type="email"
                                        x-model="form.email"
                                        placeholder="{{ __('E-mail') }}"
                                        autocomplete="email" required
                                        class="w-full rounded-xl border border-transparent bg-zinc-50 pl-11 pr-4 py-3.5 text-sm text-zinc-800 placeholder:text-zinc-400 outline-none transition hover:bg-zinc-100/70 focus:border-[#DDA1AA] focus:bg-white focus:ring-2 focus:ring-[#DDA1AA]/15"
                                    >
                                </div>
                                <p x-show="errors.email" x-text="errors.email?.[0]" class="mt-1 text-xs text-red-500"></p>
                            </div>
                        </div>
                    </section>

                    <section class="border-b border-zinc-100 py-9 sm:py-10">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#F6ECEE] text-bmlk-brand">
                                <x-lucide-map-pin class="h-4 w-4" />
                            </span>

                            <h2 class="font-serif text-2xl font-medium tracking-tight text-zinc-900">
                                {{ __('Adresse de livraison') }}
                            </h2>
                        </div>

                        <div class="mt-7 grid gap-x-4 gap-y-5 sm:grid-cols-2">
                            <div class="group relative sm:col-span-2">
                                <x-lucide-globe-2 class="pointer-events-none absolute left-4 top-4 h-4 w-4 text-zinc-400 transition-colors group-focus-within:text-bmlk-brand" aria-hidden="true" />
                                <select
                                    x-model="form.country"
                                    @change="updateDeliveryCost()"
                                    class="w-full appearance-none rounded-xl border border-transparent bg-zinc-50 pl-11 py-3.5 pr-10 text-sm text-zinc-700 outline-none transition hover:bg-zinc-100/70 focus:border-[#DDA1AA] focus:bg-white focus:ring-2 focus:ring-[#DDA1AA]/15"
                                    required
                                >
                                    <option value="" disabled>{{ __('Pays / zone de livraison') }}</option>

                                    @foreach (\App\Http\Controllers\SendcloudController::countries() as $code => $countryName)
                                        <option value="{{ $code }}" data-country-option>{{ $countryName }}</option>
                                    @endforeach
                                </select>

                                <x-lucide-chevron-down class="pointer-events-none absolute right-4 top-4 h-4 w-4 text-zinc-400" />

                                <p x-show="errors.country" x-text="errors.country?.[0]" class="mt-1 text-xs text-red-500"></p>

                            </div>

                            <div>
                                <div class="group relative">
                                    <x-lucide-building-2 class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400 transition-colors group-focus-within:text-bmlk-brand" aria-hidden="true" />
                                    <input
                                        type="text"
                                        x-model="form.city"
                                        @input.debounce.650ms="updateDeliveryCost()"
                                        placeholder="{{ __('Ville') }}"
                                        class="w-full rounded-xl border border-transparent bg-zinc-50 pl-11 pr-4 py-3.5 text-sm text-zinc-800 placeholder:text-zinc-400 outline-none transition hover:bg-zinc-100/70 focus:border-[#DDA1AA] focus:bg-white focus:ring-2 focus:ring-[#DDA1AA]/15"
                                        required
                                    >
                                </div>
                                <p x-show="errors.city" x-text="errors.city?.[0]" class="mt-1 text-xs text-red-500"></p>
                            </div>

                            <div>
                                <div class="group relative">
                                    <x-lucide-hash class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400 transition-colors group-focus-within:text-bmlk-brand" aria-hidden="true" />
                                    <input
                                        type="text"
                                        x-model="form.zip"
                                        @input.debounce.650ms="updateDeliveryCost()"
                                        required
                                        placeholder="{{ __('Code postal') }}"
                                        class="w-full rounded-xl border border-transparent bg-zinc-50 pl-11 pr-4 py-3.5 text-sm text-zinc-800 placeholder:text-zinc-400 outline-none transition hover:bg-zinc-100/70 focus:border-[#DDA1AA] focus:bg-white focus:ring-2 focus:ring-[#DDA1AA]/15"
                                    >
                                </div>
                                <p x-show="errors.zip" x-text="errors.zip?.[0]" class="mt-1 text-xs text-red-500"></p>
                            </div>

                            <div class="sm:col-span-2">
                                <div class="group relative">
                                    <x-lucide-map-pin class="pointer-events-none absolute left-4 top-4.5 h-4 w-4 text-zinc-400 transition-colors group-focus-within:text-bmlk-brand" aria-hidden="true" />
                                    <textarea
                                        x-model="form.address"
                                        @input.debounce.650ms="updateDeliveryCost()"
                                        rows="3"
                                        placeholder="{{ __('Adresse complète') }}"
                                        class="w-full resize-none rounded-xl border border-transparent bg-zinc-50 pl-11 pr-4 py-3.5 text-sm text-zinc-800 placeholder:text-zinc-400 outline-none transition hover:bg-zinc-100/70 focus:border-[#DDA1AA] focus:bg-white focus:ring-2 focus:ring-[#DDA1AA]/15"
                                        required
                                    ></textarea>
                                </div>
                                <p x-show="errors.address" x-text="errors.address?.[0]" class="mt-1 text-xs text-red-500"></p>
                            </div>

                            <div class="sm:col-span-2">
                                <div class="group relative">
                                    <x-lucide-message-square-text class="pointer-events-none absolute left-4 top-4.5 h-4 w-4 text-zinc-400 transition-colors group-focus-within:text-bmlk-brand" aria-hidden="true" />
                                    <textarea
                                        x-model="form.message"
                                        rows="3"
                                        placeholder="{{ __('Commentaire pour la commande') }}"
                                        class="w-full resize-none rounded-xl border border-transparent bg-zinc-50 pl-11 pr-4 py-3.5 text-sm text-zinc-800 placeholder:text-zinc-400 outline-none transition hover:bg-zinc-100/70 focus:border-[#DDA1AA] focus:bg-white focus:ring-2 focus:ring-[#DDA1AA]/15"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="border-b border-zinc-100 py-7 sm:py-8">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#F6ECEE] text-bmlk-brand">
                                <x-lucide-truck class="h-4 w-4" />
                            </span>
                            <h2 class="font-serif text-2xl font-medium tracking-tight text-zinc-900">Mode de livraison</h2>
                        </div>
                        <p x-show="!form.country || !form.zip || !form.city || !form.address"
                           class="mt-4 text-xs text-zinc-500">Renseignez votre adresse pour afficher les tarifs.</p>
                        <p x-show="isCalculating" class="mt-4 text-xs text-zinc-500">Calcul des tarifs Sendcloud…</p>
                        <p x-show="shippingError" x-text="shippingError" class="mt-4 text-xs text-red-500"></p>
                        <p x-show="errors.shipping_option" x-text="errors.shipping_option?.[0]" class="mt-2 text-xs text-red-500"></p>
                        <div class="mt-5 space-y-2" x-show="shippingOptions.length">
                            <template x-for="option in shippingOptions" :key="option.code + option.contract_id">
                                <button type="button" @click="selectShipping(option)"
                                    class="flex w-full items-center gap-3 rounded-xl bg-zinc-50 px-4 py-3 text-left transition hover:bg-zinc-100"
                                    :class="selectedShipping?.code === option.code && selectedShipping?.contract_id === option.contract_id ? 'ring-1 ring-bmlk-brand' : ''">
                                    <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border border-zinc-300">
                                        <span x-show="selectedShipping?.code === option.code && selectedShipping?.contract_id === option.contract_id"
                                            class="h-2 w-2 rounded-full bg-bmlk-brand"></span>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-medium text-zinc-800" x-text="option.carrier + ' · ' + option.name"></span>
                                        <span class="block text-[11px] text-zinc-500"
                                            x-text="option.type === 'pickup' ? 'Point relais' : option.type === 'express' ? 'Express' : 'À domicile'"></span>
                                    </span>
                                    <span class="shrink-0 text-sm font-semibold text-zinc-800"
                                        x-text="option.price === 0 ? 'Offerte' : formatPrice(option.price)"></span>
                                </button>
                            </template>
                        </div>
                        <div x-show="selectedShipping?.type === 'pickup'" class="mt-4 space-y-2">
                            <p class="text-xs font-medium text-zinc-700">Choisissez un point relais</p>
                            <p x-show="isLoadingPoints" class="text-xs text-zinc-500">Recherche des points relais…</p>
                            <template x-for="point in servicePoints" :key="point.id">
                                <button type="button" @click="form.service_point_id = point.id"
                                    class="flex w-full gap-3 rounded-lg bg-zinc-50 px-3 py-3 text-left hover:bg-zinc-100">
                                    <x-lucide-map-pin class="mt-0.5 h-4 w-4 shrink-0 text-bmlk-brand" />
                                    <span class="min-w-0 flex-1 text-xs">
                                        <span class="block font-medium" x-text="point.name"></span>
                                        <span class="block text-zinc-500" x-text="point.address"></span>
                                    </span>
                                    <x-lucide-check x-show="String(form.service_point_id) === String(point.id)" class="h-4 w-4 shrink-0 text-bmlk-brand" />
                                </button>
                            </template>
                            <p x-show="errors.service_point_id" x-text="errors.service_point_id?.[0]" class="text-xs text-red-500"></p>
                        </div>
                        <p x-show="delivery?.free_from && !isFreeDelivery"
                           class="mt-3 text-[11px] text-zinc-500">
                            Livraison standard offerte dès <span x-text="formatPrice(delivery?.free_from)"></span>.
                        </p>
                    </section>

                    <section class="pt-8 sm:pt-10">
                        <label class="flex items-start gap-3 text-sm leading-6 text-zinc-600">
                            <input
                                type="checkbox"
                                x-model="form.privacy"
                                class="mt-1 h-4 w-4 shrink-0 rounded border-zinc-300 accent-bmlk-brand"
                                required
                            >
                            <span>{{ __('J’accepte le traitement de mes données pour la gestion de cette commande.') }}</span>
                        </label>

                        <p x-show="errors.privacy" x-text="errors.privacy?.[0]" class="mt-2 text-xs text-red-500"></p>
                        <p x-show="errors.promo_code" x-text="errors.promo_code?.[0]" class="mt-2 text-xs text-red-500"></p>
                        <p x-show="errors.cart_items" x-text="errors.cart_items?.[0]" class="mt-2 text-xs text-red-500"></p>
                        <p x-show="errors.general" x-text="errors.general" class="mt-2 text-xs text-red-500"></p>

                        <button
                            type="submit"
                            :disabled="isSubmitting || isCalculating || ($store.cart.items.length === 0 && !paymentRetryUrl) || (!paymentRetryUrl && !selectedShipping)"
                            class="mt-7 flex w-full items-center justify-center rounded-full bg-bmlk-brand px-6 py-4 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#945763] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-bmlk-brand disabled:cursor-not-allowed disabled:bg-zinc-300"
                        >
                            <span x-show="!isSubmitting && !paymentRetryUrl">{{ __('Payer la commande') }}</span>
                            <span x-show="!isSubmitting && paymentRetryUrl">{{ __('Réessayer le paiement') }}</span>
                            <span x-show="isSubmitting">{{ __('Traitement…') }}</span>
                        </button>

                        <div class="mt-6 border-t border-zinc-100 pt-5">
                            <div class="flex items-center justify-center gap-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                    <x-lucide-lock-keyhole class="h-3.5 w-3.5" />
                                </span>

                                <div>
                                    <div class="text-xs font-medium text-zinc-900">
                                        {{ __('Paiement sécurisé par Stripe') }}
                                    </div>
                                    <div class="mt-0.5 text-[10px] text-zinc-400">
                                        {{ __('Vos informations bancaires sont traitées par Stripe.') }}
                                    </div>
                                </div>
                            </div>

                            <p class="mt-4 text-center text-[11px] text-zinc-400">
                                {{ __('Les moyens de paiement disponibles seront affichés sur Stripe.') }}
                            </p>
                        </div>
                    </section>
                </form>

                <aside class="min-w-0 rounded-3xl bg-white p-6 sm:p-8 lg:sticky lg:top-6">
                    <div class="flex items-center justify-between">
                        <h2 class="font-serif text-2xl font-medium tracking-tight text-zinc-900">
                            {{ __('Votre commande') }}
                        </h2>
                        <span class="rounded-full bg-zinc-50 px-3 py-1.5 text-xs font-medium text-zinc-500" x-text="$store.cart.count + ' ×'"></span>
                    </div>

                    <template x-if="$store.cart.items.length === 0">
                        <div class="py-12 text-center">
                            <p class="text-sm text-zinc-500">{{ __('Votre panier est vide') }}</p>
                            <a href="{{ route('shop.index') }}" class="mt-3 inline-block text-sm font-medium text-bmlk-brand">
                                {{ __('Retour à la boutique') }}
                            </a>
                        </div>
                    </template>

                    <div class="mt-6 divide-y divide-zinc-100">
                        <template x-for="item in $store.cart.items" :key="item.id">
                            <article class="grid grid-cols-[60px_minmax(0,1fr)_auto] gap-3 py-4 first:pt-0 sm:grid-cols-[72px_minmax(0,1fr)_auto] sm:gap-4">
                                <img :src="item.image" :alt="item.name" class="h-[60px] w-[60px] rounded-xl bg-zinc-50 object-cover sm:h-[72px] sm:w-[72px]">

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
                                    class="whitespace-nowrap text-sm font-semibold text-zinc-900"
                                    x-text="formatPrice(item.unit_price * item.quantity)"
                                ></p>
                            </article>
                        </template>
                    </div>

                    <div x-show="$store.cart.items.length > 0" class="mt-5 space-y-4 border-t border-zinc-100 pt-5 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-600">{{ __('Sous-total') }}</span>
                            <span class="font-medium text-zinc-900" x-text="formatPrice($store.cart.totalPrice)"></span>
                        </div>

                        <div class="pt-2">
                            <button
                                type="button"
                                @click="promoOpen = !promoOpen; if (promoOpen) $nextTick(() => $refs.promoInput?.focus())"
                                :aria-expanded="promoOpen.toString()"
                                aria-controls="order-promo-panel"
                                class="flex w-full items-center justify-between gap-3 border-b border-dashed border-zinc-300 pb-3 text-left text-sm text-zinc-600 transition-colors hover:border-bmlk-brand hover:text-bmlk-brand focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-bmlk-brand"
                            >
                                <span class="flex min-w-0 items-center gap-3">
                                    <x-lucide-ticket-percent class="h-4.5 w-4.5 shrink-0 text-bmlk-brand" aria-hidden="true" />
                                    <span>{{ __('Code promotionnel') }}</span>
                                </span>
                                <span class="flex items-center gap-2">
                                    <span x-show="promoPercent !== null" x-cloak x-text="promoPercent + ' %'" class="text-xs font-medium text-emerald-700"></span>
                                    <x-lucide-chevron-down class="h-4 w-4 shrink-0 text-zinc-400 transition-transform duration-200" x-bind:class="promoOpen ? 'rotate-180' : ''" aria-hidden="true" />
                                </span>
                            </button>
                            <div id="order-promo-panel" x-show="promoOpen" x-cloak x-transition.opacity.duration.150ms class="pt-4">
                                <label for="order-promo" class="sr-only">{{ __('Code promotionnel') }}</label>
                                <div class="group relative">
                                    <x-lucide-ticket class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400 transition-colors group-focus-within:text-bmlk-brand" aria-hidden="true" />
                                    <input
                                        id="order-promo"
                                        x-ref="promoInput"
                                        type="text"
                                        x-model="form.promo_code"
                                        @input.debounce.400ms="updatePromo()"
                                        maxlength="32"
                                        autocomplete="off"
                                        placeholder="{{ __('Votre code') }}"
                                        class="w-full rounded-xl border border-transparent bg-zinc-50 py-3 pl-11 pr-10 text-sm font-medium uppercase tracking-wide text-zinc-900 placeholder:font-normal placeholder:normal-case placeholder:tracking-normal placeholder:text-zinc-400 outline-none transition focus:border-[#DDA1AA] focus:bg-white focus:ring-2 focus:ring-[#DDA1AA]/15"
                                    >
                                    <x-lucide-check x-show="promoPercent !== null" x-cloak class="pointer-events-none absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 text-emerald-600" aria-hidden="true" />
                                </div>
                                <p x-show="promoError" x-text="promoError" role="status" class="mt-2 text-xs text-red-600"></p>
                                <p x-show="promoPercent !== null" role="status" class="mt-2 text-xs text-emerald-700">{{ __('Code appliqué :') }} <span x-text="promoPercent + ' %'"></span></p>
                            </div>
                        </div>

                        <div x-show="discountPreview > 0" class="flex items-center justify-between text-bmlk-brand">
                            <span>{{ __('Réduction') }}</span>
                            <span x-text="'− ' + formatPrice(discountPreview)"></span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-zinc-600">{{ __('Livraison') }}</span>
                            <span
                                class="font-medium"
                                :class="isFreeDelivery ? 'text-emerald-700' : 'text-zinc-900'"
                                x-text="deliveryCostDisplay"
                            ></span>
                        </div>

                        <div x-show="delivery?.free_from && ! isFreeDelivery" class="text-[11px] leading-5 text-zinc-500">
                            <span>{{ __('Livraison offerte dès') }}</span>
                            <span x-text="formatPrice(delivery?.free_from)"></span>
                        </div>

                        <div class="flex items-center justify-between border-t border-zinc-100 pt-5">
                            <span class="font-semibold text-zinc-900">{{ __('Total') }}</span>
                            <span class="font-serif text-2xl font-medium tracking-tight text-zinc-900" x-text="formatPrice(totalWithDelivery)"></span>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-center text-emerald-500">
                        <p class="bg-white py-2 px-6 rounded-full flex gap-2 items-center justify-center">
                            <x-lucide-shield-check class="h-4 w-4" />
                            <span class="text-xs font-medium">{{ __('Secure Checkout') }}</span>
                        </p>
                    </div>
                </aside>
            </div>
        </div>
    </main>
</x-layout>
