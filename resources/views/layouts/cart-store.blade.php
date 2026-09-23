<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('cart', {
            items: [],
            isOpen: false,
            initialized: false,

            init() {
                if (this.initialized) {
                    return;
                }

                try {
                    const stored = localStorage.getItem('bm_cart');

                    if (stored) {
                        this.items = JSON.parse(stored);
                    }
                } catch {
                    this.items = [];
                }

                this.initialized = true;
            },

            save() {
                localStorage.setItem('bm_cart', JSON.stringify(this.items));
            },

            get count() {
                return this.items.reduce((total, item) => total + Number(item.quantity || 1), 0);
            },

            get totalPrice() {
                return this.items.reduce(
                    (total, item) => total + (Number(item.unit_price || 0) * Number(item.quantity || 1)),
                    0
                );
            },

            addItem(product, selections = {}, quantity = 1) {
                const options = Object.values(selections)
                    .filter(Boolean)
                    .sort((a, b) => String(a.id).localeCompare(String(b.id)));

                const signature = options
                    .map(option => `${option.id}:${option.key}`)
                    .join('|');

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

                this.save();
                this.isOpen = true;
            },

            removeItem(itemId) {
                this.items = this.items.filter(item => item.id !== itemId);
                this.save();
            },

            updateQuantity(itemId, quantity) {
                const nextQuantity = Number(quantity);

                if (nextQuantity <= 0) {
                    this.removeItem(itemId);
                    return;
                }

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
            },

            close() {
                this.isOpen = false;
            },
        });

        Alpine.store('cart').init();
    });
</script>
