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
                    <span className="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                        Payée
                    </span>
                );
            case 'pending':
                return (
                    <span className="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                        À encaisser
                    </span>
                );
            case 'failed':
                return (
                    <span className="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-800">
                        Annulée
                    </span>
                );
            default:
                return (
                    <span className="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600">
                        {st}
                    </span>
                );
        }
    };

    const settlementPill = (st) => {
        switch (st) {
            case 'settled':
                return (
                    <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">
                        Versé
                    </span>
                );
            case 'pending':
                return (
                    <span className="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800">
                        En attente
                    </span>
                );
            case 'reversed':
                return (
                    <span className="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-800">
                        Remboursé
                    </span>
                );
            default:
                return (
                    <span className="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-semibold text-zinc-600">
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
                                className="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs"
                            >
                                <div className="flex items-center justify-between pb-3 border-b border-zinc-100">
                                    <h2 className="font-serif text-base font-semibold text-zinc-900">
                                        {sub.vendor?.display_name || 'Vendeur'}
                                    </h2>
                                    <span className="font-mono text-xs text-zinc-400 font-medium">
                                        {sub.suborder_number}
                                    </span>
                                </div>

                                <table className="mt-3 w-full text-xs">
                                    <tbody className="divide-y divide-zinc-100">
                                        {sub.items &&
                                            sub.items.map((item) => (
                                                <tr key={item.id}>
                                                    <td className="py-2.5 font-medium text-zinc-900">
                                                        {item.quantity} × {item.title}
                                                    </td>
                                                    <td className="py-2.5 text-right font-semibold text-zinc-700">
                                                        {formatPrice(item.line_total)} {order.currency || 'XOF'}
                                                    </td>
                                                </tr>
                                            ))}
                                    </tbody>
                                </table>

                                <dl className="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 border-t border-zinc-100 pt-4 text-xs">
                                    <div>
                                        <dt className="text-zinc-500 font-medium">Brut vendeur</dt>
                                        <dd className="mt-0.5 font-semibold text-zinc-900">
                                            {formatPrice(sub.vendor_gross)} {order.currency || 'XOF'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-zinc-500 font-medium">
                                            Commission ({parseFloat((Number(sub.commission_rate || 0) * 100).toFixed(2))} %)
                                        </dt>
                                        <dd className="mt-0.5 font-semibold text-rose-600">
                                            − {formatPrice(sub.commission_amount)} {order.currency || 'XOF'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-zinc-500 font-medium">Net vendeur</dt>
                                        <dd className="mt-0.5 font-bold text-zinc-900 text-sm">
                                            {formatPrice(sub.vendor_net)} {order.currency || 'XOF'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-zinc-500 font-medium">Statut règlement</dt>
                                        <dd className="mt-0.5">{settlementPill(sub.settlement_status)}</dd>
                                    </div>
                                </dl>

                                {order.payment_status === 'paid' && sub.settlement_status !== 'reversed' && (
                                    <form
                                        onSubmit={(e) => handleRefundSuborder(e, sub.id)}
                                        className="mt-4 flex items-center gap-2 border-t border-zinc-100 pt-4"
                                    >
                                        <input
                                            type="text"
                                            required
                                            placeholder="Motif du remboursement"
                                            value={refundReasons[sub.id] || ''}
                                            onChange={(e) =>
                                                setRefundReasons({ ...refundReasons, [sub.id]: e.target.value })
                                            }
                                            className="h-8 flex-1 rounded-xl border border-zinc-300 bg-white px-3 text-xs text-zinc-900 shadow-2xs placeholder:text-zinc-400 focus:border-accent focus:outline-none"
                                        />
                                        <button
                                            type="submit"
                                            className="rounded-xl border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-800 shadow-2xs hover:bg-rose-100 transition-colors"
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
                    <section className="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs text-xs">
                        <h2 className="font-serif text-base font-semibold text-zinc-900 pb-3 border-b border-zinc-100">
                            Commande globale
                        </h2>
                        <dl className="mt-4 space-y-3">
                            <div>
                                <dt className="text-zinc-500 font-medium">Acheteur</dt>
                                <dd className="mt-0.5 font-semibold text-zinc-900 text-sm">
                                    {order.buyer_email}
                                    {order.buyer_phone ? ` · ${order.buyer_phone}` : ''}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-zinc-500 font-medium">Mode & Statut de paiement</dt>
                                <dd className="mt-1 flex items-center gap-2">
                                    <span className="font-semibold text-zinc-800">{order.payment_provider}</span>
                                    {statusPill(order.payment_status)}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-zinc-500 font-medium">Montant total TTC</dt>
                                <dd className="mt-0.5 text-base font-bold text-zinc-900">
                                    {formatPrice(order.grand_total)} {order.currency || 'XOF'}
                                </dd>
                            </div>
                            {order.paid_at && (
                                <div>
                                    <dt className="text-zinc-500 font-medium">Payée le</dt>
                                    <dd className="mt-0.5 text-zinc-800">
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
                                className="h-11 w-full rounded-2xl border border-emerald-300 bg-emerald-50 text-xs font-bold text-emerald-800 shadow-2xs hover:bg-emerald-100 transition-colors"
                            >
                                Encaisser la commande
                            </button>

                            <form
                                onSubmit={handleCancel}
                                className="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs space-y-2"
                            >
                                <label className="block text-xs font-medium text-zinc-700">Annuler la commande</label>
                                <input
                                    type="text"
                                    required
                                    placeholder="Motif d'annulation"
                                    value={cancelReason}
                                    onChange={(e) => setCancelReason(e.target.value)}
                                    className="h-10 w-full rounded-xl border border-zinc-300 bg-white px-3 text-xs text-zinc-900 shadow-2xs placeholder:text-zinc-400 focus:border-accent focus:outline-none"
                                />
                                <button
                                    type="submit"
                                    className="h-9 w-full rounded-xl border border-rose-300 bg-rose-50 text-xs font-bold text-rose-800 shadow-2xs hover:bg-rose-100 transition-colors"
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
                    className="text-xs font-semibold text-accent hover:underline"
                >
                    ← Toutes les commandes
                </Link>
            </div>
        </AdminLayout>
    );
}
