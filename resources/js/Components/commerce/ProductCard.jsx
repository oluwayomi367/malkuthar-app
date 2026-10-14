import { useState, useEffect } from 'react';
import { addToCart } from '../../lib/cart';
import { isFavorite, toggleFavorite } from '../../lib/wishlist';
import CatalogCard from './CatalogCard';

const TYPE_LABELS = { digital: 'Digital', physical: 'Physique', service: 'Service', training: 'Formation' };

export default function ProductCard({ product }) {
    const isAffiliable = product.type === 'digital' && product.is_affiliable;
    const price = product.promotional_price ?? product.price;
    const hasSale = product.promotional_price !== null && product.promotional_price !== undefined && Number(product.promotional_price) < Number(product.price);
    const discountPercent = hasSale ? Math.round(100 - (product.promotional_price / product.price) * 100) : 0;

    const favId = `prod_${product.id}`;
    const [fav, setFav] = useState(false);
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

        addToCart({
            // `product_id` : clé lue par Cart.jsx au paiement (même forme que les fiches produit).
            product_id: product.id,
            name: product.name,
            price: Number(price),
            currency: product.currency,
            quantity: 1,
            type: product.type,
            image: product.main_image ?? null,
        });

        setAdded(true);
        setTimeout(() => setAdded(false), 2000);
    };

    return (
        <CatalogCard
            href={route('catalog.show', product.slug)}
            title={product.name}
            image={product.main_image}
            seller={{ label: 'Maison MALKUTHAR', house: true }}
            typeLabel={TYPE_LABELS[product.type] ?? product.type}
            price={price}
            compareAt={hasSale ? product.price : null}
            currency={product.currency ?? 'FCFA'}
            discount={discountPercent}
            colors={product.colors ?? []}
            meta={(product.category || isAffiliable) ? (
                <>
                    {product.category && <span>{product.category}</span>}
                    {isAffiliable && <span className="font-semibold text-accent-2">Affiliable</span>}
                </>
            ) : null}
            fav={fav}
            onToggleFav={handleToggleFav}
            onAdd={handleAddToCart}
            added={added}
        />
    );
}
