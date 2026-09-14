import { useState } from 'react';
import { Head } from '@inertiajs/react';
import StorefrontLayout from '../Layouts/StorefrontLayout';
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

    const inputClass = 'h-11 rounded-xl border border-black/10 bg-surface px-3.5 text-[15px] text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25';

    return (
        <>
            <Head title="Panier & paiement">
                <meta name="description" content="Votre panier MALKUTHAR et le paiement sécurisé." />
            </Head>

            <div className="mx-auto max-w-[1180px] px-4 py-10 sm:px-6">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Panier</h1>
                    <a href={route('catalog.index')} className="text-sm text-ink-2 transition-colors hover:text-ink">‹ Continuer mes achats</a>
                </div>

                {cart.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-black/12 bg-surface px-6 py-16 text-center">
                        <p className="font-display font-semibold text-ink">Votre panier est vide</p>
                        <p className="mx-auto mt-1 max-w-sm text-sm text-ink-2">Parcourez la boutique pour ajouter des produits digitaux, physiques ou des services.</p>
                        <a href={route('catalog.index')}
                           className="mt-5 inline-flex h-11 items-center justify-center rounded-xl bg-accent px-5 text-sm font-semibold text-white shadow-[0_10px_34px_-10px] shadow-black/10">
                            Explorer la boutique
                        </a>
                    </div>
                ) : (
                    <div className="grid gap-8 lg:grid-cols-[1.5fr_1fr] lg:gap-12">
                        <div className="space-y-6">
                            <ul className="space-y-3">
                                {cart.map((i, idx) => (
                                    <li key={idx} className="flex items-start gap-4 rounded-2xl border border-black/10 bg-surface p-4">
                                        <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-black/10 bg-gradient-to-br from-black/[0.05] to-transparent text-ink-3">
                                            <svg className="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.4">
                                                <rect x="3" y="4" width="14" height="12" rx="2" /><path d="M3 13l4-4 3 3 3-3 4 4" />
                                            </svg>
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-[15px] font-medium text-ink">{i.name}</p>
                                            <p className="mt-0.5 text-xs text-ink-3">
                                                {TYPE_LABELS[i.type]}
                                                {i.reservation_date && ` · ${i.reservation_date} ${i.reservation_time}`}
                                                {i.variant_sku && ` · ${i.variant_sku}`}
                                            </p>
                                            <div className="mt-2 flex items-center gap-3">
                                                <div className="inline-flex items-center rounded-lg border border-black/10">
                                                    <button type="button" onClick={() => setQty(idx, i.quantity - 1)} className="flex h-8 w-8 items-center justify-center text-ink-2 hover:text-ink" aria-label="Réduire">−</button>
                                                    <span className="w-6 text-center text-sm font-semibold text-ink">{i.quantity}</span>
                                                    <button type="button" onClick={() => setQty(idx, i.quantity + 1)} className="flex h-8 w-8 items-center justify-center text-ink-2 hover:text-ink" aria-label="Augmenter">+</button>
                                                </div>
                                                <button type="button" onClick={() => remove(idx)} className="text-xs text-ink-3 underline decoration-rust/50 underline-offset-2 hover:text-rust">Retirer</button>
                                            </div>
                                        </div>
                                        <p className="shrink-0 text-sm font-semibold text-ink">{money(i.price * i.quantity)} {i.currency}</p>
                                    </li>
                                ))}
                            </ul>

                            <div className="rounded-2xl border border-black/10 bg-surface p-5">
                                <p className="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Vos coordonnées</p>
                                <div className="mt-3 grid gap-3">
                                    <input value={email} onChange={(e) => setEmail(e.target.value)} type="email"
                                           placeholder="Email — pour le reçu et l'accès digital" className={inputClass} />
                                </div>
                            </div>

                            {hasPhysical && (
                                <div className="rounded-2xl border border-black/10 bg-surface p-5">
                                    <p className="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Adresse de livraison</p>
                                    <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                        {[
                                            ['first_name', 'Prénom'], ['last_name', 'Nom'], ['phone', 'Téléphone'], ['country', 'Pays'],
                                            ['city', 'Ville'], ['neighborhood', 'Quartier (facultatif)'],
                                        ].map(([field, placeholder]) => (
                                            <input key={field} value={ship[field]} onChange={(e) => setShip((s) => ({ ...s, [field]: e.target.value }))}
                                                   placeholder={placeholder} className={inputClass} />
                                        ))}
                                        <input value={ship.address} onChange={(e) => setShip((s) => ({ ...s, address: e.target.value }))}
                                               placeholder="Adresse complète" className={`${inputClass} sm:col-span-2`} />
                                    </div>
                                </div>
                            )}

                            {hasService && (
                                <div className="rounded-2xl border border-black/10 bg-surface p-5 text-sm text-ink-2">
                                    <p className="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Réservation</p>
                                    <ul className="mt-3 space-y-1">
                                        {cart.filter((i) => i.type === 'service').map((i, idx) => (
                                            <li key={idx}><span className="text-ink">{i.name}</span> — {i.reservation_date} à {i.reservation_time}</li>
                                        ))}
                                    </ul>
                                    <p className="mt-2 text-xs text-ink-3">Créneau choisi sur la fiche du service. Pour le modifier, retirez la ligne et re-réservez.</p>
                                </div>
                            )}

                            <div className="rounded-2xl border border-black/10 bg-surface p-5">
                                <p className="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Paiement</p>
                                <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                    {[['fedapay', 'FedaPay'], ['cinetpay', 'CinetPay']].map(([value, label]) => (
                                        <label key={value} className={`flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition-colors ${provider === value ? 'border-accent bg-accent/10' : 'border-black/10 hover:border-black/20'}`}>
                                            <input type="radio" checked={provider === value} onChange={() => setProvider(value)} className="text-accent focus:ring-accent/30" />
                                            <span className="text-sm font-medium text-ink">{label}</span>
                                        </label>
                                    ))}
                                    <label className={`flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition-colors sm:col-span-2 ${provider === 'cod' ? 'border-accent bg-accent/10' : 'border-black/10 hover:border-black/20'}`}>
                                        <input type="radio" checked={provider === 'cod'} onChange={() => setProvider('cod')} className="text-accent focus:ring-accent/30" />
                                        <span className="text-sm font-medium text-ink">Paiement à la livraison</span>
                                    </label>
                                </div>
                                <p className="mt-2 text-xs text-ink-3">
                                    {provider === 'cod'
                                        ? 'Vous réglez en espèces à la réception (ou lors du rendez-vous). Votre commande est préparée dès validation.'
                                        : "Mobile Money ou carte selon votre pays, à l'étape suivante."}
                                </p>
                            </div>
                        </div>

                        <div className="lg:sticky lg:top-20 lg:self-start">
                            <div className="rounded-2xl border border-black/10 bg-black/[0.04] p-5 backdrop-blur-sm">
                                <p className="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Récapitulatif</p>
                                <div className="mt-3 flex items-center gap-2">
                                    <input value={coupon} onChange={(e) => setCoupon(e.target.value)} type="text" placeholder="Code promo"
                                           className="h-10 flex-1 rounded-xl border border-black/10 bg-surface px-3 text-sm text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25" />
                                </div>
                                <dl className="mt-4 space-y-2 text-sm">
                                    <div className="flex justify-between"><dt className="text-ink-2">Sous-total</dt><dd className="text-ink">{money(subtotal)} {currency}</dd></div>
                                    <div className="flex justify-between text-ink-3"><dt>Livraison &amp; remise</dt><dd>calculées au paiement</dd></div>
                                </dl>
                                <div className="mt-4 flex items-center justify-between border-t border-black/10 pt-4">
                                    <span className="font-display text-lg font-semibold text-ink">Total</span>
                                    <span className="font-display text-lg font-semibold text-ink">{money(subtotal)} {currency}</span>
                                </div>
                                {error && <p className="mt-3 rounded-xl border border-rust/40 bg-rust/10 p-3 text-sm text-rust">{error}</p>}
                                <button onClick={pay} disabled={loading}
                                        className="mt-4 hidden h-12 w-full items-center justify-center rounded-xl bg-accent text-sm font-semibold text-white shadow-[0_12px_40px_-12px] shadow-black/10 transition-transform hover:-translate-y-0.5 disabled:opacity-50 lg:flex">
                                    {loading ? (provider === 'cod' ? 'Validation…' : 'Redirection…') : (provider === 'cod' ? 'Valider la commande' : 'Payer')}
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {cart.length > 0 && (
                    <>
                        <div className="fixed inset-x-0 bottom-0 z-30 border-t border-black/[0.08] bg-ground/85 px-4 py-3 backdrop-blur-xl lg:hidden"
                             style={{ paddingBottom: 'calc(0.75rem + env(safe-area-inset-bottom))' }}>
                            <div className="mx-auto flex max-w-[1180px] items-center gap-3">
                                <div className="min-w-0 flex-1">
                                    <p className="text-xs text-ink-3">Total</p>
                                    <p className="font-semibold text-ink">{money(subtotal)} {currency}</p>
                                </div>
                                <button onClick={pay} disabled={loading}
                                        className="inline-flex h-11 flex-1 items-center justify-center rounded-xl bg-accent text-sm font-semibold text-white shadow-[0_10px_34px_-10px] shadow-black/10 disabled:opacity-50">
                                    {loading ? (provider === 'cod' ? 'Validation…' : 'Redirection…') : (provider === 'cod' ? 'Valider la commande' : 'Payer')}
                                </button>
                            </div>
                        </div>
                        <div className="h-20 lg:hidden" aria-hidden="true" />
                    </>
                )}
            </div>
        </>
    );
}

Cart.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
