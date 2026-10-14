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
                className="rounded-full border border-night-line bg-night-2 px-3.5 py-1.5 text-sm font-semibold text-night-ink-2 hover:bg-night-line/50 min-h-11 inline-flex items-center justify-center"
            >
                Liste
            </Link>

            {order.payment_provider === 'cod' && order.payment_status === 'pending' && order.status !== 'cancelled' && (
                <button
                    type="button"
                    onClick={submitCollectCod}
                    className="rounded-full bg-sage px-4 py-1.5 text-sm font-semibold text-white hover:bg-sage/85 min-h-11"
                >
                    Encaisser
                </button>
            )}

            {order.status !== 'cancelled' && order.status !== 'completed' && (
                <button
                    type="button"
                    onClick={submitCancel}
                    className="rounded-full border border-rust bg-night-2 px-3.5 py-1.5 text-sm font-semibold text-rust-tint hover:bg-rust/20 min-h-11"
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
            <div className="mb-6 flex flex-wrap items-center gap-3 text-sm text-night-ink-2">
                <span className="font-mono font-bold text-night-ink">{order.order_number}</span>
                <span>·</span>
                <span>Paiement : <strong className="text-night-ink uppercase">{order.payment_provider}</strong></span>
                <span>·</span>
                {order.payment_status === 'paid' ? (
                    <span className="rounded-full bg-sage/30 px-2.5 py-0.5 font-semibold text-sage-soft">
                        Paiement validé
                    </span>
                ) : (
                    <span className="rounded-full bg-amber/25 px-2.5 py-0.5 font-semibold text-amber-tint">
                        {order.payment_status}
                    </span>
                )}
            </div>

            {/* GRILLE PRINCIPALE : DÉTAIL COMMANDE + ACTIONS */}
            <div className="grid gap-8 lg:grid-cols-[1fr_360px]">
                {/* COLONNE GAUCHE : ARTICLES + COMMISSIONS */}
                <div className="space-y-6">
                    {/* ARTICLES */}
                    <div className="overflow-x-auto rounded-2xl border border-night-line bg-night-2">
                        <div className="border-b border-night-line bg-night px-5 py-3">
                            <h2 className="font-sans text-xs font-semibold uppercase tracking-wider text-night-ink-2">
                                Articles commandés
                            </h2>
                        </div>
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-night-line text-xs font-semibold uppercase tracking-[0.08em] text-night-ink-2">
                                <tr>
                                    <th className="px-5 py-2.5">Article</th>
                                    <th className="px-5 py-2.5">Type</th>
                                    <th className="px-5 py-2.5">Qté</th>
                                    <th className="px-5 py-2.5 text-right">Prix</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-night-line">
                                {order.items && order.items.map((item) => (
                                    <tr key={item.id} className="hover:bg-night-line/50">
                                        <td className="px-5 py-3.5">
                                            <p className="font-semibold text-night-ink">{item.product_name}</p>
                                            {item.variant_label && (
                                                <p className="text-xs text-night-ink-2">Variante : {item.variant_label}</p>
                                            )}
                                        </td>
                                        <td className="px-5 py-3.5 text-night-ink-2 uppercase text-sm">
                                            {item.product_type}
                                        </td>
                                        <td className="px-5 py-3.5 font-semibold text-night-ink">{item.quantity}</td>
                                        <td className="px-5 py-3.5 text-right font-bold text-night-ink">
                                            {formatNumber(item.total_price)} {order.currency || 'FCFA'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <div className="border-t border-night-line bg-night px-5 py-3 text-right">
                            <span className="text-xs text-night-ink-2 mr-3">Total payé :</span>
                            <span className="font-sans text-base font-semibold text-night-ink">
                                {formatNumber(order.total_amount)} {order.currency || 'FCFA'}
                            </span>
                        </div>
                    </div>

                    {/* COMMISSIONS GÉNÉRÉES */}
                    <div className="overflow-x-auto rounded-2xl border border-night-line bg-night-2">
                        <div className="border-b border-night-line bg-night px-5 py-3">
                            <h2 className="font-sans text-xs font-semibold uppercase tracking-wider text-night-ink-2">
                                Commissions d'affiliation générées
                            </h2>
                        </div>
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-night-line text-xs font-semibold uppercase tracking-[0.08em] text-night-ink-2">
                                <tr>
                                    <th className="px-5 py-2.5">Bénéficiaire</th>
                                    <th className="px-5 py-2.5">Niveau</th>
                                    <th className="px-5 py-2.5">Montant</th>
                                    <th className="px-5 py-2.5">Statut</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-night-line">
                                {commissions && commissions.length > 0 ? (
                                    commissions.map((c) => (
                                        <tr key={c.id}>
                                            <td className="px-5 py-3">
                                                <p className="font-semibold text-night-ink">
                                                    {c.user ? `${c.user.first_name} ${c.user.last_name}` : '—'}
                                                </p>
                                                <p className="font-mono text-xs text-night-ink-2">{c.user?.affiliate_id}</p>
                                            </td>
                                            <td className="px-5 py-3 font-medium text-night-ink-2">Niveau {c.level}</td>
                                            <td className="px-5 py-3 font-bold text-accent-light">
                                                +{formatNumber(c.amount)} {c.currency}
                                            </td>
                                            <td className="px-5 py-3">
                                                <span className="rounded-full bg-night-line px-2 py-0.5 text-xs font-semibold text-night-ink-2">
                                                    {c.status}
                                                </span>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="4" className="px-5 py-6 text-center text-night-ink-2">
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
                    <div className="rounded-2xl border border-night-line bg-night-2 p-5 text-sm">
                        <h3 className="font-sans text-xs font-semibold uppercase tracking-wider text-night-ink-2">
                            Client & Livraison
                        </h3>
                        <dl className="mt-3.5 space-y-2">
                            <div>
                                <dt className="text-night-ink-2">Nom</dt>
                                <dd className="font-semibold text-night-ink">
                                    {order.user ? `${order.user.first_name} ${order.user.last_name}` : 'Client invité'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-night-ink-2">E-mail</dt>
                                <dd className="font-medium text-night-ink">{order.customer_email || order.user?.email || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-night-ink-2">Téléphone</dt>
                                <dd className="font-medium text-night-ink">{order.shipping_phone || order.customer_phone || '—'}</dd>
                            </div>
                            {order.shipping_address && (
                                <div>
                                    <dt className="text-night-ink-2">Adresse de livraison</dt>
                                    <dd className="mt-1 rounded-lg bg-night p-2 text-night-ink-2 leading-relaxed">
                                        {order.shipping_address}, {order.shipping_city}, {order.shipping_country}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    {/* EXPÉDITION / LIVRAISON PHYSIQUE */}
                    {order.has_physical_items && (
                        <div className="rounded-2xl border border-night-line bg-night-2 p-5 text-sm">
                            <h3 className="font-sans text-xs font-semibold uppercase tracking-wider text-night-ink-2">
                                Expédition physique
                            </h3>
                            <form onSubmit={submitFulfilment} className="mt-4 space-y-3">
                                <div>
                                    <label className="font-medium text-night-ink-2">Étape d'expédition</label>
                                    <select
                                        value={fulfilmentForm.data.shipping_status}
                                        onChange={(e) => fulfilmentForm.setData('shipping_status', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border border-night-ink-2/60 p-2 focus:border-accent-light focus:outline-none min-h-11"
                                    >
                                        <option value="preparing">En préparation</option>
                                        <option value="shipped">Expédié</option>
                                        <option value="in_delivery">En cours de livraison</option>
                                        <option value="delivered">Livré</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="font-medium text-night-ink-2">N° de suivi (tracking)</label>
                                    <input
                                        type="text"
                                        placeholder="Ex. DHL-2938102"
                                        value={fulfilmentForm.data.tracking_number}
                                        onChange={(e) => fulfilmentForm.setData('tracking_number', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-3 py-2.5 focus:border-accent-light focus:outline-none min-h-11"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    disabled={fulfilmentForm.processing}
                                    className="w-full rounded-full bg-accent-light px-4 py-2 font-semibold text-night hover:bg-accent-light/85 min-h-11"
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
