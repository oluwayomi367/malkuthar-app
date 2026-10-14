import { useState } from 'react';
import { Head } from '@inertiajs/react';
import StorefrontLayout from '../Layouts/StorefrontLayout';
import Button from '../Components/ui/Button';
import EmptyState from '../Components/ui/EmptyState';
import { readCart, removeFromCart, setCartQuantity, clearCart } from '../lib/cart';
import { money } from '../lib/format';

const TYPE_LABELS = { digital: 'Digital', physical: 'Physique', service: 'Service' };

export default function Cart() {
    const [cart, setCart] = useState(() => readCart());
    const [email, setEmail] = useState('');
    const [provider, setProvider] = useState('fedapay');
    const [method] = useState('mobile_money');
    const [coupon, setCoupon] = useState('');
    const [ship, setShip] = useState({ first_name: '', last_name: '', phone: '', country: '', city: '', neighborhood: '', address: '' });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const hasPhysical = cart.some((i) => i.type === 'physical');
    const hasService = cart.some((i) => i.type === 'service');
    const subtotal = cart.reduce((s, i) => s + i.price * i.quantity, 0);
    const currency = cart[0]?.currency || 'FCFA';

    const refresh = () => setCart(readCart());
    const remove = (idx) => { removeFromCart(idx); refresh(); };
    const setQty = (idx, q) => { setCartQuantity(idx, q); refresh(); };

    const pay = async () => {
        setError('');
        setLoading(true);
        const payload = {
            payment_method: provider === 'cod' ? 'cash_on_delivery' : method,
            payment_provider: provider,
            email: email || null,
            coupon_code: coupon || null,
            items: cart.map((i) => {
                const it = { product_id: i.product_id, quantity: i.quantity };
                if (i.reservation_date) { it.reservation_date = i.reservation_date; it.reservation_time = i.reservation_time; }
                if (i.variant_id) it.variant_id = i.variant_id;
                if (i.variant_sku) it.variant_sku = i.variant_sku;
                return it;
            }),
        };
        if (hasPhysical) payload.shipping_address = ship;

        try {
            const r = await fetch('/checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            if (!r.ok) {
                setError(data.message || 'Une erreur est survenue.');
                setLoading(false);
                return;
            }
            clearCart();
            if (data.data?.payment?.url) {
                window.location = data.data.payment.url;
            } else {
                window.location = '/checkout/return/' + data.data.order_id;
            }
        } catch {
            setError('Erreur réseau. Réessayez.');
            setLoading(false);
        }
    };

    const inputClass = 'mt-1.5 h-11 w-full rounded-xl border border-line-strong bg-surface px-3.5 text-base text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    const labelClass = 'block text-sm font-medium text-ink-2';
    const cardClass = 'rounded-2xl border border-line bg-surface p-5 shadow-[0_1px_2px_rgba(28,25,23,0.04)] sm:p-6';
    const payLabel = loading ? (provider === 'cod' ? 'Validation…' : 'Redirection…') : (provider === 'cod' ? 'Valider la commande' : 'Payer');
    const count = cart.reduce((n, i) => n + i.quantity, 0);

    return (
        <>
            <Head title="Panier & paiement">
                <meta name="description" content="Votre panier MALKUTHAR et le paiement sécurisé." />
            </Head>

            <div className="mx-auto max-w-[1280px] px-4 pb-16 pt-8 sm:px-6 sm:pt-12">
                <div className="flex flex-wrap items-end justify-between gap-3 border-b border-line pb-6">
                    <div>
                        <h1 className="font-display text-[36px] font-semibold leading-[1.1] text-ink sm:text-[44px]">Panier</h1>
                        {cart.length > 0 && (
                            <p className="mt-1 text-base text-ink-2">{count} article{count > 1 ? 's' : ''}</p>
                        )}
                    </div>
                    <a href={route('catalog.index')} className="inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-ink-2 hover:text-ink">
                        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6" /></svg>
                        Continuer mes achats
                    </a>
                </div>

                {cart.length === 0 ? (
                    <EmptyState
                        className="mt-8"
                        icon="cart"
                        title="Votre panier est vide"
                        text="Parcourez la boutique pour ajouter des produits digitaux, physiques ou des services."
                        cta={<Button variant="primary" href={route('catalog.index')} native>Explorer la boutique</Button>}
                    />
                ) : (
                    <div className="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1.55fr)_minmax(0,1fr)] lg:gap-12">
                        <div className="space-y-6">
                            <section aria-labelledby="panier-articles">
                                <h2 id="panier-articles" className="sr-only">Articles</h2>
                                <ul className="divide-y divide-line rounded-2xl border border-line bg-surface">
                                    {cart.map((i, idx) => (
                                        <li key={idx} className="flex gap-4 p-4 sm:p-5">
                                            <div className="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-line bg-ground-alt">
                                                {i.image ? (
                                                    <img src={i.image} alt="" className="h-full w-full object-cover" />
                                                ) : (
                                                    <span className="flex h-full w-full items-center justify-center text-ink-3">
                                                        <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.4" aria-hidden="true">
                                                            <rect x="3" y="4" width="18" height="16" rx="2" /><path d="M3 15l5-5 4 4 3-3 6 6" />
                                                        </svg>
                                                    </span>
                                                )}
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-start justify-between gap-3">
                                                    <div className="min-w-0">
                                                        <p className="text-base font-semibold leading-snug text-ink">{i.name}</p>
                                                        <p className="mt-1 text-sm text-ink-2">
                                                            {TYPE_LABELS[i.type]}
                                                            {i.reservation_date && ` · ${i.reservation_date} à ${i.reservation_time}`}
                                                            {i.variant_sku && ` · ${i.variant_sku}`}
                                                        </p>
                                                    </div>
                                                    <p className="shrink-0 whitespace-nowrap text-base font-semibold text-ink">{money(i.price * i.quantity)} <span className="text-sm font-medium text-ink-3">{i.currency}</span></p>
                                                </div>
                                                <div className="mt-3 flex flex-wrap items-center gap-3">
                                                    <div className="inline-flex h-11 items-center rounded-full border border-line-strong">
                                                        <button type="button" onClick={() => setQty(idx, i.quantity - 1)} aria-label={`Réduire la quantité de ${i.name}`}
                                                                className="flex h-11 w-11 items-center justify-center rounded-full text-ink hover:bg-ground-alt">
                                                            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" aria-hidden="true"><path d="M5 12h14" /></svg>
                                                        </button>
                                                        <span className="w-8 text-center text-base font-semibold text-ink">
                                                            <span className="sr-only">Quantité : </span>{i.quantity}
                                                        </span>
                                                        <button type="button" onClick={() => setQty(idx, i.quantity + 1)} aria-label={`Augmenter la quantité de ${i.name}`}
                                                                className="flex h-11 w-11 items-center justify-center rounded-full text-ink hover:bg-ground-alt">
                                                            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                                                        </button>
                                                    </div>
                                                    <button type="button" onClick={() => remove(idx)} aria-label={`Retirer ${i.name} du panier`}
                                                            className="inline-flex min-h-11 items-center px-2 text-sm font-medium text-ink-2 underline decoration-line-strong underline-offset-4 hover:text-rust">
                                                        Retirer
                                                    </button>
                                                </div>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            </section>

                            <section className={cardClass} aria-labelledby="panier-coordonnees">
                                <h2 id="panier-coordonnees" className="text-lg font-semibold text-ink">Vos coordonnées</h2>
                                <label htmlFor="panier-email" className={`${labelClass} mt-4`}>Email</label>
                                <input id="panier-email" value={email} onChange={(e) => setEmail(e.target.value)} type="email" autoComplete="email"
                                       placeholder="vous@exemple.com" className={inputClass} />
                                <p className="mt-1.5 text-xs text-ink-3">Pour le reçu et l'accès aux produits digitaux.</p>
                            </section>

                            {hasPhysical && (
                                <section className={cardClass} aria-labelledby="panier-livraison">
                                    <h2 id="panier-livraison" className="text-lg font-semibold text-ink">Adresse de livraison</h2>
                                    <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                        {[
                                            ['first_name', 'Prénom', 'given-name'], ['last_name', 'Nom', 'family-name'], ['phone', 'Téléphone', 'tel'], ['country', 'Pays', 'country-name'],
                                            ['city', 'Ville', 'address-level2'], ['neighborhood', 'Quartier (facultatif)', 'address-level3'],
                                        ].map(([field, label, ac]) => (
                                            <div key={field}>
                                                <label htmlFor={`livraison-${field}`} className={labelClass}>{label}</label>
                                                <input id={`livraison-${field}`} value={ship[field]} autoComplete={ac}
                                                       type={field === 'phone' ? 'tel' : 'text'}
                                                       onChange={(e) => setShip((s) => ({ ...s, [field]: e.target.value }))}
                                                       className={inputClass} />
                                            </div>
                                        ))}
                                        <div className="sm:col-span-2">
                                            <label htmlFor="livraison-address" className={labelClass}>Adresse complète</label>
                                            <input id="livraison-address" value={ship.address} autoComplete="street-address"
                                                   onChange={(e) => setShip((s) => ({ ...s, address: e.target.value }))}
                                                   className={inputClass} />
                                        </div>
                                    </div>
                                </section>
                            )}

                            {hasService && (
                                <section className={cardClass} aria-labelledby="panier-reservation">
                                    <h2 id="panier-reservation" className="text-lg font-semibold text-ink">Réservation</h2>
                                    <ul className="mt-3 space-y-1.5 text-base text-ink-2">
                                        {cart.filter((i) => i.type === 'service').map((i, idx) => (
                                            <li key={idx}><span className="font-medium text-ink">{i.name}</span> — {i.reservation_date} à {i.reservation_time}</li>
                                        ))}
                                    </ul>
                                    <p className="mt-3 text-sm text-ink-3">Créneau choisi sur la fiche du service. Pour le modifier, retirez la ligne et réservez à nouveau.</p>
                                </section>
                            )}

                            <section className={cardClass} aria-labelledby="panier-paiement">
                                <h2 id="panier-paiement" className="text-lg font-semibold text-ink">Paiement</h2>
                                <div className="mt-4 grid gap-3 sm:grid-cols-2" role="radiogroup" aria-labelledby="panier-paiement">
                                    {[['fedapay', 'FedaPay', 'Mobile Money ou carte'], ['cinetpay', 'CinetPay', 'Mobile Money ou carte'], ['cod', 'Paiement à la livraison', 'En espèces à la réception']].map(([value, label, hint]) => (
                                        <label key={value}
                                               className={['flex min-h-14 cursor-pointer items-center gap-3 rounded-xl border px-4 py-3 transition-colors',
                                                   value === 'cod' ? 'sm:col-span-2' : '',
                                                   provider === value ? 'border-ink bg-ground-alt' : 'border-line-strong hover:border-ink'].join(' ')}>
                                            <input type="radio" name="payment_provider" checked={provider === value} onChange={() => setProvider(value)}
                                                   className="h-4 w-4 accent-accent" />
                                            <span>
                                                <span className="block text-base font-semibold text-ink">{label}</span>
                                                <span className="block text-sm text-ink-2">{hint}</span>
                                            </span>
                                        </label>
                                    ))}
                                </div>
                                <p className="mt-3 text-sm text-ink-2">
                                    {provider === 'cod'
                                        ? 'Vous réglez en espèces à la réception (ou lors du rendez-vous). Votre commande est préparée dès validation.'
                                        : "Mobile Money ou carte selon votre pays, à l'étape suivante."}
                                </p>
                            </section>
                        </div>

                        <aside className="lg:sticky lg:top-24 lg:self-start" aria-labelledby="panier-recap">
                            <div className={cardClass}>
                                <h2 id="panier-recap" className="text-lg font-semibold text-ink">Récapitulatif</h2>

                                <label htmlFor="panier-coupon" className={`${labelClass} mt-4`}>Code promo</label>
                                <input id="panier-coupon" value={coupon} onChange={(e) => setCoupon(e.target.value)} type="text"
                                       autoComplete="off" className={inputClass} />
                                <p className="mt-1.5 text-xs text-ink-3">Appliqué au moment du paiement.</p>

                                <dl className="mt-5 space-y-2.5 border-t border-line pt-5 text-sm">
                                    <div className="flex justify-between gap-4"><dt className="text-ink-2">Sous-total</dt><dd className="font-medium text-ink">{money(subtotal)} {currency}</dd></div>
                                    <div className="flex justify-between gap-4"><dt className="text-ink-2">Livraison et remise</dt><dd className="text-ink-2">calculées au paiement</dd></div>
                                </dl>
                                <div className="mt-5 flex items-baseline justify-between gap-4 border-t border-line pt-5">
                                    <span className="text-base font-semibold text-ink">Total</span>
                                    <span className="font-display text-[32px] font-semibold leading-none text-ink">
                                        {money(subtotal)} <span className="font-sans text-sm font-medium text-ink-3">{currency}</span>
                                    </span>
                                </div>

                                {error && (
                                    <p role="alert" className="mt-4 hidden rounded-xl border border-rust/25 bg-rust-tint px-4 py-3 text-sm text-rust lg:block">{error}</p>
                                )}

                                <div className="mt-5 hidden lg:block">
                                    <Button variant="primary" full onClick={pay} disabled={loading}>{payLabel}</Button>
                                </div>

                                <p className="mt-4 flex items-start gap-2 text-xs text-ink-3">
                                    <svg className="mt-px h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                        <rect x="5" y="10.5" width="14" height="10" rx="2" /><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3" />
                                    </svg>
                                    Paiement en ligne traité par FedaPay ou CinetPay.
                                </p>
                            </div>
                        </aside>
                    </div>
                )}

                {cart.length > 0 && (
                    <>
                        <div className="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface px-4 py-3 lg:hidden"
                             style={{ paddingBottom: 'calc(0.75rem + env(safe-area-inset-bottom))' }}>
                            <div className="mx-auto max-w-[1280px]">
                                <div className="mb-2 flex items-baseline justify-between gap-3">
                                    <span className="text-sm text-ink-2">Total</span>
                                    <span className="text-lg font-semibold text-ink">{money(subtotal)} {currency}</span>
                                </div>
                                {error && <p role="alert" className="mb-2 text-sm text-rust">{error}</p>}
                                <Button variant="primary" full onClick={pay} disabled={loading}>{payLabel}</Button>
                            </div>
                        </div>
                        <div className="h-32 lg:hidden" aria-hidden="true" />
                    </>
                )}
            </div>
        </>
    );
}

Cart.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
