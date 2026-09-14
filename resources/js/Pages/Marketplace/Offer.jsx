import { useMemo, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import StorefrontLayout from '../../Layouts/StorefrontLayout';
import Button from '../../Components/ui/Button';
import OfferCard from '../../Components/commerce/OfferCard';
import { money } from '../../lib/format';

const TYPE_LABELS = { physical: 'Produit physique', digital: 'Produit digital', service: 'Service', training: 'Formation' };

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
        <form onSubmit={submit} className="mt-4">
            <div className="grid gap-3 sm:grid-cols-2">
                {Object.entries(axes).map(([axis, values]) => (
                    <label key={axis} className="text-sm">
                        <span className="text-ink-2 capitalize">{axis}</span>
                        <select value={sel[axis] ?? ''} onChange={(e) => setSel((s) => ({ ...s, [axis]: e.target.value }))}
                                className="mt-1 h-10 w-full rounded-xl border border-line bg-surface px-3 text-sm">
                            <option value="">Choisir…</option>
                            {values.map((val) => <option key={val} value={val}>{val}</option>)}
                        </select>
                    </label>
                ))}
            </div>
            {current && (
                <p className="mt-2 text-sm">
                    <span className="font-semibold text-ink">{money(current.price)} {offer.currency}</span>
                    <span className="ml-2 text-ink-3">{current.stock > 0 ? `${current.stock} en stock` : 'épuisé'}</span>
                </p>
            )}
            {chosen && !current && <p className="mt-2 text-sm text-rust">Cette combinaison n'est pas disponible.</p>}
            <div className="mt-4 flex items-center gap-3">
                <input type="number" min="1" max="20" value={data.quantity} onChange={(e) => setData('quantity', e.target.value)}
                       className="h-11 w-20 rounded-xl border border-line bg-surface px-3 text-sm" />
                <Button type="submit" variant="primary" full disabled={!current || current.stock <= 0 || processing}>Ajouter au panier</Button>
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
        <form onSubmit={submit} className="mt-4 flex items-center gap-3">
            <input type="number" min="1" max="20" value={data.quantity} onChange={(e) => setData('quantity', e.target.value)}
                   className="h-11 w-20 rounded-xl border border-line bg-surface px-3 text-sm" />
            <Button type="submit" variant="primary" full disabled={soldOut || processing}>{soldOut ? 'Épuisé' : 'Ajouter au panier'}</Button>
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
        <form onSubmit={submit} className="mb-6 max-w-lg rounded-xl border border-line bg-surface p-4">
            <label className="text-sm text-ink-2">Votre note
                <select value={data.rating} onChange={(e) => setData('rating', e.target.value)} className="ml-2 h-8 rounded border border-line bg-surface px-2 text-sm">
                    {[5, 4, 3, 2, 1].map((i) => <option key={i} value={i}>{i} ★</option>)}
                </select>
            </label>
            <input value={data.title} onChange={(e) => setData('title', e.target.value)} placeholder="Titre (facultatif)"
                   className="mt-2 h-9 w-full rounded-lg border border-line bg-surface px-2 text-sm" />
            <textarea value={data.body} onChange={(e) => setData('body', e.target.value)} rows={3} placeholder="Votre expérience…"
                      className="mt-2 w-full rounded-lg border border-line bg-surface px-2 py-1.5 text-sm" />
            <div className="mt-2"><Button type="submit" variant="secondary" disabled={processing}>Publier mon avis</Button></div>
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

            <div className="mx-auto max-w-[1180px] px-4 py-8 sm:px-6 sm:py-12">
                <nav className="text-xs text-ink-3">
                    <Link href={route('marketplace.shop')} className="hover:text-ink">Place de marché</Link>
                    {offer.category && (
                        <> / <Link href={route('marketplace.shop', { categorie: offer.category.slug })} className="hover:text-ink">{offer.category.name}</Link></>
                    )}
                </nav>

                <p className="mt-4 text-[11px] font-semibold uppercase tracking-[0.14em] text-accent">{TYPE_LABELS[offer.type] ?? offer.type}</p>
                <h1 className="mt-1 max-w-3xl font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-4xl">{offer.title}</h1>
                <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-ink-2">
                    <Link href={route('marketplace.shop.vendor', offer.vendor.slug)} className="font-medium text-ink hover:text-accent">{offer.vendor.display_name}</Link>
                    {offer.vendor.region && <span className="text-ink-3">{offer.vendor.region}</span>}
                    {offer.rating_count > 0 && (
                        <span className="flex items-center gap-1">
                            <span className="text-accent">{'★'.repeat(rating)}<span className="text-line-strong">{'★'.repeat(5 - rating)}</span></span>
                            {offer.rating_avg.toFixed(1).replace('.', ',')} ({offer.rating_count})
                        </span>
                    )}
                    {offer.sales_count > 0 && <span className="text-ink-3">{offer.sales_count} vendu·s</span>}
                </div>

                <div className="mt-6 grid gap-8 lg:grid-cols-[7fr_5fr] lg:gap-10">
                    <div>
                        <div className="overflow-hidden rounded-2xl border border-line bg-ground-alt">
                            <div className="aspect-square sm:aspect-[4/3]">
                                {activeImage ? (
                                    <img src={activeImage} alt={offer.title} className="h-full w-full object-cover" />
                                ) : (
                                    <div className="flex h-full items-center justify-center text-line-strong">
                                        <svg className="h-16 w-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1"><path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7" /></svg>
                                    </div>
                                )}
                            </div>
                        </div>
                        {offer.gallery_urls.length > 1 && (
                            <div className="mt-2 flex gap-2 overflow-x-auto pb-1">
                                {offer.gallery_urls.map((url) => (
                                    <button key={url} type="button" onClick={() => setActiveImage(url)}
                                            className={['h-16 w-16 shrink-0 overflow-hidden rounded-lg border-2 transition-colors', activeImage === url ? 'border-accent' : 'border-line'].join(' ')}>
                                        <img src={url} alt="" className="h-full w-full object-cover" />
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="lg:sticky lg:top-24 lg:self-start">
                        <div className="rounded-2xl border border-line bg-surface p-5 shadow-[0_10px_40px_-28px_rgba(46,44,40,0.25)]">
                            {offer.summary && <p className="text-sm text-ink-2">{offer.summary}</p>}

                            <div className="mt-4 flex items-end gap-3">
                                <span className="font-display text-3xl font-bold tracking-[-0.02em] text-ink">{money(offer.effective_price)}</span>
                                <span className="pb-1 text-sm text-ink-3">{offer.currency}</span>
                                {hasSale && (
                                    <>
                                        <span className="pb-1 text-sm text-ink-3 line-through">{money(offer.price)}</span>
                                        <span className="mb-1 rounded-full bg-rust px-2 py-0.5 text-[11px] font-bold text-white">−{off}%</span>
                                    </>
                                )}
                            </div>

                            {hasVariants
                                ? <VariantForm offer={offer} axes={axes} variants={offer.variants} />
                                : <SimpleForm offer={offer} soldOut={soldOutSimple} />}

                            <ul className="mt-4 space-y-1.5 border-t border-line pt-4 text-[13px] text-ink-2">
                                <li className="flex items-center gap-2"><svg className="h-4 w-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7"><path d="M9 12l2 2 4-4M12 3l7 4v5c0 5-3 7-7 9-4-2-7-4-7-9V7z" /></svg> Vendeur vérifié par MALKUTHAR</li>
                                <li className="flex items-center gap-2"><svg className="h-4 w-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7"><path d="M12 2l8 4v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6z" /></svg> Paiement protégé, litiges arbitrés</li>
                                <li className="flex items-center gap-2">
                                    <svg className="h-4 w-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                                    {offer.type === 'physical' ? 'Livraison suivie' : offer.type === 'digital' ? 'Livraison immédiate après paiement' : offer.type === 'service' ? 'Créneau réel confirmé' : 'Accès en ligne, à votre rythme'}
                                </li>
                            </ul>
                        </div>

                        <Link href={route('marketplace.shop.vendor', offer.vendor.slug)}
                              className="mt-3 flex items-center gap-3 rounded-2xl border border-line bg-surface p-4 transition-colors hover:border-line-strong">
                            <span className="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-ground-alt">
                                {offer.vendor.logo_url ? (
                                    <img src={offer.vendor.logo_url} alt="" className="h-full w-full object-cover" />
                                ) : (
                                    <span className="font-display font-bold text-ink-3">{offer.vendor.display_name.slice(0, 1)}</span>
                                )}
                            </span>
                            <span className="min-w-0">
                                <span className="block text-sm font-semibold text-ink">{offer.vendor.display_name}</span>
                                <span className="block text-xs text-ink-3">Voir la boutique →</span>
                            </span>
                        </Link>
                    </div>
                </div>

                <div className="mt-12">
                    <div className="flex gap-1 border-b border-line text-sm">
                        <button type="button" onClick={() => setTab('desc')} className={['border-b-2 px-3 py-2', tab === 'desc' ? 'border-accent font-semibold text-ink' : 'border-transparent text-ink-3'].join(' ')}>Description</button>
                        {specs.length > 0 && (
                            <button type="button" onClick={() => setTab('specs')} className={['border-b-2 px-3 py-2', tab === 'specs' ? 'border-accent font-semibold text-ink' : 'border-transparent text-ink-3'].join(' ')}>Caractéristiques</button>
                        )}
                        <button type="button" onClick={() => setTab('avis')} className={['border-b-2 px-3 py-2', tab === 'avis' ? 'border-accent font-semibold text-ink' : 'border-transparent text-ink-3'].join(' ')}>Avis ({offer.rating_count})</button>
                    </div>

                    {tab === 'desc' && <div className="prose-sm mt-5 max-w-2xl whitespace-pre-line text-ink-2">{offer.description}</div>}

                    {tab === 'specs' && specs.length > 0 && (
                        <dl className="mt-5 grid max-w-xl grid-cols-1 gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                            {specs.map((s) => (
                                <div key={s.label} className="flex justify-between border-b border-line py-1.5"><dt className="text-ink-3">{s.label}</dt><dd className="text-ink">{s.value}</dd></div>
                            ))}
                        </dl>
                    )}

                    {tab === 'avis' && (
                        <div className="mt-5">
                            {reviewableItem && <ReviewForm reviewableItem={reviewableItem} />}
                            <div className="space-y-4">
                                {reviews.length === 0 ? (
                                    <p className="text-sm text-ink-3">Pas encore d'avis.</p>
                                ) : reviews.map((r) => (
                                    <div key={r.id} className="border-b border-line pb-4">
                                        <p className="text-sm text-ink">
                                            {'★'.repeat(r.rating)}<span className="text-line-strong">{'★'.repeat(5 - r.rating)}</span>
                                            {r.title && <span className="ml-2 font-medium">{r.title}</span>}
                                        </p>
                                        {r.body && <p className="mt-1 text-sm text-ink-2">{r.body}</p>}
                                        <p className="mt-1 text-xs text-ink-3">{r.created_at}</p>
                                        {r.vendor_reply && (
                                            <p className="mt-2 rounded-lg bg-ground-alt/60 px-3 py-2 text-sm text-ink-2">
                                                <span className="font-medium text-ink">{offer.vendor.display_name} :</span> {r.vendor_reply}
                                            </p>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                {related.length > 0 && (
                    <div className="mt-14">
                        <h2 className="font-display text-lg font-semibold text-ink">Dans la même catégorie</h2>
                        <div className="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {related.map((r) => <OfferCard key={r.id} offer={r} />)}
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

Offer.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
