import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({
    orders = { data: [] },
    counts = {},
    payment_status = '',
    q = '',
}) {
    const [search, setSearch] = useState(q || '');
    const [cancelReasons, setCancelReasons] = useState({});

    const handleFilter = (newStatus) => {
        router.get(
            route('admin.marketplace.orders.index'),
            {
                payment_status: newStatus || undefined,
                q: search || undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        handleFilter(payment_status);
    };

    const handleMarkPaid = (orderId) => {
        if (confirm('Marquer cette commande comme payée / encaissée ?')) {
            router.post(route('admin.marketplace.orders.mark-paid', orderId));
        }
    };

    const handleCancel = (e, orderId) => {
        e.preventDefault();
        const reason = cancelReasons[orderId];
        if (!reason || reason.trim().length < 3) {
            alert('Veuillez indiquer un motif d’annulation (au moins 3 caractères).');
            return;
        }
        router.post(route('admin.marketplace.orders.cancel', orderId), { reason });
    };

    const formatPrice = (val) => {
        if (val === null || val === undefined) return '0';
        return Number(val).toLocaleString('fr-FR');
    };

    const statusPill = (st) => {
        switch (st) {
            case 'paid':
                return (
                    <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
                        Payée
                    </span>
                );
            case 'pending':
                return (
                    <span className="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                        À encaisser
                    </span>
                );
            case 'failed':
                return (
                    <span className="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800">
                        Annulée
                    </span>
                );
            default:
                return (
                    <span className="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-600">
                        {st}
                    </span>
                );
        }
    };

    const tabs = [
        { key: '', label: 'Toutes' },
        { key: 'pending', label: 'À encaisser' },
        { key: 'paid', label: 'Payées' },
        { key: 'failed', label: 'Annulées' },
    ];

    return (
        <AdminLayout
            title="Commandes Marketplace"
            eyebrow="Marketplace"
            heading="Commandes"
        >
            {/* ONGLETS STATUS */}
            <nav className="mb-6 flex flex-wrap gap-2 text-xs font-medium">
                {tabs.map((tab) => {
                    const isActive = (payment_status || '') === tab.key;
                    return (
                        <button
                            key={tab.key}
                            type="button"
                            onClick={() => handleFilter(tab.key)}
                            className={`rounded-full px-3.5 py-1.5 transition-colors cursor-pointer ${
                                isActive
                                    ? 'bg-accent text-white shadow-xs'
                                    : 'border border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900'
                            }`}
                        >
                            {tab.label}
                            {tab.key && counts[tab.key] !== undefined && (
                                <span className={`ml-1.5 font-bold ${isActive ? 'text-white/90' : 'text-zinc-400'}`}>
                                    ({counts[tab.key]})
                                </span>
                            )}
                        </button>
                    );
                })}
            </nav>

            {/* FORMULAIRE RECHERCHE */}
            <form onSubmit={handleSearch} className="mb-6 flex flex-wrap items-center gap-3">
                <input
                    type="search"
                    placeholder="Numéro de commande, email acheteur…"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="flex-1 min-w-[260px] rounded-xl border border-zinc-300 bg-white px-3.5 py-2 text-xs text-zinc-900 shadow-2xs placeholder:text-zinc-400 focus:border-accent focus:outline-none"
                />
                <button
                    type="submit"
                    className="rounded-xl border border-zinc-300 bg-white px-4 py-2 text-xs font-semibold text-zinc-800 shadow-2xs transition-colors hover:bg-zinc-50"
                >
                    Filtrer
                </button>
            </form>

            {/* LISTE DES COMMANDES */}
            {(!orders.data || orders.data.length === 0) ? (
                <div className="rounded-2xl border border-dashed border-zinc-300 bg-white p-12 text-center shadow-xs">
                    <p className="font-serif text-base font-semibold text-zinc-800">Aucune commande</p>
                    <p className="mt-1 text-xs text-zinc-500">Aucune commande pour ce filtre.</p>
                </div>
            ) : (
                <div className="space-y-3">
                    {orders.data.map((order) => (
                        <div
                            key={order.id}
                            className="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-shadow hover:shadow-sm"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <Link
                                        href={route('admin.marketplace.orders.show', order.id)}
                                        className="font-mono text-sm font-bold text-zinc-900 hover:text-accent"
                                    >
                                        {order.order_number}
                                    </Link>
                                    <p className="mt-1 text-xs text-zinc-500">
                                        {[
                                            order.buyer_email,
                                            order.suborders_count !== undefined
                                                ? `${order.suborders_count} vendeur(s)`
                                                : null,
                                            order.created_at
                                                ? new Date(order.created_at).toLocaleString('fr-FR', {
                                                      day: '2-digit',
                                                      month: '2-digit',
                                                      year: 'numeric',
                                                      hour: '2-digit',
                                                      minute: '2-digit',
                                                  })
                                                : null,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                </div>
                                <div className="flex items-center gap-3">
                                    <span className="text-sm font-bold text-zinc-900">
                                        {formatPrice(order.grand_total)} {order.currency || 'XOF'}
                                    </span>
                                    {statusPill(order.payment_status)}
                                </div>
                            </div>

                            {/* ACTIONS SI EN ATTENTE */}
                            {order.payment_status === 'pending' && (
                                <div className="mt-4 flex flex-wrap items-center gap-2.5 border-t border-zinc-100 pt-3.5">
                                    <button
                                        type="button"
                                        onClick={() => handleMarkPaid(order.id)}
                                        className="rounded-xl border border-emerald-300 bg-emerald-50 px-3.5 py-1.5 text-xs font-bold text-emerald-800 shadow-2xs hover:bg-emerald-100 transition-colors"
                                    >
                                        Encaisser
                                    </button>

                                    <form onSubmit={(e) => handleCancel(e, order.id)} className="flex items-center gap-1.5">
                                        <input
                                            type="text"
                                            required
                                            placeholder="Motif d'annulation"
                                            value={cancelReasons[order.id] || ''}
                                            onChange={(e) =>
                                                setCancelReasons({ ...cancelReasons, [order.id]: e.target.value })
                                            }
                                            className="h-8 w-52 rounded-xl border border-zinc-300 bg-white px-3 text-xs text-zinc-900 shadow-2xs placeholder:text-zinc-400 focus:border-accent focus:outline-none"
                                        />
                                        <button
                                            type="submit"
                                            className="rounded-xl border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-800 shadow-2xs hover:bg-rose-100 transition-colors"
                                        >
                                            Annuler
                                        </button>
                                    </form>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {/* PAGINATION */}
            {orders.links && orders.links.length > 3 && (
                <div className="mt-6 flex flex-wrap justify-center gap-1.5">
                    {orders.links.map((link, idx) => (
                        <Link
                            key={idx}
                            href={link.url || '#'}
                            preserveScroll
                            disabled={!link.url}
                            className={`rounded-xl px-3 py-1.5 text-xs font-medium transition-colors ${
                                link.active
                                    ? 'bg-accent text-white shadow-2xs'
                                    : link.url
                                    ? 'border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50'
                                    : 'border border-zinc-100 bg-zinc-50 text-zinc-300 cursor-not-allowed'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
