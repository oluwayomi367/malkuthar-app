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
                <div className="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-xs">
                    <p className="text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                        À encaisser (COD)
                    </p>
                    <p className="mt-1 font-display text-2xl font-extrabold text-amber-700">
                        {formatNumber(kpi.cod_amount)} FCFA
                    </p>
                    <p className="text-xs text-zinc-500">{kpi.cod_count || 0} commande(s) en attente</p>
                </div>

                <div className="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-xs">
                    <p className="text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                        Colis à expédier
                    </p>
                    <p className="mt-1 font-display text-2xl font-extrabold text-zinc-900">
                        {kpi.to_prepare || 0}
                    </p>
                    <p className="text-xs text-zinc-500">Commandes physiques payées en préparation</p>
                </div>

                <div className="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-xs">
                    <p className="text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                        Chiffre d'affaires aujourd'hui
                    </p>
                    <p className="mt-1 font-display text-2xl font-extrabold text-emerald-600">
                        {formatNumber(kpi.revenue_today)} FCFA
                    </p>
                    <p className="text-xs text-zinc-500">Encaissé ce jour</p>
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
                    className="flex-1 min-w-[200px] rounded-xl border border-zinc-300 bg-white px-3.5 py-2 text-xs text-zinc-900 shadow-2xs placeholder:text-zinc-400 focus:border-accent focus:outline-none"
                />

                <select
                    value={provider}
                    onChange={(e) => {
                        setProvider(e.target.value);
                        handleFilter({ provider: e.target.value });
                    }}
                    className="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-700 shadow-2xs focus:border-accent focus:outline-none"
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
                    className="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-700 shadow-2xs focus:border-accent focus:outline-none"
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
                    className="rounded-xl bg-zinc-900 px-4 py-2 text-xs font-semibold text-white shadow-2xs hover:bg-zinc-800"
                >
                    Filtrer
                </button>
            </div>

            {/* TABLEAU DES COMMANDES */}
            <div className="overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-xs">
                <table className="w-full text-left text-xs">
                    <thead className="border-b border-zinc-200 bg-zinc-50 text-[10px] font-bold uppercase tracking-wider text-zinc-500">
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
                    <tbody className="divide-y divide-zinc-100">
                        {orders.data && orders.data.length > 0 ? (
                            orders.data.map((o) => (
                                <tr key={o.id} className="hover:bg-zinc-50/60">
                                    <td className="px-4 py-3.5 font-mono font-bold text-zinc-900">
                                        <Link
                                            href={route('admin.orders.show', o.id)}
                                            className="hover:text-accent hover:underline"
                                        >
                                            {o.order_number}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3.5 text-zinc-500">
                                        {o.created_at ? new Date(o.created_at).toLocaleDateString('fr-FR') : '—'}
                                    </td>
                                    <td className="px-4 py-3.5">
                                        <p className="font-semibold text-zinc-900">
                                            {o.user ? `${o.user.first_name} ${o.user.last_name}` : 'Client invité'}
                                        </p>
                                        <p className="text-[11px] text-zinc-400">{o.customer_email || o.user?.email}</p>
                                    </td>
                                    <td className="px-4 py-3.5">
                                        <span className="font-medium text-zinc-700 uppercase tracking-wider text-[10px]">
                                            {o.payment_provider || 'Direct'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3.5 font-bold text-zinc-900">
                                        {formatNumber(o.total_amount)} {o.currency || 'FCFA'}
                                    </td>
                                    <td className="px-4 py-3.5">
                                        {o.payment_status === 'paid' ? (
                                            <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-800">
                                                Payé
                                            </span>
                                        ) : o.payment_status === 'pending' ? (
                                            <span className="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-semibold text-amber-800">
                                                En attente
                                            </span>
                                        ) : (
                                            <span className="rounded-full bg-zinc-100 px-2.5 py-0.5 text-[10px] font-semibold text-zinc-700">
                                                {o.payment_status}
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3.5 text-right">
                                        <Link
                                            href={route('admin.orders.show', o.id)}
                                            className="font-semibold text-accent hover:text-accent-2"
                                        >
                                            Détails →
                                        </Link>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan="7" className="px-4 py-12 text-center text-zinc-500">
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
