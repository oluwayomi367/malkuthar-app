/**
 * Panier client — localStorage `mal_cart`, MÊME clé et MÊME forme d'objet
 * que l'ancien store Alpine (resources/js/app.js, toujours actif pour le
 * panier/checkout et les fiches produit non encore migrées). Ne jamais
 * changer la clé ni la forme sans migrer les deux côtés à la fois.
 *
 * `mal-cart-changed` est déclenché à chaque écriture pour que la pastille
 * du panier dans la navbar (React) se mette à jour dans le même onglet —
 * l'évènement navigateur `storage` ne se déclenche que dans les AUTRES
 * onglets.
 */
const KEY = 'mal_cart';

export function readCart() {
    try {
        return JSON.parse(localStorage.getItem(KEY) || '[]');
    } catch {
        return [];
    }
}

function write(items) {
    localStorage.setItem(KEY, JSON.stringify(items));
    window.dispatchEvent(new CustomEvent('mal-cart-changed'));
}

export function addToCart(item) {
    write([...readCart(), item]);
}

export function removeFromCart(index) {
    write(readCart().filter((_, i) => i !== index));
}

export function setCartQuantity(index, quantity) {
    const items = readCart();
    if (items[index]) {
        items[index].quantity = Math.max(1, quantity);
        write(items);
    }
}

export function clearCart() {
    localStorage.removeItem(KEY);
    window.dispatchEvent(new CustomEvent('mal-cart-changed'));
}

export function cartCount() {
    return readCart().length;
}
