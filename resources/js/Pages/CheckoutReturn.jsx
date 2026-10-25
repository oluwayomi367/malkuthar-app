import { useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import StorefrontLayout from '../Layouts/StorefrontLayout';
import Button from '../Components/ui/Button';
import StatusPill from '../Components/ui/StatusPill';
import { money } from '../lib/format';

const MAP = {
    paid: ['Paiement confirmé', 'Votre commande est validée. Vous allez recevoir un email avec vos accès.', 'ok'],
    pending: ['Paiement en cours de vérification', 'Nous confirmons votre paiement auprès du prestataire. Cette page se met à jour toute seule.', 'wait'],
    failed: ['Paiement non abouti', "Le paiement n'a pas pu être confirmé. Vous pouvez réessayer.", 'bad'],
    refunded: ['Commande remboursée', 'Cette commande a été remboursée.', 'neut'],
};

const RING = {
    ok: 'bg-sage-soft text-sage',
    wait: 'bg-amber-tint text-amber',
    bad: 'bg-rust-tint text-rust',
    neut: 'bg-ground-alt text-ink-2',
};

export default function CheckoutReturn({ order, status, isLocal, csrfToken }) {
    const isCod = order.payment_provider === 'cod' && status !== 'paid';
    const [title, text, tone] = isCod
        ? ['Commande enregistrée', 'Vous réglerez à la livraison (ou lors du rendez-vous). Nous préparons votre commande et vous recontactons.', 'neut']
        : (MAP[status] ?? MAP.pending);

    useEffect(() => {
        if (status !== 'pending' || isCod) return;
        const t = setTimeout(() => router.reload(), 5000);
        return () => clearTimeout(t);
    }, [status, isCod]);

    return (
        <>
            <Head title="Confirmation de paiement">
                <meta name="description" content="Statut de votre commande MALKUTHAR." />
            </Head>

            <div className="mx-auto max-w-lg px-4 pb-16 pt-12 sm:px-6 sm:pt-16">
                <div className="text-center">
                    <span className={`mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-full ${RING[tone]}`} aria-hidden="true">
                        {tone === 'ok' ? (
                            <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"><path d="M5 12.5l4.5 4.5L19 7.5" strokeLinecap="round" strokeLinejoin="round" /></svg>
                        ) : tone === 'bad' ? (
                            <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"><path d="M7 7l10 10M17 7L7 17" strokeLinecap="round" /></svg>
                        ) : (
                            <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"><circle cx="12" cy="12" r="8.5" /><path d="M12 7.5V12l3 2" /></svg>
                        )}
                    </span>

                    <h1 className="font-display text-[36px] font-semibold leading-[1.1] text-ink">{title}</h1>
                    <p className="mx-auto mt-3 max-w-md text-base leading-[1.6] text-ink-2" aria-live="polite">{text}</p>
                </div>

                <dl className="mt-8 divide-y divide-line rounded-2xl border border-line bg-surface px-5 text-sm shadow-[0_1px_2px_rgba(28,25,23,0.04)]">
                    <div className="flex items-center justify-between gap-4 py-3.5"><dt className="text-ink-2">Commande</dt><dd className="font-mono text-ink">{order.order_number}</dd></div>
                    <div className="flex items-center justify-between gap-4 py-3.5"><dt className="text-ink-2">Statut</dt><dd><StatusPill status={order.payment_status} /></dd></div>
                    <div className="flex items-baseline justify-between gap-4 py-4">
                        <dt className="text-base font-semibold text-ink">Montant</dt>
                        <dd className="font-display text-[28px] font-semibold leading-none text-ink">
                            {money(order.total_amount)} <span className="font-sans text-sm font-medium text-ink-3">{order.currency}</span>
                        </dd>
                    </div>
                </dl>

                <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                    {status === 'paid' && <Button variant="primary" href="/mon-compte" native className="w-full sm:w-auto">Voir mes commandes</Button>}
                    {status === 'failed' && <Button variant="primary" href={route('cart')} className="w-full sm:w-auto">Réessayer le paiement</Button>}
                    <Button variant="secondary" href="/" native className="w-full sm:w-auto">Retour à l'accueil</Button>
                </div>

                {isLocal && (
                    <div className="mt-10 rounded-xl border border-dashed border-amber/40 bg-amber-tint p-4 text-left text-sm text-amber">
                        <p className="font-semibold">Outils de développement (local uniquement)</p>
                        <div className="mt-2 flex flex-wrap gap-2">
                            {order.payment_status !== 'paid' ? (
                                <form method="POST" action={route('dev.confirm-payment', order.id)}>
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <button className="min-h-11 rounded-full bg-deep px-4 font-semibold text-white">Simuler le paiement</button>
                                </form>
                            ) : (
                                <form method="POST" action={route('dev.refund', order.id)}>
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <button className="min-h-11 rounded-full bg-rust px-4 font-semibold text-white">Simuler un remboursement</button>
                                </form>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

CheckoutReturn.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
