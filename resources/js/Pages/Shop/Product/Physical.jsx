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
import ProductLayout, { ProductHeader, BuyBox, OptionGroup, QuantityInput } from '../../../Components/commerce/ProductLayout';

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

            <ProductLayout
                breadcrumb={<Breadcrumb product={product} />}
                gallery={current?.image ? (
                    <div className="overflow-hidden rounded-2xl border border-line bg-surface">
                        <img src={current.image} alt={`${product.name} — ${current.name}`} className="aspect-square w-full object-contain" />
                    </div>
                ) : (
                    <ProductGallery product={product} />
                )}
                below={
                    <ProductDescription product={product} accordions={[
                        ['Dimensions & poids', (product.weight ? `${Number(product.weight).toFixed(2).replace('.', ',')} kg. ` : '') + "Détails complets sur la fiche d'expédition."],
                        ['Livraison & retours', 'Expédition suivie sous 48 h à 7 jours selon la destination. Retour possible sous 14 jours pour un article non ouvert.'],
                    ]} />
                }
            >
                <ProductHeader title={product.name} seller={{ label: 'Maison MALKUTHAR', house: true }} typeLabel="Physique">
                    {product.category && <span>{product.category}</span>}
                    {product.sku && <span className="font-mono text-xs text-ink-3">Réf. {product.sku}</span>}
                </ProductHeader>

                <BuyBox>
                    {current ? (
                        <p className="font-display text-[32px] font-semibold leading-none text-ink">
                            {money(price)} <span className="font-sans text-sm font-medium text-ink-3">{product.currency}</span>
                        </p>
                    ) : (
                        <Price price={basePrice} compareAt={product.promotional_price ? product.price : null} currency={product.currency} size="display" />
                    )}

                    {hasVariants && (
                        <div className="mt-6 space-y-5">
                            {Object.entries(optionGroups).map(([key, values]) => (
                                <OptionGroup key={key}
                                             label={OPTION_LABELS[key] ?? key[0].toUpperCase() + key.slice(1)}
                                             values={values}
                                             selected={selection[key]}
                                             onSelect={(val) => toggle(key, val)} />
                            ))}
                            {current && (
                                <p className="text-sm text-ink-2">
                                    Sélection : <span className="font-semibold text-ink">{current.name}</span>
                                    {current.sku && <span className="font-mono text-xs text-ink-3"> · {current.sku}</span>}
                                </p>
                            )}
                        </div>
                    )}

                    <div className="mt-5">
                        {hasVariants ? (
                            <p className={['inline-flex items-center gap-2 text-sm', !current ? 'text-ink-2' : stock <= 0 ? 'font-medium text-rust' : stock <= 5 ? 'font-medium text-amber' : 'text-ink-2'].join(' ')}>
                                {current && <span className={['h-2 w-2 rounded-full', stock <= 0 ? 'bg-rust' : stock <= 5 ? 'bg-amber' : 'bg-sage'].join(' ')} aria-hidden="true" />}
                                {current ? (stock <= 0 ? 'Variante épuisée' : stock <= 5 ? `Plus que ${stock} en stock` : 'En stock') : 'Choisissez une variante pour continuer.'}
                            </p>
                        ) : (
                            <StockIndicator available={stock} />
                        )}
                    </div>

                    <div className="mt-5 flex items-center gap-4">
                        <span className="text-sm font-semibold text-ink">Quantité</span>
                        <QuantityInput value={qty}
                                       onDec={() => setQty((q) => Math.max(1, q - 1))}
                                       onInc={() => setQty((q) => q + 1)}
                                       decDisabled={qty <= 1} />
                    </div>

                    <div className="mt-6 hidden lg:block">
                        <Button variant="primary" full onClick={add} disabled={!canAdd}>{buttonLabel}</Button>
                    </div>

                    <Reassurance variant="physical" layout="stack" className="mt-6 border-t border-line pt-5" />
                </BuyBox>
            </ProductLayout>

            <StickyCta info={<span className="font-semibold text-ink">{money(price)} {product.currency}</span>}>
                <Button variant="primary" full onClick={add} disabled={!canAdd}>{buttonLabel}</Button>
            </StickyCta>
        </>
    );
}

Physical.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
