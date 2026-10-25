import './bootstrap';
import './consent';

// Stores Alpine partagés — panier (localStorage) + tiroir de navigation.
document.addEventListener('alpine:init', () => {
    const read = () => {
        try {
            return JSON.parse(localStorage.getItem('mal_cart') || '[]');
        } catch (e) {
            return [];
        }
    };

    window.Alpine.store('drawer', {
        open: false,
        toggle() { this.open = !this.open; },
        close() { this.open = false; },
    });

    window.Alpine.store('cart', {
        items: read(),
        get count() { return this.items.length; },
        sync() { this.items = read(); },
        add(item) {
            this.items = [...read(), item];
            localStorage.setItem('mal_cart', JSON.stringify(this.items));
        },
        removeAt(index) {
            this.items = read().filter((_, i) => i !== index);
            localStorage.setItem('mal_cart', JSON.stringify(this.items));
        },
        clear() {
            this.items = [];
            localStorage.removeItem('mal_cart');
        },
    });
});

window.addEventListener('storage', (e) => {
    if (e.key === 'mal_cart') window.Alpine?.store('cart')?.sync();
});
