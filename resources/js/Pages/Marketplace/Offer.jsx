import { useMemo, useState, useEffect } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import StorefrontLayout from '../../Layouts/StorefrontLayout';
import Button from '../../Components/ui/Button';
import OfferCard from '../../Components/commerce/OfferCard';
import RosetteBadge from '../../Components/commerce/RosetteBadge';
import { isFavorite, toggleFavorite } from '../../lib/wishlist';
import { money } from '../../lib/format';

const TYPE_LABELS = { physical: 'Produit physique', digital: 'Produit digital', service: 'Service', training: 'Formation' };

function QuantityStepper({ value, onChange, max = 20, disabled = false }) {
    const num = Number(value) || 1;
    const dec = () => !disabled && num > 1 && onChange(num - 1);
    const inc = () => !disabled && num < max && onChange(num + 1);

    return (
        <div className="inline-flex h-11 items-center rounded-xl border border-line/90 bg-surface shadow-2xs">
            <button
                type="button"
                onClick={dec}
                disabled={disabled || num <= 1}
                className="flex h-full w-9 items-center justify-center text-ink-3 transition-colors hover:text-ink disabled:opacity-30"
                aria-label="Diminuer la quantité"
            >
                <svg className="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2">
                    <path d="M5 12h14" />
                </svg>
            </button>
            <span className="w-10 text-center font-mono text-sm font-semibold text-ink select-none">
                {num}
            </span>
            <button
                type="button"
                onClick={inc}
                disabled={disabled || num >= max}
                className="flex h-full w-9 items-center justify-center text-ink-3 transition-colors hover:text-ink disabled:opacity-30"
                aria-label="Augmenter la quantité"
            >
                <svg className="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2">
                    <path d="M12 5v14M5 12h14" />
                </svg>
            </button>
        </div>
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
        <form onSubmit={submit} className="mt-4 space-y-4">
            <div className="space-y-3">
                {Object.entries(axes).map(([axis, values]) => (
                    <div key={axis}>
                        <div className="flex items-center justify-between text-xs">
                            <span className="font-semibold uppercase tracking-wider text-ink-3 capitalize">{axis}</span>
                            {sel[axis] && <span className="font-medium text-accent">{sel[axis]}</span>}
                        </div>
                        <div className="mt-1.5 flex flex-wrap gap-2">
                            {values.map((val) => {
                                const isSelected = sel[axis] === val;
                                return (
                                    <button
                                        key={val}
                                        type="button"
                                        onClick={() => setSel((s) => ({ ...s, [axis]: val }))}
                                        className={`rounded-xl px-3 py-1.5 text-xs font-medium transition-all ${
                                            isSelected
                                                ? 'border border-accent bg-accent-soft/70 font-semibold text-accent ring-2 ring-accent/20'
                                                : 'border border-line/80 bg-surface text-ink-2 hover:border-line-strong hover:text-ink'
                                        }`}
                                    >
                                        {val}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </div>

            {current && (
                <div className="flex items-center justify-between rounded-xl bg-ground-alt/70 px-3.5 py-2.5 text-sm">
                    <span className="font-display font-bold text-ink">
                        {money(current.price)} {offer.currency}
                    </span>
                    <span className={`inline-flex items-center gap-1 text-xs font-medium ${current.stock > 0 ? 'text-brand-green' : 'text-rust'}`}>
                        <span className={`h-1.5 w-1.5 rounded-full ${current.stock > 0 ? 'bg-brand-green' : 'bg-rust'}`} />
                        {current.stock > 0 ? `${current.stock} disponible${current.stock > 1 ? 's' : ''}` : 'Épuisé'}
                    </span>
                </div>
            )}

            {chosen && !current && (
                <p className="rounded-lg bg-rust/10 p-2.5 text-xs font-medium text-rust">
                    Cette combinaison n'est pas disponible actuellement.
                </p>
            )}

            <div className="flex items-center gap-3 pt-1">
                <QuantityStepper
                    value={data.quantity}
                    onChange={(q) => setData('quantity', q)}
                    disabled={!current || current.stock <= 0 || processing}
                />
                <Button
                    type="submit"
                    variant="primary"
                    full
                    disabled={!current || current.stock <= 0 || processing}
                    className="h-11 flex-1 text-xs font-semibold uppercase tracking-wider"
                >
                    <svg className="mr-1.5 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    Ajouter au panier
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
        <form onSubmit={submit} className="mt-5 flex items-center gap-3">
            <QuantityStepper
                value={data.quantity}
                onChange={(q) => setData('quantity', q)}
                disabled={soldOut || processing}
            />
            <Button
                type="submit"
                variant="primary"
                full
                disabled={soldOut || processing}
                className="h-11 flex-1 text-xs font-semibold uppercase tracking-wider"
            >
                <svg className="mr-1.5 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
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

    return (
        <form onSubmit={submit} className="mb-6 max-w-lg rounded-2xl border border-line/90 bg-surface p-5 shadow-[0_4px_20px_-8px_rgba(46,44,40,0.06)]">
            <p className="font-display text-sm font-bold text-ink">Donnez votre avis sur cette offre</p>
            <div className="mt-3 flex items-center gap-2">
                <span className="text-xs font-medium text-ink-3">Votre note :</span>
                <div className="flex gap-1">
                    {[1, 2, 3, 4, 5].map((star) => (
                        <button
                            key={star}
                            type="button"
                            onClick={() => setData('rating', star)}
                            className="text-lg transition-transform hover:scale-125 focus:outline-none"
                        >
                            <span className={star <= data.rating ? 'text-accent' : 'text-line-strong'}>★</span>
                        </button>
                    ))}
                </div>
            </div>
            <input
                value={data.title}
                onChange={(e) => setData('title', e.target.value)}
                placeholder="Titre de votre avis (facultatif)"
                className="mt-3 h-10 w-full rounded-xl border border-line/90 bg-surface px-3 text-xs text-ink placeholder:text-ink-3/70 transition-all focus:border-accent focus:outline-none focus:ring-3 focus:ring-accent/15"
            />
            <textarea
                value={data.body}
                onChange={(e) => setData('body', e.target.value)}
                rows={3}
                placeholder="Racontez votre expérience avec cette offre…"
                className="mt-2.5 w-full rounded-xl border border-line/90 bg-surface p-3 text-xs text-ink placeholder:text-ink-3/70 transition-all focus:border-accent focus:outline-none focus:ring-3 focus:ring-accent/15"
            />
            <div className="mt-3 flex justify-end">
                <Button type="submit" variant="primary" disabled={processing} className="h-9 px-4 text-xs font-medium">
                    Publier mon avis
                </Button>
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

    return (
        <>
            <Head title={offer.title}>
                <meta name="description" content={offer.seo_description} />
            </Head>

            <div className="mx-auto max-w-[1240px] px-4 py-8 sm:px-6 sm:py-12">
                {/* Breadcrumbs */}
                <nav className="flex items-center gap-2 text-xs font-medium text-ink-3">
                    <Link href={route('marketplace.shop')} className="transition-colors hover:text-ink">Place de marché</Link>
                    {offer.category && (
                        <>
                            <svg className="h-3 w-3 text-ink-3/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M9 18l6-6-6-6" />
                            </svg>
                            <Link href={route('marketplace.shop', { categorie: offer.category.slug })} className="transition-colors hover:text-ink">
                                {offer.category.name}
                            </Link>
                        </>
                    )}
                    <svg className="h-3 w-3 text-ink-3/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M9 18l6-6-6-6" />
                    </svg>
                    <span className="line-clamp-1 text-ink-2 max-w-[280px]">{offer.title}</span>
                </nav>

                {/* Title & Metadata */}
                <div className="mt-4 flex flex-wrap items-center gap-2">
                    <span className="inline-flex items-center rounded-full border border-accent/30 bg-accent-soft/50 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-accent">
                        {TYPE_LABELS[offer.type] ?? offer.type}
                    </span>
                    {offer.category && (
                        <span className="rounded-full border border-line/80 bg-surface px-2.5 py-0.5 text-[11px] font-medium text-ink-2">
                            {offer.category.name}
                        </span>
                    )}
                </div>

                <h1 className="mt-2 max-w-3xl font-display text-2xl font-bold tracking-tight text-ink sm:text-4xl">
                    {offer.title}
                </h1>

                <div className="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-ink-2">
                    <Link href={route('marketplace.shop.vendor', offer.vendor.slug)} className="flex items-center gap-1.5 font-medium text-ink transition-colors hover:text-accent">
                        <span className="h-2 w-2 rounded-full bg-accent" />
                        {offer.vendor.display_name}
                    </Link>
                    {offer.vendor.region && (
                        <span className="inline-flex items-center gap-1 text-xs text-ink-3">
                            <svg className="h-3.5 w-3.5 text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M12 21s-8-7.5-8-12a8 8 0 1116 0c0 4.5-8 12-8 12z" />
                                <circle cx="12" cy="9" r="2.5" />
                            </svg>
                            {offer.vendor.region}
                        </span>
                    )}
                    {offer.rating_count > 0 && (
                        <span className="flex items-center gap-1 text-xs font-semibold">
                            <span className="text-accent">{'★'.repeat(rating)}<span className="text-line-strong">{'★'.repeat(5 - rating)}</span></span>
                            <span className="text-ink">{offer.rating_avg.toFixed(1).replace('.', ',')}</span>
                            <span className="text-ink-3 font-normal">({offer.rating_count} avis)</span>
                        </span>
                    )}
                    {offer.sales_count > 0 && (
                        <span className="text-xs text-ink-3">
                            • {offer.sales_count} commande{offer.sales_count > 1 ? 's' : ''} réalisée{offer.sales_count > 1 ? 's' : ''}
                        </span>
                    )}
                </div>

                {/* Main Content Grid */}
                <div className="mt-8 grid gap-8 lg:grid-cols-[7fr_5fr] lg:gap-12">
                    {/* Left: Gallery */}
                    <div>
                        <div className="relative overflow-hidden rounded-3xl border border-line/90 bg-ground-alt/60 shadow-[0_4px_24px_-10px_rgba(46,44,40,0.08)]">
                            {hasSale && (
                                <div className="absolute left-4 top-4 z-10">
                                    <RosetteBadge discount={off} />
                                </div>
                            )}
                            <div className="aspect-square sm:aspect-[4/3]">
                                {activeImage ? (
                                    <img src={activeImage} alt={offer.title} className="h-full w-full object-cover transition-transform duration-500 hover:scale-105" />
                                ) : (
                                    <div className="flex h-full items-center justify-center text-line-strong">
                                        <svg className="h-20 w-20 stroke-[1]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7" />
                                        </svg>
                                    </div>
                                )}
                            </div>
                        </div>
                        {offer.gallery_urls.length > 1 && (
                            <div className="mt-3 flex gap-2.5 overflow-x-auto pb-1">
                                {offer.gallery_urls.map((url) => (
                                    <button
                                        key={url}
                                        type="button"
                                        onClick={() => setActiveImage(url)}
                                        className={`h-16 w-16 shrink-0 overflow-hidden rounded-xl border-2 transition-all ${
                                            activeImage === url
                                                ? 'border-accent ring-2 ring-accent/20 shadow-xs'
                                                : 'border-line/80 hover:border-line-strong opacity-75 hover:opacity-100'
                                        }`}
                                    >
                                        <img src={url} alt="" className="h-full w-full object-cover" />
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Right: Purchase Box */}
                    <div className="lg:sticky lg:top-24 lg:self-start space-y-4">
                        <div className="rounded-3xl border border-line/90 bg-surface p-6 shadow-[0_8px_30px_-12px_rgba(46,44,40,0.1)]">
                            {offer.summary && (
                                <p className="text-sm text-ink-2 leading-relaxed border-b border-line/70 pb-4">
                                    {offer.summary}
                                </p>
                            )}

                            {/* Price */}
                            <div className="mt-4 flex items-baseline gap-2.5">
                                <span className="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">
                                    {money(offer.effective_price)}
                                </span>
                                <span className="text-sm font-semibold text-accent">{offer.currency}</span>
                                {hasSale && (
                                    <div className="flex items-center gap-2 ml-2">
                                        <span className="text-sm text-ink-3 line-through">{money(offer.price)}</span>
                                        <span className="rounded-full bg-rust px-2.5 py-0.5 text-xs font-bold text-white shadow-2xs">
                                            −{off}%
                                        </span>
                                    </div>
                                )}
                            </div>

                            {/* Form */}
                            {hasVariants
                                ? <VariantForm offer={offer} axes={axes} variants={offer.variants} />
                                : <SimpleForm offer={offer} soldOut={soldOutSimple} />}

                            {/* Trust Guarantees */}
                            <div className="mt-6 space-y-2.5 border-t border-line/70 pt-5">
                                <div className="flex items-center gap-3 text-xs text-ink-2">
                                    <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-accent-soft/60 text-accent">
                                        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                            <path d="M9 12l2 2 4-4M12 3l7 4v5c0 5-3 7-7 9-4-2-7-4-7-9V7z" />
                                        </svg>
                                    </span>
                                    <span>Vendeur vérifié & sélectionné par <strong>MALKUTHAR</strong></span>
                                </div>
                                <div className="flex items-center gap-3 text-xs text-ink-2">
                                    <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-accent-soft/60 text-accent">
                                        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                            <path d="M12 2l8 4v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6z" />
                                        </svg>
                                    </span>
                                    <span>Paiement protégé, service client & litiges arbitrés</span>
                                </div>
                                <div className="flex items-center gap-3 text-xs text-ink-2">
                                    <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-accent-soft/60 text-accent">
                                        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                            <path d="M5 12h14M13 6l6 6-6 6" />
                                        </svg>
                                    </span>
                                    <span>
                                        {offer.type === 'physical'
                                            ? 'Expédition suivie et remise soignée'
                                            : offer.type === 'digital'
                                            ? 'Téléchargement immédiat et accès à vie'
                                            : offer.type === 'service'
                                            ? 'Créneau réel confirmé avec le praticien'
                                            : 'Accès immédiat au contenu de la formation'}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Seller Card */}
                        <Link
                            href={route('marketplace.shop.vendor', offer.vendor.slug)}
                            className="group flex items-center justify-between rounded-2xl border border-line/90 bg-surface p-4 shadow-[0_2px_12px_-4px_rgba(46,44,40,0.04)] transition-all hover:border-accent hover:shadow-sm"
                        >
                            <div className="flex items-center gap-3 min-w-0">
                                <span className="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-line/80 bg-ground-alt font-display text-base font-bold text-ink">
                                    {offer.vendor.logo_url ? (
                                        <img src={offer.vendor.logo_url} alt="" className="h-full w-full object-cover" />
                                    ) : (
                                        offer.vendor.display_name.slice(0, 1)
                                    )}
                                </span>
                                <div className="min-w-0">
                                    <span className="block truncate text-sm font-bold text-ink group-hover:text-accent transition-colors">
                                        {offer.vendor.display_name}
                                    </span>
                                    <span className="block text-xs text-ink-3">
                                        Voir toutes les offres de ce créateur
                                    </span>
                                </div>
                            </div>
                            <svg className="h-4 w-4 text-ink-3 transition-transform group-hover:translate-x-1 group-hover:text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </div>

                {/* Tabs Details */}
                <div className="mt-14 border-t border-line/80 pt-8">
                    <div className="flex gap-4 border-b border-line text-sm">
                        <button
                            type="button"
                            onClick={() => setTab('desc')}
                            className={`relative pb-3 text-sm font-semibold transition-colors ${
                                tab === 'desc' ? 'text-accent' : 'text-ink-3 hover:text-ink'
                            }`}
                        >
                            Description complète
                            {tab === 'desc' && <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-accent" />}
                        </button>
                        {specs.length > 0 && (
                            <button
                                type="button"
                                onClick={() => setTab('specs')}
                                className={`relative pb-3 text-sm font-semibold transition-colors ${
                                    tab === 'specs' ? 'text-accent' : 'text-ink-3 hover:text-ink'
                                }`}
                            >
                                Caractéristiques
                                {tab === 'specs' && <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-accent" />}
                            </button>
                        )}
                        <button
                            type="button"
                            onClick={() => setTab('avis')}
                            className={`relative pb-3 text-sm font-semibold transition-colors ${
                                tab === 'avis' ? 'text-accent' : 'text-ink-3 hover:text-ink'
                            }`}
                        >
                            Avis clients ({offer.rating_count})
                            {tab === 'avis' && <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-accent" />}
                        </button>
                    </div>

                    <div className="py-6">
                        {tab === 'desc' && (
                            <div className="prose max-w-3xl whitespace-pre-line text-sm text-ink-2 leading-relaxed">
                                {offer.description}
                            </div>
                        )}

                        {tab === 'specs' && specs.length > 0 && (
                            <dl className="grid max-w-2xl grid-cols-1 gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                                {specs.map((s) => (
                                    <div key={s.label} className="flex justify-between border-b border-line/70 py-2">
                                        <dt className="text-xs font-semibold uppercase tracking-wider text-ink-3">{s.label}</dt>
                                        <dd className="font-medium text-ink">{s.value}</dd>
                                    </div>
                                ))}
                            </dl>
                        )}

                        {tab === 'avis' && (
                            <div className="max-w-2xl">
                                {reviewableItem && <ReviewForm reviewableItem={reviewableItem} />}
                                <div className="space-y-4">
                                    {reviews.length === 0 ? (
                                        <div className="rounded-2xl border border-line/70 bg-ground-alt/40 p-6 text-center text-sm text-ink-3">
                                            Aucun avis n'a encore été déposé pour cette offre. Soyez le premier à partager votre retour d'expérience !
                                        </div>
                                    ) : (
                                        reviews.map((r) => (
                                            <div key={r.id} className="rounded-2xl border border-line/80 bg-surface p-5 shadow-2xs">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-accent text-sm">
                                                            {'★'.repeat(r.rating)}<span className="text-line-strong">{'★'.repeat(5 - r.rating)}</span>
                                                        </span>
                                                        {r.title && <span className="font-semibold text-ink text-sm">{r.title}</span>}
                                                    </div>
                                                    <span className="text-xs text-ink-3">{r.created_at}</span>
                                                </div>
                                                {r.body && <p className="mt-2 text-xs text-ink-2 leading-relaxed">{r.body}</p>}
                                                {r.vendor_reply && (
                                                    <div className="mt-3 rounded-xl border border-accent/20 bg-accent-soft/40 p-3 text-xs text-ink-2">
                                                        <span className="font-semibold text-accent">{offer.vendor.display_name} :</span> {r.vendor_reply}
                                                    </div>
                                                )}
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Related Offers */}
                {related.length > 0 && (
                    <div className="mt-16 border-t border-line/80 pt-10">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wider text-accent">Découvrir aussi</p>
                                <h2 className="mt-1 font-display text-xl font-bold text-ink sm:text-2xl">Dans la même catégorie</h2>
                            </div>
                            <Link href={route('marketplace.shop', { categorie: offer.category?.slug })} className="text-xs font-semibold text-accent hover:underline">
                                Tout voir →
                            </Link>
                        </div>
                        <div className="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                            {related.map((r) => <OfferCard key={r.id} offer={r} />)}
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

Offer.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
