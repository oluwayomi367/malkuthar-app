import { useMemo, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import StorefrontLayout from '../../Layouts/StorefrontLayout';
import Button from '../../Components/ui/Button';
import OfferCard from '../../Components/commerce/OfferCard';
import RosetteBadge from '../../Components/commerce/RosetteBadge';
import Reassurance from '../../Components/commerce/Reassurance';
import { Breadcrumb } from '../../Components/commerce/ProductDescription';
import ProductLayout, { ProductHeader, BuyBox, OptionGroup, QuantityInput } from '../../Components/commerce/ProductLayout';
import { money } from '../../lib/format';

const TYPE_LABELS = { physical: 'Physique', digital: 'Digital', service: 'Service', training: 'Formation' };

const DELIVERY = {
    physical: 'Expédition suivie par le vendeur',
    digital: 'Accès au fichier après paiement',
    service: 'Créneau confirmé avec le praticien',
    training: 'Accès au contenu de la formation après paiement',
};

function Stars({ value, className = 'h-4 w-4' }) {
    return (
        <span className="inline-flex items-center gap-0.5" aria-hidden="true">
            {[1, 2, 3, 4, 5].map((i) => (
                <svg key={i} className={[className, i <= value ? 'text-accent' : 'text-line-strong'].join(' ')} viewBox="0 0 24 24"
                     fill={i <= value ? 'currentColor' : 'none'} stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round">
                    <path d="M12 3.5l2.6 5.3 5.9.9-4.25 4.1 1 5.8L12 16.9l-5.25 2.7 1-5.8L3.5 9.7l5.9-.9z" />
                </svg>
            ))}
        </span>
    );
}

function QuantityStepper({ value, onChange, max = 20, disabled = false }) {
    const num = Number(value) || 1;
    const dec = () => !disabled && num > 1 && onChange(num - 1);
    const inc = () => !disabled && num < max && onChange(num + 1);

    return (
        <QuantityInput value={num} onDec={dec} onInc={inc} disabled={disabled} decDisabled={num <= 1} incDisabled={num >= max} />
    );
}

function VariantForm({ offer, axes, variants }) {
    const { data, setData, post, processing } = useForm({ offer_id: offer.id, variant_id: '', quantity: 1 });
    const [sel, setSel] = useState({});

    const current = variants.find((v) => Object.keys(axes).every((a) => v.options[a] === sel[a])) ?? null;
    const chosen = Object.keys(axes).every((a) => sel[a]);

    const submit = (e) => {
        e.preventDefault();
        post(route('marketplace.cart.add'), { data: { ...data, variant_id: current?.id ?? '' }, preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="mt-6 space-y-5">
            {Object.entries(axes).map(([axis, values]) => (
                <OptionGroup key={axis}
                             label={axis[0].toUpperCase() + axis.slice(1)}
                             values={values}
                             selected={sel[axis]}
                             onSelect={(val) => setSel((s) => ({ ...s, [axis]: val }))} />
            ))}

            {current && (
                <div className="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-ground-alt px-4 py-3 text-sm">
                    <span className="font-semibold text-ink">{money(current.price)} {offer.currency}</span>
                    <span className={['inline-flex items-center gap-2', current.stock > 0 ? 'text-ink-2' : 'font-medium text-rust'].join(' ')}>
                        <span className={['h-2 w-2 rounded-full', current.stock > 0 ? 'bg-sage' : 'bg-rust'].join(' ')} aria-hidden="true" />
                        {current.stock > 0 ? `${current.stock} disponible${current.stock > 1 ? 's' : ''}` : 'Épuisé'}
                    </span>
                </div>
            )}

            {chosen && !current && (
                <p className="rounded-xl border border-rust/25 bg-rust-tint px-4 py-3 text-sm text-rust">
                    Cette combinaison n'est pas disponible actuellement.
                </p>
            )}

            <div className="flex flex-wrap items-center gap-3">
                <QuantityStepper
                    value={data.quantity}
                    onChange={(q) => setData('quantity', q)}
                    disabled={!current || current.stock <= 0 || processing}
                />
                <Button
                    type="submit"
                    variant="primary"
                    disabled={!current || current.stock <= 0 || processing}
                    className="min-w-[12rem] flex-1"
                >
                    {chosen ? 'Ajouter au panier' : 'Choisissez une option'}
                </Button>
            </div>
        </form>
    );
}

function SimpleForm({ offer, soldOut }) {
    const { data, setData, post, processing } = useForm({ offer_id: offer.id, quantity: 1 });

    const submit = (e) => {
        e.preventDefault();
        post(route('marketplace.cart.add'), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="mt-6 flex flex-wrap items-center gap-3">
            <QuantityStepper
                value={data.quantity}
                onChange={(q) => setData('quantity', q)}
                disabled={soldOut || processing}
            />
            <Button
                type="submit"
                variant="primary"
                disabled={soldOut || processing}
                className="min-w-[12rem] flex-1"
            >
                {soldOut ? 'Offre épuisée' : 'Ajouter au panier'}
            </Button>
        </form>
    );
}

function ReviewForm({ reviewableItem }) {
    const { data, setData, post, processing, reset } = useForm({ order_item_id: reviewableItem.id, rating: 5, title: '', body: '' });

    const submit = (e) => {
        e.preventDefault();
        post(route('marketplace.reviews.store'), { preserveScroll: true, onSuccess: () => reset('title', 'body') });
    };

    const field = 'w-full rounded-xl border border-line-strong bg-surface px-3.5 text-base text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';

    return (
        <form onSubmit={submit} className="mb-8 rounded-2xl border border-line bg-surface p-5 sm:p-6">
            <h3 className="text-base font-semibold text-ink">Donner votre avis sur cette offre</h3>
            <fieldset className="mt-4">
                <legend className="text-sm font-medium text-ink-2">Votre note : {data.rating} sur 5</legend>
                <div className="mt-1 flex">
                    {[1, 2, 3, 4, 5].map((star) => (
                        <button
                            key={star}
                            type="button"
                            onClick={() => setData('rating', star)}
                            aria-label={`${star} sur 5`}
                            aria-pressed={star === data.rating}
                            className="inline-flex h-11 w-11 items-center justify-center rounded-full hover:bg-ground-alt"
                        >
                            <svg className={['h-6 w-6', star <= data.rating ? 'text-accent' : 'text-line-strong'].join(' ')} viewBox="0 0 24 24"
                                 fill={star <= data.rating ? 'currentColor' : 'none'} stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round" aria-hidden="true">
                                <path d="M12 3.5l2.6 5.3 5.9.9-4.25 4.1 1 5.8L12 16.9l-5.25 2.7 1-5.8L3.5 9.7l5.9-.9z" />
                            </svg>
                        </button>
                    ))}
                </div>
            </fieldset>
            <label className="mt-4 block text-sm font-medium text-ink-2" htmlFor="avis-titre">Titre (facultatif)</label>
            <input id="avis-titre" value={data.title} onChange={(e) => setData('title', e.target.value)} className={`mt-1.5 h-11 ${field}`} />
            <label className="mt-4 block text-sm font-medium text-ink-2" htmlFor="avis-texte">Votre avis</label>
            <textarea id="avis-texte" value={data.body} onChange={(e) => setData('body', e.target.value)} rows={4}
                      placeholder="Ce que vous avez apprécié, ce qui pourrait être amélioré…" className={`mt-1.5 py-3 ${field}`} />
            <div className="mt-4 flex justify-end">
                <Button type="submit" variant="primary" disabled={processing}>Publier mon avis</Button>
            </div>
        </form>
    );
}

export default function Offer({ offer, schema, related, reviews, reviewableItem }) {
    const [tab, setTab] = useState('desc');
    const [activeImage, setActiveImage] = useState(offer.gallery_urls[0] ?? null);

    const hasSale = offer.sale_price !== null && Number(offer.sale_price) < Number(offer.price);
    const off = hasSale ? Math.round(100 - (offer.sale_price / offer.price) * 100) : 0;
    const rating = Math.round(offer.rating_avg);
    const hasVariants = offer.variants.length > 0;
    const soldOutSimple = offer.type === 'physical' && !hasVariants && offer.detail && (offer.detail.stock - offer.detail.stock_reserved) <= 0;

    const axes = useMemo(() => {
        const a = {};
        for (const v of offer.variants) {
            for (const [axis, val] of Object.entries(v.options ?? {})) {
                a[axis] ??= [];
                if (!a[axis].includes(val)) a[axis].push(val);
            }
        }
        return a;
    }, [offer.variants]);

    const specs = schema
        .map((f) => {
            const val = offer.custom_fields?.[f.key];
            if (val === null || val === undefined || val === '') return null;
            return { label: f.label, value: Array.isArray(val) ? val.join(', ') : val };
        })
        .filter(Boolean);

    const tabs = [
        ['desc', 'Description'],
        ...(specs.length > 0 ? [['specs', 'Caractéristiques']] : []),
        ['avis', `Avis (${offer.rating_count})`],
    ];

    const gallery = (
        <div>
            <div className="relative overflow-hidden rounded-2xl border border-line bg-surface">
                {hasSale && (
                    <span className="absolute left-3 top-3 z-10"><RosetteBadge discount={off} /></span>
                )}
                <div className="aspect-square">
                    {activeImage ? (
                        <img src={activeImage} alt={offer.title} className="h-full w-full object-contain" />
                    ) : (
                        <div className="flex h-full items-center justify-center bg-ground-alt text-ink-3">
                            <svg className="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.3" aria-hidden="true">
                                <path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7" />
                            </svg>
                        </div>
                    )}
                </div>
            </div>
            {offer.gallery_urls.length > 1 && (
                <div className="mt-3 flex gap-2 overflow-x-auto pb-1">
                    {offer.gallery_urls.map((url, i) => (
                        <button
                            key={url}
                            type="button"
                            onClick={() => setActiveImage(url)}
                            aria-label={`Afficher la vue ${i + 1}`}
                            aria-pressed={activeImage === url}
                            className={['h-16 w-16 shrink-0 overflow-hidden rounded-xl border-2 bg-surface transition-colors',
                                activeImage === url ? 'border-ink' : 'border-line hover:border-line-strong'].join(' ')}
                        >
                            <img src={url} alt="" className="h-full w-full object-cover" />
                        </button>
                    ))}
                </div>
            )}
        </div>
    );

    return (
        <>
            <Head title={offer.title}>
                <meta name="description" content={offer.seo_description} />
            </Head>

            <ProductLayout
                breadcrumb={<Breadcrumb items={[
                    { label: 'Place de marché', href: route('marketplace.shop') },
                    ...(offer.category ? [{ label: offer.category.name, href: route('marketplace.shop', { categorie: offer.category.slug }) }] : []),
                    { label: offer.title },
                ]} />}
                gallery={gallery}
            >
                <ProductHeader
                    title={offer.title}
                    seller={{ label: offer.vendor.display_name, href: route('marketplace.shop.vendor', offer.vendor.slug) }}
                    typeLabel={TYPE_LABELS[offer.type] ?? offer.type}
                >
                    {offer.vendor.region && <span>{offer.vendor.region}</span>}
                    {offer.rating_count > 0 && (
                        <span className="inline-flex items-center gap-1.5">
                            <Stars value={rating} />
                            <span className="font-semibold text-ink">{offer.rating_avg.toFixed(1).replace('.', ',')}</span>
                            <span>({offer.rating_count} avis)</span>
                        </span>
                    )}
                    {offer.sales_count > 0 && (
                        <span>{offer.sales_count} commande{offer.sales_count > 1 ? 's' : ''}</span>
                    )}
                </ProductHeader>

                <BuyBox>
                    {offer.summary && (
                        <p className="mb-5 border-b border-line pb-5 text-base leading-[1.6] text-ink-2">{offer.summary}</p>
                    )}

                    <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <span className="font-display text-[32px] font-semibold leading-none text-ink">
                            {money(offer.effective_price)}
                        </span>
                        <span className="text-sm font-medium text-ink-3">{offer.currency}</span>
                        {hasSale && (
                            <span className="text-sm text-ink-3 line-through">
                                <span className="sr-only">Prix initial : </span>{money(offer.price)} {offer.currency}
                            </span>
                        )}
                    </div>

                    {hasVariants
                        ? <VariantForm offer={offer} axes={axes} variants={offer.variants} />
                        : <SimpleForm offer={offer} soldOut={soldOutSimple} />}

                    <Reassurance
                        layout="stack"
                        className="mt-6 border-t border-line pt-5"
                        items={[
                            ['support', 'Vendeur vérifié', 'Compte et offre relus par MALKUTHAR avant publication'],
                            ['pay', 'Paiement sécurisé', 'Encaissé par MALKUTHAR, litiges arbitrés'],
                            ['delivery', 'Livraison', DELIVERY[offer.type] ?? DELIVERY.digital],
                        ]}
                    />
                </BuyBox>

                <Link
                    href={route('marketplace.shop.vendor', offer.vendor.slug)}
                    className="mt-4 flex min-h-11 items-center gap-3 rounded-2xl border border-line bg-surface p-4 transition-colors hover:border-line-strong"
                >
                    <span className="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-line bg-ground-alt text-base font-semibold text-ink-2">
                        {offer.vendor.logo_url ? (
                            <img src={offer.vendor.logo_url} alt="" className="h-full w-full object-cover" />
                        ) : (
                            offer.vendor.display_name.slice(0, 1)
                        )}
                    </span>
                    <span className="min-w-0 flex-1">
                        <span className="block truncate text-base font-semibold text-ink">{offer.vendor.display_name}</span>
                        <span className="block text-sm text-ink-2">Voir toutes ses offres</span>
                    </span>
                    <svg className="h-5 w-5 shrink-0 text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                        <path d="M9 6l6 6-6 6" />
                    </svg>
                </Link>
            </ProductLayout>

            <div className="mx-auto max-w-[1280px] px-4 pb-16 pt-12 sm:px-6">
                <section className="border-t border-line pt-8">
                    <div className="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                        <div role="tablist" aria-label="Informations sur l'offre" className="flex w-max gap-1 border-b border-line sm:w-auto">
                            {tabs.map(([key, label]) => (
                                <button
                                    key={key}
                                    type="button"
                                    role="tab"
                                    id={`onglet-${key}`}
                                    aria-selected={tab === key}
                                    aria-controls={`panneau-${key}`}
                                    onClick={() => setTab(key)}
                                    className={['-mb-px inline-flex min-h-11 items-center whitespace-nowrap border-b-2 px-3 text-sm font-semibold transition-colors',
                                        tab === key ? 'border-ink text-ink' : 'border-transparent text-ink-2 hover:text-ink'].join(' ')}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="pt-8" role="tabpanel" id={`panneau-${tab}`} aria-labelledby={`onglet-${tab}`}>
                        {tab === 'desc' && (
                            <div className="max-w-prose whitespace-pre-line text-base leading-[1.7] text-ink-2">
                                {offer.description}
                            </div>
                        )}

                        {tab === 'specs' && specs.length > 0 && (
                            <dl className="max-w-prose divide-y divide-line border-y border-line">
                                {specs.map((s) => (
                                    <div key={s.label} className="flex flex-wrap justify-between gap-x-6 gap-y-1 py-3 text-base">
                                        <dt className="text-ink-2">{s.label}</dt>
                                        <dd className="font-medium text-ink">{s.value}</dd>
                                    </div>
                                ))}
                            </dl>
                        )}

                        {tab === 'avis' && (
                            <div className="max-w-prose">
                                {reviewableItem && <ReviewForm reviewableItem={reviewableItem} />}
                                {reviews.length === 0 ? (
                                    <p className="text-base text-ink-2">Aucun avis pour cette offre pour le moment.</p>
                                ) : (
                                    <ul className="divide-y divide-line border-y border-line">
                                        {reviews.map((r) => (
                                            <li key={r.id} className="py-5">
                                                <div className="flex flex-wrap items-center justify-between gap-2">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <Stars value={r.rating} />
                                                        <span className="sr-only">{r.rating} sur 5</span>
                                                        {r.title && <span className="text-base font-semibold text-ink">{r.title}</span>}
                                                    </div>
                                                    <span className="text-xs text-ink-3">{r.created_at}</span>
                                                </div>
                                                {r.body && <p className="mt-2 text-base leading-[1.7] text-ink-2">{r.body}</p>}
                                                {r.vendor_reply && (
                                                    <div className="mt-3 border-l-2 border-line-strong pl-4 text-sm text-ink-2">
                                                        <span className="font-semibold text-ink">Réponse de {offer.vendor.display_name} :</span> {r.vendor_reply}
                                                    </div>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        )}
                    </div>
                </section>

                {related.length > 0 && (
                    <section className="mt-16 border-t border-line pt-10" aria-labelledby="offres-liees">
                        <div className="flex flex-wrap items-end justify-between gap-3">
                            <h2 id="offres-liees" className="font-display text-[28px] font-semibold text-ink">Dans la même catégorie</h2>
                            <Link href={route('marketplace.shop', { categorie: offer.category?.slug })}
                                  className="inline-flex min-h-11 items-center text-sm font-semibold text-accent-2 underline decoration-accent/40 underline-offset-4 hover:decoration-accent-2">
                                Voir toute la catégorie
                            </Link>
                        </div>
                        <div className="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                            {related.map((r) => <OfferCard key={r.id} offer={r} />)}
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}

Offer.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
