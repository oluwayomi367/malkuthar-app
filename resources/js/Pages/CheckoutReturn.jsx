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
    ok: 'border-emerald-400/40 text-emerald-300',
    wait: 'border-amber/40 text-amber',
    bad: 'border-rust/40 text-rust',
    neut: 'border-black/15 text-ink-2',
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

            <div className="relative mx-auto max-w-md px-4 py-16 text-center">
                <span className={`mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl border ${RING[tone]} bg-surface`}>
                    {tone === 'ok' ? (
                        <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M5 13l4 4L19 7" strokeLinecap="round" strokeLinejoin="round" /></svg>
                    ) : tone === 'bad' ? (
                        <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M7 7l10 10M17 7L7 17" strokeLinecap="round" /></svg>
                    ) : (
                        <svg className="h-6 w-6 animate-spin" style={{ animationDuration: '1.4s' }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 3a9 9 0 1 0 9 9" strokeLinecap="round" /></svg>
                    )}
                </span>

                <h1 className="font-display text-2xl font-bold tracking-[-0.01em] text-ink">{title}</h1>
                <p className="mx-auto mt-2 max-w-sm text-sm text-ink-2">{text}</p>

                <div className="mt-6 rounded-2xl border border-black/10 bg-surface p-4 text-left text-sm">
                    <div className="flex justify-between"><span className="text-ink-3">Commande</span><span className="font-mono text-ink">{order.order_number}</span></div>
                    <div className="mt-1.5 flex justify-between"><span className="text-ink-3">Montant</span><span className="text-ink">{money(order.total_amount)} {order.currency}</span></div>
                    <div className="mt-1.5 flex justify-between"><span className="text-ink-3">Statut</span><StatusPill status={order.payment_status} /></div>
                </div>

                <div className="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-center">
                    {status === 'paid' && <Button variant="primary" href="/mon-compte" native>Voir mes commandes</Button>}
                    {status === 'failed' && <Button variant="primary" href={route('cart')}>Réessayer le paiement</Button>}
                    <Button variant="secondary" href="/" native>Retour à l'accueil</Button>
                </div>

                {isLocal && (
                    <div className="mt-8 rounded-xl border border-amber/30 bg-amber/[0.06] p-3 text-left text-xs text-amber">
                        <p className="font-semibold">Outils de développement (local uniquement)</p>
                        <div className="mt-2 flex flex-wrap gap-2">
                            {order.payment_status !== 'paid' ? (
                                <form method="POST" action={route('dev.confirm-payment', order.id)}>
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <button className="rounded-lg bg-emerald-500/90 px-3 py-1.5 font-semibold text-black">Simuler le paiement</button>
                                </form>
                            ) : (
                                <form method="POST" action={route('dev.refund', order.id)}>
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <button className="rounded-lg bg-rust/90 px-3 py-1.5 font-semibold text-white">Simuler un remboursement</button>
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
