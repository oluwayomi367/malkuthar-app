import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({
    orders = { data: [] },
    filters = {},
    kpi = {},
    heading = 'Commandes',
    eyebrow = 'Boutique',
}) {
    const [q, setQ] = useState(filters.q || '');
    const [provider, setProvider] = useState(filters.provider || '');
    const [paymentStatus, setPaymentStatus] = useState(filters.paymentStatus || '');
    const [status, setStatus] = useState(filters.status || '');

    const handleFilter = (newFilters = {}) => {
        const query = {
            q: newFilters.q !== undefined ? newFilters.q : q,
            provider: newFilters.provider !== undefined ? newFilters.provider : provider,
            payment_status: newFilters.paymentStatus !== undefined ? newFilters.paymentStatus : paymentStatus,
            status: newFilters.status !== undefined ? newFilters.status : status,
        };
        // Nettoyer les clés vides
        Object.keys(query).forEach((k) => !query[k] && delete query[k]);

        router.get(route('admin.orders.index'), query, { preserveState: true, preserveScroll: true });
    };

    const formatNumber = (num) => {
        if (num === null || num === undefined) return '0';
        return Number(num).toLocaleString('fr-FR');
    };

    return (
        <AdminLayout title="Commandes" eyebrow={eyebrow} heading={heading}>
            {/* KPIS RAPIDES */}
            <div className="mb-6 grid gap-4 sm:grid-cols-3">
                <div className="rounded-2xl border border-night-line bg-night-2 p-4">
                    <p className="text-xs font-bold uppercase tracking-wider text-night-ink-2">
                        À encaisser (COD)
                    </p>
                    <p className="mt-1 font-display text-2xl font-semibold text-amber-tint">
                        {formatNumber(kpi.cod_amount)} FCFA
                    </p>
                    <p className="text-xs text-night-ink-2">{kpi.cod_count || 0} commande(s) en attente</p>
                </div>

                <div className="rounded-2xl border border-night-line bg-night-2 p-4">
                    <p className="text-xs font-bold uppercase tracking-wider text-night-ink-2">
                        Colis à expédier
                    </p>
                    <p className="mt-1 font-display text-2xl font-semibold text-night-ink">
                        {kpi.to_prepare || 0}
                    </p>
                    <p className="text-xs text-night-ink-2">Commandes physiques payées en préparation</p>
                </div>

                <div className="rounded-2xl border border-night-line bg-night-2 p-4">
                    <p className="text-xs font-bold uppercase tracking-wider text-night-ink-2">
                        Chiffre d'affaires aujourd'hui
                    </p>
                    <p className="mt-1 font-display text-2xl font-semibold text-sage-soft">
                        {formatNumber(kpi.revenue_today)} FCFA
                    </p>
                    <p className="text-xs text-night-ink-2">Encaissé ce jour</p>
                </div>
            </div>

            {/* BARRE DE FILTRES */}
            <div className="mb-6 flex flex-wrap items-center gap-3">
                <input
                    type="search"
                    placeholder="N° commande, client, email…"
                    value={q}
                    onChange={(e) => setQ(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleFilter({ q })}
                    className="flex-1 min-w-[200px] rounded-xl border border-night-ink-2/60 bg-night px-3.5 py-2.5 text-sm text-night-ink placeholder:text-night-ink-2/70 focus:border-accent-light focus:outline-none min-h-11"
                />

                <select
                    value={provider}
                    onChange={(e) => {
                        setProvider(e.target.value);
                        handleFilter({ provider: e.target.value });
                    }}
                    className="rounded-xl border border-night-ink-2/60 bg-night px-3 py-2.5 text-sm text-night-ink-2 focus:border-accent-light focus:outline-none min-h-11"
                >
                    <option value="">Tous les paiements</option>
                    <option value="fedapay">FedaPay</option>
                    <option value="cinetpay">CinetPay</option>
                    <option value="cod">À la livraison (COD)</option>
                </select>

                <select
                    value={paymentStatus}
                    onChange={(e) => {
                        setPaymentStatus(e.target.value);
                        handleFilter({ paymentStatus: e.target.value });
                    }}
                    className="rounded-xl border border-night-ink-2/60 bg-night px-3 py-2.5 text-sm text-night-ink-2 focus:border-accent-light focus:outline-none min-h-11"
                >
                    <option value="">Tous les statuts paiement</option>
                    <option value="paid">Payé</option>
                    <option value="pending">En attente</option>
                    <option value="failed">Échoué</option>
                    <option value="refunded">Remboursé</option>
                </select>

                <button
                    type="button"
                    onClick={() => handleFilter({ q })}
                    className="rounded-full bg-accent-light px-4 py-2 text-sm font-semibold text-night hover:bg-accent-light/85 min-h-11"
                >
                    Filtrer
                </button>
            </div>

            {/* TABLEAU DES COMMANDES */}
            <div className="overflow-x-auto rounded-2xl border border-night-line bg-night-2">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-night-line text-xs font-semibold uppercase tracking-[0.08em] text-night-ink-2">
                        <tr>
                            <th className="px-4 py-3">N° Commande</th>
                            <th className="px-4 py-3">Date</th>
                            <th className="px-4 py-3">Client</th>
                            <th className="px-4 py-3">Paiement</th>
                            <th className="px-4 py-3">Montant</th>
                            <th className="px-4 py-3">Statut</th>
                            <th className="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-night-line">
                        {orders.data && orders.data.length > 0 ? (
                            orders.data.map((o) => (
                                <tr key={o.id} className="hover:bg-night-line/50">
                                    <td className="px-4 py-3.5 font-mono font-bold text-night-ink">
                                        <Link
                                            href={route('admin.orders.show', o.id)}
                                            className="hover:text-accent hover:underline"
                                        >
                                            {o.order_number}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3.5 text-night-ink-2">
                                        {o.created_at ? new Date(o.created_at).toLocaleDateString('fr-FR') : '—'}
                                    </td>
                                    <td className="px-4 py-3.5">
                                        <p className="font-semibold text-night-ink">
                                            {o.user ? `${o.user.first_name} ${o.user.last_name}` : 'Client invité'}
                                        </p>
                                        <p className="text-xs text-night-ink-2">{o.customer_email || o.user?.email}</p>
                                    </td>
                                    <td className="px-4 py-3.5">
                                        <span className="font-medium text-night-ink-2 uppercase tracking-wider text-xs">
                                            {o.payment_provider || 'Direct'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3.5 font-bold text-night-ink">
                                        {formatNumber(o.total_amount)} {o.currency || 'FCFA'}
                                    </td>
                                    <td className="px-4 py-3.5">
                                        {o.payment_status === 'paid' ? (
                                            <span className="rounded-full bg-sage/30 px-2.5 py-0.5 text-xs font-semibold text-sage-soft">
                                                Payé
                                            </span>
                                        ) : o.payment_status === 'pending' ? (
                                            <span className="rounded-full bg-amber/25 px-2.5 py-0.5 text-xs font-semibold text-amber-tint">
                                                En attente
                                            </span>
                                        ) : (
                                            <span className="rounded-full bg-night-line px-2.5 py-0.5 text-xs font-semibold text-night-ink-2">
                                                {o.payment_status}
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3.5 text-right">
                                        <Link
                                            href={route('admin.orders.show', o.id)}
                                            className="font-semibold text-accent-light hover:text-night-ink"
                                        >
                                            Détails
                                        </Link>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan="7" className="px-4 py-12 text-center text-night-ink-2">
                                    Aucune commande ne correspond aux filtres.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
