import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({
    vendors = { data: [] },
    q = '',
    status = '',
    region = '',
    counts = {},
}) {
    const [search, setSearch] = useState(q || '');
    const [regionFilter, setRegionFilter] = useState(region || '');
    const [rejectReasons, setRejectReasons] = useState({});

    const handleFilter = (newStatus) => {
        router.get(
            route('admin.marketplace.vendors.index'),
            {
                q: search || undefined,
                region: regionFilter || undefined,
                status: newStatus !== undefined ? newStatus : status || undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        handleFilter(status);
    };

    const handleApprove = (vendorId) => {
        if (confirm('Approuver ce compte vendeur ?')) {
            router.post(route('admin.marketplace.vendors.approve', vendorId));
        }
    };

    const handleReject = (e, vendorId) => {
        e.preventDefault();
        const reason = rejectReasons[vendorId];
        if (!reason || reason.trim().length < 5) {
            alert('Veuillez indiquer un motif de refus d’au moins 5 caractères.');
            return;
        }
        router.post(route('admin.marketplace.vendors.reject', vendorId), { reason });
    };

    const statusPill = (st) => {
        switch (st) {
            case 'active':
                return (
                    <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
                        Actif
                    </span>
                );
            case 'pending':
                return (
                    <span className="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                        En attente
                    </span>
                );
            case 'suspended':
                return (
                    <span className="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800">
                        Suspendu
                    </span>
                );
            case 'closed':
                return (
                    <span className="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-600">
                        Fermé
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

    const statusTabs = [
        { key: '', label: 'Tous' },
        { key: 'pending', label: 'En attente' },
        { key: 'active', label: 'Actifs' },
        { key: 'suspended', label: 'Suspendus' },
        { key: 'closed', label: 'Fermés' },
    ];

    return (
        <AdminLayout
            title="Comptes vendeurs"
            eyebrow="Marketplace"
            heading="Comptes vendeurs"
        >
            {/* ONGLETS STATUS */}
            <nav className="mb-6 flex flex-wrap gap-2 text-xs font-medium">
                {statusTabs.map((tab) => {
                    const isActive = (status || '') === tab.key;
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
                    placeholder="Nom, raison sociale, email…"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="flex-1 min-w-[240px] rounded-xl border border-zinc-300 bg-white px-3.5 py-2 text-xs text-zinc-900 shadow-2xs placeholder:text-zinc-400 focus:border-accent focus:outline-none"
                />
                <input
                    type="text"
                    placeholder="Région"
                    value={regionFilter}
                    onChange={(e) => setRegionFilter(e.target.value)}
                    className="w-40 rounded-xl border border-zinc-300 bg-white px-3.5 py-2 text-xs text-zinc-900 shadow-2xs placeholder:text-zinc-400 focus:border-accent focus:outline-none"
                />
                <button
                    type="submit"
                    className="rounded-xl border border-zinc-300 bg-white px-4 py-2 text-xs font-semibold text-zinc-800 shadow-2xs transition-colors hover:bg-zinc-50"
                >
                    Filtrer
                </button>
            </form>

            {/* LISTE DES VENDEURS */}
            {(!vendors.data || vendors.data.length === 0) ? (
                <div className="rounded-2xl border border-dashed border-zinc-300 bg-white p-12 text-center shadow-xs">
                    <p className="font-serif text-base font-semibold text-zinc-800">Aucun vendeur</p>
                    <p className="mt-1 text-xs text-zinc-500">Aucun compte ne correspond à ces critères.</p>
                </div>
            ) : (
                <div className="space-y-3">
                    {vendors.data.map((v) => (
                        <div
                            key={v.id}
                            className="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-shadow hover:shadow-sm"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <Link
                                        href={route('admin.marketplace.vendors.show', v.id)}
                                        className="font-serif text-base font-semibold text-zinc-900 hover:text-accent"
                                    >
                                        {v.display_name}
                                    </Link>
                                    <p className="mt-1 text-xs text-zinc-500">
                                        {[
                                            v.user ? `${v.user.first_name || ''} ${v.user.last_name || ''}`.trim() : null,
                                            v.user?.email,
                                            v.region,
                                            v.created_at ? `demandé le ${new Date(v.created_at).toLocaleDateString('fr-FR')}` : null,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                </div>
                                <div className="flex items-center gap-3">
                                    {v.commission_rate_override !== null && v.commission_rate_override !== undefined && (
                                        <span className="text-xs font-medium text-zinc-500">
                                            comm. {parseFloat((Number(v.commission_rate_override) * 100).toFixed(2))} %
                                        </span>
                                    )}
                                    {statusPill(v.status)}
                                </div>
                            </div>

                            {/* ACTIONS EN ATTENTE */}
                            {v.status === 'pending' && (
                                <div className="mt-4 flex flex-wrap items-center gap-2.5 border-t border-zinc-100 pt-3.5">
                                    <button
                                        type="button"
                                        onClick={() => handleApprove(v.id)}
                                        className="rounded-xl border border-emerald-300 bg-emerald-50 px-3.5 py-1.5 text-xs font-bold text-emerald-800 shadow-2xs hover:bg-emerald-100 transition-colors"
                                    >
                                        Approuver
                                    </button>
                                    <form onSubmit={(e) => handleReject(e, v.id)} className="flex items-center gap-2">
                                        <input
                                            type="text"
                                            required
                                            placeholder="Motif de refus"
                                            value={rejectReasons[v.id] || ''}
                                            onChange={(e) =>
                                                setRejectReasons({ ...rejectReasons, [v.id]: e.target.value })
                                            }
                                            className="h-8 w-56 rounded-xl border border-zinc-300 bg-white px-3 text-xs text-zinc-900 shadow-2xs placeholder:text-zinc-400 focus:border-accent focus:outline-none"
                                        />
                                        <button
                                            type="submit"
                                            className="rounded-xl border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-800 shadow-2xs hover:bg-rose-100 transition-colors"
                                        >
                                            Refuser
                                        </button>
                                    </form>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {/* PAGINATION */}
            {vendors.links && vendors.links.length > 3 && (
                <div className="mt-6 flex flex-wrap justify-center gap-1.5">
                    {vendors.links.map((link, idx) => (
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
