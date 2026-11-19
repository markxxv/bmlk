export function registerCartStore(Alpine) {
    Alpine.store('cart', {
        items: [],
        isOpen: false,
        initialized: false,
        checkingAvailability: false,
        availabilityChecked: false,
        availabilityNotice: '',
        _availabilityPromise: null,

        init() {
            if (this.initialized) return;

            try {
                const stored = JSON.parse(localStorage.getItem('bm_cart') || '[]');
                this.items = Array.isArray(stored) ? stored : [];
            } catch {
                this.items = [];
            }

            this.initialized = true;
            this.refreshAvailability();

            // Recheck an overnight cart when its tab becomes visible.
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) this.refreshAvailability();
            });
        },

        save() {
            localStorage.setItem('bm_cart', JSON.stringify(this.items));
        },

        async requestAvailability(productIds, items = []) {
            const response = await fetch('/api/cart/availability', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    product_ids: productIds,
                    cart_items: items.map(item => ({
                        product_id: Number(item.product_id),
                        quantity: Number(item.quantity || 1),
                        options: item.options || [],
                    })),
                }),
            });

            if (!response.ok) throw new Error('Availability check failed');
            return response.json();
        },

        async refreshAvailability() {
            if (this._availabilityPromise) return this._availabilityPromise;
            if (!this.items.length) {
                this.availabilityChecked = true;
                return { ok: true, removed: false };
            }

            this.checkingAvailability = true;
            this.availabilityChecked = false;
            this._availabilityPromise = (async () => {
                try {
                    const snapshot = this.items.filter(item => Number.isInteger(Number(item.product_id)) && Number(item.product_id) > 0);
                    const ids = [...new Set(snapshot.map(item => Number(item.product_id)))];
                    const data = await this.requestAvailability(ids, snapshot);
                    const prices = new Map(snapshot.map((item, index) => [item.id, data.cart_prices?.[index]]));
                    const checkedIds = new Set(ids);
                    const availableIds = new Set((data.available_ids || []).map(Number));
                    const removed = this.items.filter(item => {
                        const id = Number(item.product_id);
                        return !Number.isInteger(id) || id < 1 || (checkedIds.has(id) && !availableIds.has(id));
                    });

                    if (removed.length) {
                        this.items = this.items.filter(item => !removed.includes(item));
                        this.save();
                        const names = [...new Set(removed.map(item => item.name).filter(Boolean))];
                        this.availabilityNotice = (data.removed_message || 'Articles retirés du panier :') + ' ' + names.join(', ') + '.';
                    }

                    let changed = false;
                    const updated = this.items.map(item => {
                        const price = prices.get(item.id);
                        if (price == null || Number(price) === Number(item.unit_price)) return item;
                        changed = true;
                        return { ...item, unit_price: Number(price) };
                    });

                    if (changed) {
                        this.items = updated;
                        this.save();
                        if (!removed.length) this.availabilityNotice = data.prices_message;
                    }

                    this.availabilityChecked = true;
                    return { ok: true, removed: removed.length > 0, changed };
                } catch {
                    this.availabilityNotice = 'Impossible de vérifier la disponibilité du panier. Réessayez avant de payer.';
                    this.availabilityChecked = false;
                    return { ok: false, removed: false };
                } finally {
                    this.checkingAvailability = false;
                    this._availabilityPromise = null;
                }
            })();

            return this._availabilityPromise;
        },

        get count() {
            return this.items.reduce((total, item) => total + Number(item.quantity || 1), 0);
        },

        get totalPrice() {
            return this.items.reduce((total, item) => total + Number(item.unit_price || 0) * Number(item.quantity || 1), 0);
        },

        async addItem(product, selections = {}, quantity = 1) {
            const options = Object.values(selections).filter(Boolean).sort((a, b) => String(a.id).localeCompare(String(b.id)));
            let unitPrice;

            try {
                const data = await this.requestAvailability([Number(product.id)], [{
                    product_id: product.id, quantity, options,
                }]);
                if (!(data.available_ids || []).map(Number).includes(Number(product.id))) {
                    this.availabilityNotice = 'Ce produit est temporairement indisponible.';
                    this.isOpen = true;
                    return false;
                }
                if (data.cart_prices?.[0] == null) throw new Error('Price check failed');
                unitPrice = Number(data.cart_prices[0]);
            } catch {
                this.availabilityNotice = 'Impossible de vérifier la disponibilité du produit. Réessayez.';
                this.isOpen = true;
                return false;
            }

            const signature = options.map(option => `${option.id}:${option.key}`).join('|');
            const itemId = `${product.id}|${signature || 'default'}`;
            const existing = this.items.find(item => item.id === itemId);
            if (existing) {
                existing.unit_price = unitPrice;
                existing.quantity += Number(quantity || 1);
            } else {
                this.items.push({
                    id: itemId,
                    product_id: product.id,
                    name: product.name,
                    url: product.url,
                    image: product.image,
                    unit_price: unitPrice,
                    options,
                    quantity: Number(quantity || 1),
                });
            }

            this.availabilityNotice = '';
            this.availabilityChecked = true;
            this.save();
            this.isOpen = true;
            return true;
        },

        removeItem(itemId) {
            this.items = this.items.filter(item => item.id !== itemId);
            this.save();
        },

        updateQuantity(itemId, quantity) {
            const nextQuantity = Number(quantity);
            if (nextQuantity <= 0) return this.removeItem(itemId);
            const item = this.items.find(item => item.id === itemId);
            if (item) {
                item.quantity = nextQuantity;
                this.save();
            }
        },

        clearCart() {
            this.items = [];
            this.save();
        },

        toggle() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) this.refreshAvailability();
        },

        close() {
            this.isOpen = false;
        },
    });

    Alpine.store('cart').init();
}
