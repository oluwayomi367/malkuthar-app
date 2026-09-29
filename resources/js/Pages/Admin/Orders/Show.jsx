import React, { useState } from 'react';
import { Link, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Show({
    order,
    commissions = [],
    fulfilmentSteps = ['preparing', 'shipped', 'in_delivery', 'delivered'],
    heading,
    eyebrow = 'Commandes',
}) {
    const formatNumber = (num) => {
        if (num === null || num === undefined) return '0';
        return Number(num).toLocaleString('fr-FR');
    };

    // Formulaire d'encaissement COD
    const codForm = useForm({});
    const submitCollectCod = (e) => {
        e.preventDefault();
        if (confirm(`Confirmer l'encaissement de ${formatNumber(order.total_amount)} FCFA pour la commande ${order.order_number} ?`)) {
            codForm.post(route('admin.orders.collect-cod', order.id));
        }
    };

    // Formulaire d'annulation
    const cancelForm = useForm({
        reason: '',
    });
    const submitCancel = (e) => {
        e.preventDefault();
        if (confirm(`Confirmer l'annulation de la commande ${order.order_number} ?`)) {
            cancelForm.post(route('admin.orders.cancel', order.id));
        }
    };

    // Formulaire d'expédition
    const fulfilmentForm = useForm({
        shipping_status: order.shipping_status || 'preparing',
        tracking_number: order.tracking_number || '',
    });
    const submitFulfilment = (e) => {
        e.preventDefault();
        fulfilmentForm.post(route('admin.orders.fulfilment', order.id));
    };

    const actions = (
        <div className="flex items-center gap-2">
            <Link
                href={route('admin.orders.index')}
                className="rounded-xl border border-zinc-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-zinc-700 shadow-2xs hover:bg-zinc-50"
            >
                ← Liste
            </Link>

            {order.payment_provider === 'cod' && order.payment_status === 'pending' && order.status !== 'cancelled' && (
                <button
                    type="button"
                    onClick={submitCollectCod}
                    className="rounded-xl bg-emerald-600 px-4 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-emerald-700"
                >
                    Encaisser
                </button>
            )}

            {order.status !== 'cancelled' && order.status !== 'completed' && (
                <button
                    type="button"
                    onClick={submitCancel}
                    className="rounded-xl border border-rose-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-rose-700 shadow-2xs hover:bg-rose-50"
                >
                    Annuler
                </button>
            )}
        </div>
    );

    return (
        <AdminLayout
            title={`Commande ${order.order_number}`}
            eyebrow={eyebrow}
            heading={heading || `Commande ${order.order_number}`}
            actions={actions}
        >
            {/* STATUTS HEADER */}
            <div className="mb-6 flex flex-wrap items-center gap-3 text-xs text-zinc-500">
                <span className="font-mono font-bold text-zinc-800">{order.order_number}</span>
                <span>·</span>
                <span>Paiement : <strong className="text-zinc-800 uppercase">{order.payment_provider}</strong></span>
                <span>·</span>
                {order.payment_status === 'paid' ? (
                    <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 font-semibold text-emerald-800">
                        Paiement validé
                    </span>
                ) : (
                    <span className="rounded-full bg-amber-100 px-2.5 py-0.5 font-semibold text-amber-800">
                        {order.payment_status}
                    </span>
                )}
            </div>

            {/* GRILLE PRINCIPALE : DÉTAIL COMMANDE + ACTIONS */}
            <div className="grid gap-8 lg:grid-cols-[1fr_360px]">
                {/* COLONNE GAUCHE : ARTICLES + COMMISSIONS */}
                <div className="space-y-6">
                    {/* ARTICLES */}
                    <div className="overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-xs">
                        <div className="border-b border-zinc-200 bg-zinc-50/70 px-5 py-3">
                            <h2 className="font-display text-xs font-bold uppercase tracking-wider text-zinc-700">
                                Articles commandés
                            </h2>
                        </div>
                        <table className="w-full text-left text-xs">
                            <thead className="border-b border-zinc-100 bg-zinc-50 text-[10px] uppercase tracking-wider text-zinc-400">
                                <tr>
                                    <th className="px-5 py-2.5">Article</th>
                                    <th className="px-5 py-2.5">Type</th>
                                    <th className="px-5 py-2.5">Qté</th>
                                    <th className="px-5 py-2.5 text-right">Prix</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-zinc-100">
                                {order.items && order.items.map((item) => (
                                    <tr key={item.id} className="hover:bg-zinc-50/50">
                                        <td className="px-5 py-3.5">
                                            <p className="font-semibold text-zinc-900">{item.product_name}</p>
                                            {item.variant_label && (
                                                <p className="text-[11px] text-zinc-500">Variante : {item.variant_label}</p>
                                            )}
                                        </td>
                                        <td className="px-5 py-3.5 text-zinc-500 uppercase text-[10px]">
                                            {item.product_type}
                                        </td>
                                        <td className="px-5 py-3.5 font-semibold text-zinc-800">{item.quantity}</td>
                                        <td className="px-5 py-3.5 text-right font-bold text-zinc-900">
                                            {formatNumber(item.total_price)} {order.currency || 'FCFA'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <div className="border-t border-zinc-100 bg-zinc-50/40 px-5 py-3 text-right">
                            <span className="text-xs text-zinc-500 mr-3">Total payé :</span>
                            <span className="font-display text-base font-extrabold text-zinc-900">
                                {formatNumber(order.total_amount)} {order.currency || 'FCFA'}
                            </span>
                        </div>
                    </div>

                    {/* COMMISSIONS GÉNÉRÉES */}
                    <div className="overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-xs">
                        <div className="border-b border-zinc-200 bg-zinc-50/70 px-5 py-3">
                            <h2 className="font-display text-xs font-bold uppercase tracking-wider text-zinc-700">
                                Commissions d'affiliation générées
                            </h2>
                        </div>
                        <table className="w-full text-left text-xs">
                            <thead className="border-b border-zinc-100 bg-zinc-50 text-[10px] uppercase tracking-wider text-zinc-400">
                                <tr>
                                    <th className="px-5 py-2.5">Bénéficiaire</th>
                                    <th className="px-5 py-2.5">Niveau</th>
                                    <th className="px-5 py-2.5">Montant</th>
                                    <th className="px-5 py-2.5">Statut</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-zinc-100">
                                {commissions && commissions.length > 0 ? (
                                    commissions.map((c) => (
                                        <tr key={c.id}>
                                            <td className="px-5 py-3">
                                                <p className="font-semibold text-zinc-900">
                                                    {c.user ? `${c.user.first_name} ${c.user.last_name}` : '—'}
                                                </p>
                                                <p className="font-mono text-[10px] text-zinc-400">{c.user?.affiliate_id}</p>
                                            </td>
                                            <td className="px-5 py-3 font-medium text-zinc-600">Niveau {c.level}</td>
                                            <td className="px-5 py-3 font-bold text-accent">
                                                +{formatNumber(c.amount)} {c.currency}
                                            </td>
                                            <td className="px-5 py-3">
                                                <span className="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-semibold text-zinc-700">
                                                    {c.status}
                                                </span>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="4" className="px-5 py-6 text-center text-zinc-500">
                                            Aucune commission distribuée pour cette commande.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* COLONNE DROITE : CLIENT & EXPÉDITION */}
                <div className="space-y-6">
                    {/* CLIENT */}
                    <div className="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-xs text-xs">
                        <h3 className="font-display text-xs font-bold uppercase tracking-wider text-zinc-500">
                            Client & Livraison
                        </h3>
                        <dl className="mt-3.5 space-y-2">
                            <div>
                                <dt className="text-zinc-400">Nom</dt>
                                <dd className="font-semibold text-zinc-900">
                                    {order.user ? `${order.user.first_name} ${order.user.last_name}` : 'Client invité'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-zinc-400">E-mail</dt>
                                <dd className="font-medium text-zinc-800">{order.customer_email || order.user?.email || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-zinc-400">Téléphone</dt>
                                <dd className="font-medium text-zinc-800">{order.shipping_phone || order.customer_phone || '—'}</dd>
                            </div>
                            {order.shipping_address && (
                                <div>
                                    <dt className="text-zinc-400">Adresse de livraison</dt>
                                    <dd className="mt-1 rounded-lg bg-zinc-50 p-2 text-zinc-700 leading-relaxed">
                                        {order.shipping_address}, {order.shipping_city}, {order.shipping_country}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    {/* EXPÉDITION / LIVRAISON PHYSIQUE */}
                    {order.has_physical_items && (
                        <div className="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-xs text-xs">
                            <h3 className="font-display text-xs font-bold uppercase tracking-wider text-zinc-500">
                                Expédition physique
                            </h3>
                            <form onSubmit={submitFulfilment} className="mt-4 space-y-3">
                                <div>
                                    <label className="font-medium text-zinc-700">Étape d'expédition</label>
                                    <select
                                        value={fulfilmentForm.data.shipping_status}
                                        onChange={(e) => fulfilmentForm.setData('shipping_status', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border border-zinc-300 p-2 shadow-2xs focus:border-accent focus:outline-none"
                                    >
                                        <option value="preparing">En préparation</option>
                                        <option value="shipped">Expédié</option>
                                        <option value="in_delivery">En cours de livraison</option>
                                        <option value="delivered">Livré</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="font-medium text-zinc-700">N° de suivi (tracking)</label>
                                    <input
                                        type="text"
                                        placeholder="Ex. DHL-2938102"
                                        value={fulfilmentForm.data.tracking_number}
                                        onChange={(e) => fulfilmentForm.setData('tracking_number', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border border-zinc-300 px-3 py-1.5 shadow-2xs focus:border-accent focus:outline-none"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    disabled={fulfilmentForm.processing}
                                    className="w-full rounded-xl bg-zinc-900 px-4 py-2 font-semibold text-white shadow-2xs hover:bg-zinc-800"
                                >
                                    Mettre à jour le suivi
                                </button>
                            </form>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
