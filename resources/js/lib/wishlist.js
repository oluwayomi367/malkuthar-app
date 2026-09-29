const KEY = 'mal_wishlist';

export function readWishlist() {
    try {
        return JSON.parse(localStorage.getItem(KEY) || '[]');
    } catch {
        return [];
    }
}

export function isFavorite(id) {
    const list = readWishlist();
    return list.includes(id);
}

export function toggleFavorite(id) {
    const list = readWishlist();
    const index = list.indexOf(id);
    let next;
    if (index >= 0) {
        next = list.filter((item) => item !== id);
    } else {
        next = [...list, id];
    }
    localStorage.setItem(KEY, JSON.stringify(next));
    window.dispatchEvent(new CustomEvent('mal-wishlist-changed', { detail: { id, next } }));
    return index < 0;
}
