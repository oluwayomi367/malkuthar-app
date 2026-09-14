import { useMemo, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import StorefrontLayout from '../../../Layouts/StorefrontLayout';
import Button from '../../../Components/ui/Button';
import Price from '../../../Components/commerce/Price';
import ProductGallery from '../../../Components/commerce/ProductGallery';
import Reassurance from '../../../Components/commerce/Reassurance';
import StockIndicator from '../../../Components/commerce/StockIndicator';
import ProductDescription, { Breadcrumb } from '../../../Components/commerce/ProductDescription';
import StickyCta from '../../../Components/layout/StickyCta';
import { addToCart } from '../../../lib/cart';
import { money } from '../../../lib/format';

const OPTION_LABELS = { taille: 'Taille', couleur: 'Couleur', modele: 'Modèle', format: 'Format', autre: 'Autre' };

export default function Physical({ product }) {
    const variants = product.variants ?? [];
    const hasVariants = variants.length > 0;
    const basePrice = product.promotional_price ?? product.price;
    const baseStock = product.stock_available;

    const optionGroups = useMemo(() => {
        const groups = {};
        for (const v of variants) {
            for (const [k, val] of Object.entries(v.options ?? {})) {
                if (val === null || val === '') continue;
                groups[k] ??= [];
                if (!groups[k].includes(val)) groups[k].push(val);
            }
        }
        return groups;
    }, [variants]);

    const [qty, setQty] = useState(1);
    const [selection, setSelection] = useState({});

    const current = hasVariants && Object.keys(selection).length > 0
        ? variants.find((v) => Object.entries(selection).every(([k, val]) => (v.options[k] ?? '') === val)) ?? null
        : null;

    const price = current ? current.price : basePrice;
    const stock = current ? current.stock : baseStock;
    const needsVariant = hasVariants && !current;
    const outOfStock = stock !== null && stock <= 0;
    const canAdd = !needsVariant && !outOfStock;

    const toggle = (key, val) => setSelection((s) => ({ ...s, [key]: s[key] === val ? undefined : val }));

    const add = () => {
        if (!canAdd) return;
        addToCart({
            product_id: product.id,
            variant_id: current ? current.id : null,
            name: product.name + (current ? ` — ${current.name}` : ''),
            type: 'physical', price, currency: product.currency, quantity: qty,
            variant_sku: current ? current.sku : null,
        });
        router.visit(route('cart'));
    };

    const buttonLabel = needsVariant ? 'Choisir une variante' : outOfStock ? 'Épuisé' : 'Ajouter au panier';

    return (
        <>
            <Head title={product.name}>
                <meta name="description" content={product.seo_description} />
                <script type="application/ld+json">{JSON.stringify({
                    '@context': 'https://schema.org', '@type': 'Product',
                    name: product.name, description: product.description_text, sku: product.sku, category: product.category,
                    offers: {
                        '@type': 'Offer', price: basePrice, priceCurrency: product.currency,
                        availability: (baseStock === null || baseStock > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    },
                })}</script>
            </Head>

            <div className="mx-auto max-w-[1180px] px-4 py-6 sm:px-6 lg:grid lg:grid-cols-[1.05fr_1fr] lg:gap-12 lg:py-10">
                <div className="lg:sticky lg:top-20 lg:self-start">
                    <Breadcrumb product={product} />
                    <div className="relative mt-3">
                        {current?.image ? (
                            <img src={current.image} alt={current.name} className="aspect-[4/5] w-full rounded-lg border border-line object-cover" />
                        ) : (
                            <ProductGallery product={product} />
                        )}
                    </div>
                </div>

                <div className="mt-6 lg:mt-0">
                    <h1 className="text-2xl font-bold tracking-[-0.01em] text-ink sm:text-[28px]">{product.name}</h1>
                    {product.sku && <p className="mt-1 font-mono text-xs text-ink-3">Réf. {product.sku}</p>}

                    <div className="mt-3">
                        {current ? (
                            <p className="text-2xl font-semibold text-ink">{money(price)} <span className="text-base text-ink-2">{product.currency}</span></p>
                        ) : (
                            <Price price={basePrice} compareAt={product.promotional_price ? product.price : null} currency={product.currency} size="lg" />
                        )}
                    </div>

                    {hasVariants && (
                        <div className="mt-6 space-y-4">
                            {Object.entries(optionGroups).map(([key, values]) => (
                                <div key={key}>
                                    <p className="mb-1.5 text-[13px] font-semibold text-ink-2">{OPTION_LABELS[key] ?? key[0].toUpperCase() + key.slice(1)}</p>
                                    <div className="flex flex-wrap gap-2">
                                        {values.map((val) => (
                                            <button key={val} type="button" onClick={() => toggle(key, val)}
                                                    className={['h-10 rounded-md border px-3 text-sm transition-colors', selection[key] === val ? 'border-ink bg-ink text-white' : 'border-line-strong text-ink hover:border-ink'].join(' ')}>
                                                {val}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            ))}
                            {needsVariant && <p className="text-[13px] text-rust">Choisissez une variante pour continuer.</p>}
                            {current && (
                                <p className="text-[13px] text-ink-2">
                                    Sélection : <span className="font-semibold text-ink">{current.name}</span>
                                    {current.sku && <span className="font-mono text-ink-3"> · {current.sku}</span>}
                                </p>
                            )}
                        </div>
                    )}

                    <div className="mt-5">
                        {hasVariants ? (
                            <p className={['text-[13px]', !current ? 'text-ink-2' : stock <= 0 ? 'text-rust' : stock <= 5 ? 'text-amber' : 'text-ink-2'].join(' ')}>
                                {current ? (stock <= 0 ? 'Variante épuisée' : stock <= 5 ? `Plus que ${stock} en stock` : 'En stock') : 'Sélectionnez une variante'}
                            </p>
                        ) : (
                            <StockIndicator available={stock} />
                        )}
                    </div>

                    <div className="mt-5 flex items-center gap-4">
                        <span className="text-[13px] font-semibold text-ink-2">Quantité</span>
                        <div className="inline-flex items-center rounded-md border border-line-strong/25">
                            <button type="button" onClick={() => setQty((q) => Math.max(1, q - 1))} className="flex h-11 w-11 items-center justify-center text-ink" aria-label="Retirer">−</button>
                            <span className="w-8 text-center text-sm font-semibold">{qty}</span>
                            <button type="button" onClick={() => setQty((q) => q + 1)} className="flex h-11 w-11 items-center justify-center text-ink" aria-label="Ajouter">+</button>
                        </div>
                    </div>

                    <div className="mt-6 hidden lg:block">
                        <Button variant="primary" full onClick={add} disabled={!canAdd}>{buttonLabel}</Button>
                    </div>
                </div>
            </div>

            <div className="mx-auto max-w-[1180px] px-4 pb-12 sm:px-6">
                <ProductDescription product={product} accordions={[
                    ['Dimensions & poids', (product.weight ? `${Number(product.weight).toFixed(2).replace('.', ',')} kg. ` : '') + "Détails complets sur la fiche d'expédition."],
                    ['Livraison & retours', 'Expédition suivie sous 48 h à 7 jours selon la destination. Retour possible sous 14 jours pour un article non ouvert.'],
                ]} />
                <Reassurance variant="physical" className="mt-10 border-t border-line pt-8" />
            </div>

            <StickyCta info={<span className="font-semibold text-ink">{money(price)} {product.currency}</span>}>
                <Button variant="primary" full onClick={add} disabled={!canAdd}>{buttonLabel}</Button>
            </StickyCta>
        </>
    );
}

Physical.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
