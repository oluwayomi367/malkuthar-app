import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { isFavorite, toggleFavorite } from '../../lib/wishlist';
import CatalogCard from './CatalogCard';

const TYPE_LABELS = {
    physical: 'Physique',
    digital: 'Digital',
    service: 'Service',
    training: 'Formation',
};

export default function OfferCard({ offer }) {
    const hasSale = offer.sale_price !== null && Number(offer.sale_price) < Number(offer.price);
    const off = hasSale ? Math.round(100 - (offer.sale_price / offer.price) * 100) : 0;

    const favId = `offer_${offer.id}`;
    const [fav, setFav] = useState(false);
    const [adding, setAdding] = useState(false);
    const [added, setAdded] = useState(false);

    useEffect(() => {
        setFav(isFavorite(favId));
        const handleWishlist = () => setFav(isFavorite(favId));
        window.addEventListener('mal-wishlist-changed', handleWishlist);
        return () => window.removeEventListener('mal-wishlist-changed', handleWishlist);
    }, [favId]);

    const handleToggleFav = (e) => {
        e.preventDefault();
        e.stopPropagation();
        const next = toggleFavorite(favId);
        setFav(next);
    };

    const handleAddToCart = (e) => {
        e.preventDefault();
        e.stopPropagation();

        // Si c'est un produit physique avec variantes possibles ou service sur RDV, rediriger vers la fiche
        if (offer.type === 'physical' || offer.type === 'service') {
            router.visit(route('marketplace.shop.offer', offer.slug));
            return;
        }

        setAdding(true);
        router.post(
            route('marketplace.cart.add'),
            { offer_id: offer.id, quantity: 1 },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setAdding(false);
                    setAdded(true);
                    setTimeout(() => setAdded(false), 2000);
                },
                onError: () => {
                    setAdding(false);
                    router.visit(route('marketplace.shop.offer', offer.slug));
                },
            }
        );
    };

    const needsChoice = offer.type === 'physical' || offer.type === 'service';

    return (
        <CatalogCard
            href={route('marketplace.shop.offer', offer.slug)}
            title={offer.title}
            image={offer.main_image_url}
            seller={offer.vendor?.display_name
                ? { label: offer.vendor.display_name, href: offer.vendor.slug ? route('marketplace.shop', { vendeur: offer.vendor.slug }) : null }
                : { label: 'Maison MALKUTHAR', house: true }}
            typeLabel={TYPE_LABELS[offer.type] ?? offer.type}
            price={offer.effective_price}
            compareAt={hasSale ? offer.price : null}
            currency={offer.currency}
            discount={off}
            colors={offer.colors ?? []}
            meta={offer.rating_count > 0 ? (
                <span>
                    <span className="font-semibold text-ink-2">{Number(offer.rating_avg).toFixed(1).replace('.', ',')} / 5</span>
                    {' '}({offer.rating_count} avis)
                </span>
            ) : null}
            fav={fav}
            onToggleFav={handleToggleFav}
            onAdd={handleAddToCart}
            addLabel={needsChoice ? 'Choisir' : 'Ajouter'}
            adding={adding}
            added={added}
        />
    );
}
