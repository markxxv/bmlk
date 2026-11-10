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

        async requestAvailability(productIds) {
            const response = await fetch('/api/cart/availability', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ product_ids: productIds }),
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
                    const ids = [...new Set(this.items.map(item => Number(item.product_id)).filter(id => Number.isInteger(id) && id > 0))];
                    const data = await this.requestAvailability(ids);
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

                    this.availabilityChecked = true;
                    return { ok: true, removed: removed.length > 0 };
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
            try {
                const data = await this.requestAvailability([Number(product.id)]);
                if (!(data.available_ids || []).map(Number).includes(Number(product.id))) {
                    this.availabilityNotice = 'Ce produit est temporairement indisponible.';
                    return false;
                }
            } catch {
                this.availabilityNotice = 'Impossible de vérifier la disponibilité du produit. Réessayez.';
                return false;
            }

            const options = Object.values(selections).filter(Boolean).sort((a, b) => String(a.id).localeCompare(String(b.id)));
            const signature = options.map(option => `${option.id}:${option.key}`).join('|');
            const itemId = `${product.id}|${signature || 'default'}`;
            const existing = this.items.find(item => item.id === itemId);
            const pricedOption = options.find(option => option.price !== null && option.price !== undefined);
            const unitPrice = pricedOption ? Number(pricedOption.price) : Number(product.price);

            if (existing) {
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
