import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Show({ order = {} }) {
    const [cancelReason, setCancelReason] = useState('');
    const [refundReasons, setRefundReasons] = useState({});

    const handleMarkPaid = () => {
        if (confirm(`Encaisser la commande ${order.order_number} ?`)) {
            router.post(route('admin.marketplace.orders.mark-paid', order.id));
        }
    };

    const handleCancel = (e) => {
        e.preventDefault();
        if (!cancelReason || cancelReason.trim().length < 3) {
            alert('Veuillez indiquer un motif d’annulation (au moins 3 caractères).');
            return;
        }
        router.post(route('admin.marketplace.orders.cancel', order.id), { reason: cancelReason });
    };

    const handleRefundSuborder = (e, suborderId) => {
        e.preventDefault();
        const reason = refundReasons[suborderId];
        if (!reason || reason.trim().length < 3) {
            alert('Veuillez indiquer un motif de remboursement (au moins 3 caractères).');
            return;
        }
        if (confirm('Confirmer le remboursement de cette part vendeur ?')) {
            router.post(route('admin.marketplace.suborders.refund', suborderId), { reason });
        }
    };

    const formatPrice = (val) => {
        if (val === null || val === undefined) return '0';
        return Number(val).toLocaleString('fr-FR');
    };

    const statusPill = (st) => {
        switch (st) {
            case 'paid':
                return (
                    <span className="rounded-full bg-sage-soft px-3 py-1 text-xs font-semibold text-sage">
                        Payée
                    </span>
                );
            case 'pending':
                return (
                    <span className="rounded-full bg-amber/25 px-3 py-1 text-xs font-semibold text-amber">
                        À encaisser
                    </span>
                );
            case 'failed':
                return (
                    <span className="rounded-full bg-rust-tint px-3 py-1 text-xs font-semibold text-rust">
                        Annulée
                    </span>
                );
            default:
                return (
                    <span className="rounded-full bg-night-line px-3 py-1 text-xs font-semibold text-night-ink-2">
                        {st}
                    </span>
                );
        }
    };

    const settlementPill = (st) => {
        switch (st) {
            case 'settled':
                return (
                    <span className="rounded-full bg-sage-soft px-2 py-0.5 text-xs font-semibold text-sage">
                        Versé
                    </span>
                );
            case 'pending':
                return (
                    <span className="rounded-full bg-amber/25 px-2 py-0.5 text-xs font-semibold text-amber">
                        En attente
                    </span>
                );
            case 'reversed':
                return (
                    <span className="rounded-full bg-rust-tint px-2 py-0.5 text-xs font-semibold text-rust">
                        Remboursé
                    </span>
                );
            default:
                return (
                    <span className="rounded-full bg-night-line px-2 py-0.5 text-xs font-semibold text-night-ink-2">
                        {st}
                    </span>
                );
        }
    };

    return (
        <AdminLayout
            title={`Commande ${order.order_number || ''}`}
            eyebrow="Marketplace · Commande"
            heading={order.order_number}
        >
            <div className="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
                {/* SOUS-COMMANDES VENDEURS */}
                <div className="space-y-5">
                    {order.suborders &&
                        order.suborders.map((sub) => (
                            <section
                                key={sub.id}
                                className="rounded-2xl border border-night-line bg-night-2 p-6 "
                            >
                                <div className="flex items-center justify-between pb-3 border-b border-night-line">
                                    <h2 className="font-sans text-base font-semibold text-night-ink">
                                        {sub.vendor?.display_name || 'Vendeur'}
                                    </h2>
                                    <span className="font-mono text-xs text-night-ink-2 font-medium">
                                        {sub.suborder_number}
                                    </span>
                                </div>

                                <table className="mt-3 w-full text-sm">
                                    <tbody className="divide-y divide-night-line">
                                        {sub.items &&
                                            sub.items.map((item) => (
                                                <tr key={item.id}>
                                                    <td className="py-2.5 font-medium text-night-ink">
                                                        {item.quantity} × {item.title}
                                                    </td>
                                                    <td className="py-2.5 text-right font-semibold text-night-ink-2">
                                                        {formatPrice(item.line_total)} {order.currency || 'XOF'}
                                                    </td>
                                                </tr>
                                            ))}
                                    </tbody>
                                </table>

                                <dl className="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 border-t border-night-line pt-4 text-sm">
                                    <div>
                                        <dt className="text-night-ink-2 font-medium">Brut vendeur</dt>
                                        <dd className="mt-0.5 font-semibold text-night-ink">
                                            {formatPrice(sub.vendor_gross)} {order.currency || 'XOF'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-night-ink-2 font-medium">
                                            Commission ({parseFloat((Number(sub.commission_rate || 0) * 100).toFixed(2))} %)
                                        </dt>
                                        <dd className="mt-0.5 font-semibold text-rust">
                                            − {formatPrice(sub.commission_amount)} {order.currency || 'XOF'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-night-ink-2 font-medium">Net vendeur</dt>
                                        <dd className="mt-0.5 font-bold text-night-ink text-sm">
                                            {formatPrice(sub.vendor_net)} {order.currency || 'XOF'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-night-ink-2 font-medium">Statut règlement</dt>
                                        <dd className="mt-0.5">{settlementPill(sub.settlement_status)}</dd>
                                    </div>
                                </dl>

                                {order.payment_status === 'paid' && sub.settlement_status !== 'reversed' && (
                                    <form
                                        onSubmit={(e) => handleRefundSuborder(e, sub.id)}
                                        className="mt-4 flex items-center gap-2 border-t border-night-line pt-4"
                                    >
                                        <input
                                            type="text"
                                            required
                                            placeholder="Motif du remboursement"
                                            value={refundReasons[sub.id] || ''}
                                            onChange={(e) =>
                                                setRefundReasons({ ...refundReasons, [sub.id]: e.target.value })
                                            }
                                            className="h-11 flex-1 rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink placeholder:text-night-ink-2/70 focus:border-accent focus:outline-none"
                                        />
                                        <button
                                            type="submit"
                                            className="rounded-full border border-rust bg-rust-tint px-3 py-1.5 text-sm font-bold text-rust hover:bg-rust/35 transition-colors min-h-11"
                                        >
                                            Rembourser cette part
                                        </button>
                                    </form>
                                )}
                            </section>
                        ))}
                </div>

                {/* DETAILS DE LA COMMANDE & ENCAISSEMENT */}
                <div className="space-y-6">
                    <section className="rounded-2xl border border-night-line bg-night-2 p-6 text-sm">
                        <h2 className="font-sans text-base font-semibold text-night-ink pb-3 border-b border-night-line">
                            Commande globale
                        </h2>
                        <dl className="mt-4 space-y-3">
                            <div>
                                <dt className="text-night-ink-2 font-medium">Acheteur</dt>
                                <dd className="mt-0.5 font-semibold text-night-ink text-sm">
                                    {order.buyer_email}
                                    {order.buyer_phone ? ` · ${order.buyer_phone}` : ''}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-night-ink-2 font-medium">Mode & Statut de paiement</dt>
                                <dd className="mt-1 flex items-center gap-2">
                                    <span className="font-semibold text-night-ink">{order.payment_provider}</span>
                                    {statusPill(order.payment_status)}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-night-ink-2 font-medium">Montant total TTC</dt>
                                <dd className="mt-0.5 text-base font-bold text-night-ink">
                                    {formatPrice(order.grand_total)} {order.currency || 'XOF'}
                                </dd>
                            </div>
                            {order.paid_at && (
                                <div>
                                    <dt className="text-night-ink-2 font-medium">Payée le</dt>
                                    <dd className="mt-0.5 text-night-ink">
                                        {new Date(order.paid_at).toLocaleString('fr-FR')}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </section>

                    {order.payment_status === 'pending' && (
                        <div className="space-y-3">
                            <button
                                type="button"
                                onClick={handleMarkPaid}
                                className="h-11 w-full rounded-2xl border border-sage bg-sage-soft text-xs font-bold text-sage hover:bg-sage-soft transition-colors"
                            >
                                Encaisser la commande
                            </button>

                            <form
                                onSubmit={handleCancel}
                                className="rounded-2xl border border-night-line bg-night-2 p-5 space-y-2"
                            >
                                <label className="block text-sm font-medium text-night-ink-2">Annuler la commande</label>
                                <input
                                    type="text"
                                    required
                                    placeholder="Motif d'annulation"
                                    value={cancelReason}
                                    onChange={(e) => setCancelReason(e.target.value)}
                                    className="h-11 w-full rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink placeholder:text-night-ink-2/70 focus:border-accent focus:outline-none min-h-11"
                                />
                                <button
                                    type="submit"
                                    className="h-9 w-full rounded-xl border border-rust bg-rust-tint text-xs font-bold text-rust hover:bg-rust/35 transition-colors"
                                >
                                    Annuler
                                </button>
                            </form>
                        </div>
                    )}
                </div>
            </div>

            <div className="mt-8">
                <Link
                    href={route('admin.marketplace.orders.index')}
                    className="text-xs font-semibold text-accent-2 hover:underline"
                >
                    Toutes les commandes
                </Link>
            </div>
        </AdminLayout>
    );
}
