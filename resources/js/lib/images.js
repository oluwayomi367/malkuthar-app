import { usePage } from '@inertiajs/react';

/**
 * Images de la vitrine, remplaçables depuis l'admin (Admin › Images de la vitrine).
 * Les URL viennent du back (prop partagée `images`, voir App\Support\StorefrontImages) ;
 * `fallback` ne sert que si la prop manque.
 */
export function useImages() {
    const { images = {} } = usePage().props;
    return (key, fallback = '') => images[key] ?? fallback;
}
