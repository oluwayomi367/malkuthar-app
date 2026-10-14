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
                    <span className="rounded-full bg-sage/30 px-2.5 py-0.5 text-xs font-semibold text-sage-soft">
                        Payée
                    </span>
                );
            case 'pending':
                return (
                    <span className="rounded-full bg-amber/25 px-2.5 py-0.5 text-xs font-semibold text-amber-tint">
                        À encaisser
                    </span>
                );
            case 'failed':
                return (
                    <span className="rounded-full bg-rust/20 px-2.5 py-0.5 text-xs font-semibold text-rust-tint">
                        Annulée
                    </span>
                );
            default:
                return (
                    <span className="rounded-full bg-night-line px-2.5 py-0.5 text-xs font-semibold text-night-ink-2">
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
            <nav className="mb-6 flex flex-wrap gap-2 text-sm font-medium">
                {tabs.map((tab) => {
                    const isActive = (payment_status || '') === tab.key;
                    return (
                        <button
                            key={tab.key}
                            type="button"
                            onClick={() => handleFilter(tab.key)}
                            className={`rounded-full px-3.5 py-1.5 transition-colors cursor-pointer min-h-11 ${
                                isActive
                                    ? 'bg-accent-light text-night '
                                    : 'border border-night-line bg-night-2 text-night-ink-2 hover:bg-night-line/50 hover:text-night-ink'
                            }`}
                        >
                            {tab.label}
                            {tab.key && counts[tab.key] !== undefined && (
                                <span className={`ml-1.5 font-bold ${isActive ? 'text-night' : 'text-night-ink-2'}`}>
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
                    className="flex-1 min-w-[260px] rounded-xl border border-night-ink-2/60 bg-night px-3.5 py-2.5 text-sm text-night-ink placeholder:text-night-ink-2/70 focus:border-accent-light focus:outline-none min-h-11"
                />
                <button
                    type="submit"
                    className="rounded-full border border-night-ink-2/60 bg-night-2 px-4 py-2 text-sm font-semibold text-night-ink transition-colors hover:bg-night-line/50 min-h-11"
                >
                    Filtrer
                </button>
            </form>

            {/* LISTE DES COMMANDES */}
            {(!orders.data || orders.data.length === 0) ? (
                <div className="rounded-2xl border border-dashed border-night-ink-2/60 bg-night-2 p-12 text-center">
                    <p className="font-sans text-base font-semibold text-night-ink">Aucune commande</p>
                    <p className="mt-1 text-xs text-night-ink-2">Aucune commande pour ce filtre.</p>
                </div>
            ) : (
                <div className="space-y-3">
                    {orders.data.map((order) => (
                        <div
                            key={order.id}
                            className="rounded-2xl border border-night-line bg-night-2 p-5 transition-shadow"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <Link
                                        href={route('admin.marketplace.orders.show', order.id)}
                                        className="font-mono text-sm font-bold text-night-ink hover:text-accent-light"
                                    >
                                        {order.order_number}
                                    </Link>
                                    <p className="mt-1 text-xs text-night-ink-2">
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
                                    <span className="text-sm font-bold text-night-ink">
                                        {formatPrice(order.grand_total)} {order.currency || 'XOF'}
                                    </span>
                                    {statusPill(order.payment_status)}
                                </div>
                            </div>

                            {/* ACTIONS SI EN ATTENTE */}
                            {order.payment_status === 'pending' && (
                                <div className="mt-4 flex flex-wrap items-center gap-2.5 border-t border-night-line pt-3.5">
                                    <button
                                        type="button"
                                        onClick={() => handleMarkPaid(order.id)}
                                        className="rounded-full border border-sage bg-sage/30 px-3.5 py-1.5 text-sm font-bold text-sage-soft hover:bg-sage/45 transition-colors min-h-11"
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
                                            className="h-11 w-52 rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink placeholder:text-night-ink-2/70 focus:border-accent-light focus:outline-none"
                                        />
                                        <button
                                            type="submit"
                                            className="rounded-full border border-rust bg-rust/20 px-3 py-1.5 text-sm font-bold text-rust-tint hover:bg-rust/35 transition-colors min-h-11"
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
                                    ? 'bg-accent-light text-night '
                                    : link.url
                                    ? 'border border-night-line bg-night-2 text-night-ink-2 hover:bg-night-line/50'
                                    : 'border border-night-line bg-night text-night-ink-2 cursor-not-allowed'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
