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
                    <span className="rounded-full bg-sage-soft px-2.5 py-0.5 text-xs font-semibold text-sage">
                        Actif
                    </span>
                );
            case 'pending':
                return (
                    <span className="rounded-full bg-amber/25 px-2.5 py-0.5 text-xs font-semibold text-amber">
                        En attente
                    </span>
                );
            case 'suspended':
                return (
                    <span className="rounded-full bg-rust-tint px-2.5 py-0.5 text-xs font-semibold text-rust">
                        Suspendu
                    </span>
                );
            case 'closed':
                return (
                    <span className="rounded-full bg-night-line px-2.5 py-0.5 text-xs font-semibold text-night-ink-2">
                        Fermé
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
            <nav className="mb-6 flex flex-wrap gap-2 text-sm font-medium">
                {statusTabs.map((tab) => {
                    const isActive = (status || '') === tab.key;
                    return (
                        <button
                            key={tab.key}
                            type="button"
                            onClick={() => handleFilter(tab.key)}
                            className={`rounded-full px-3.5 py-1.5 transition-colors cursor-pointer min-h-11 ${
                                isActive
                                    ? 'bg-accent text-white '
                                    : 'border border-night-line bg-night-2 text-night-ink-2 hover:bg-night-line/50 hover:text-night-ink'
                            }`}
                        >
                            {tab.label}
                            {tab.key && counts[tab.key] !== undefined && (
                                <span className={`ml-1.5 font-bold ${isActive ? 'text-white' : 'text-night-ink-2'}`}>
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
                    className="flex-1 min-w-[240px] rounded-xl border border-night-ink-2/60 bg-night px-3.5 py-2.5 text-sm text-night-ink placeholder:text-night-ink-2/70 focus:border-accent focus:outline-none min-h-11"
                />
                <input
                    type="text"
                    placeholder="Région"
                    value={regionFilter}
                    onChange={(e) => setRegionFilter(e.target.value)}
                    className="w-40 rounded-xl border border-night-ink-2/60 bg-night px-3.5 py-2.5 text-sm text-night-ink placeholder:text-night-ink-2/70 focus:border-accent focus:outline-none min-h-11"
                />
                <button
                    type="submit"
                    className="rounded-full border border-night-ink-2/60 bg-night-2 px-4 py-2 text-sm font-semibold text-night-ink transition-colors hover:bg-night-line/50 min-h-11"
                >
                    Filtrer
                </button>
            </form>

            {/* LISTE DES VENDEURS */}
            {(!vendors.data || vendors.data.length === 0) ? (
                <div className="rounded-2xl border border-dashed border-night-ink-2/60 bg-night-2 p-12 text-center">
                    <p className="font-sans text-base font-semibold text-night-ink">Aucun vendeur</p>
                    <p className="mt-1 text-xs text-night-ink-2">Aucun compte ne correspond à ces critères.</p>
                </div>
            ) : (
                <div className="space-y-3">
                    {vendors.data.map((v) => (
                        <div
                            key={v.id}
                            className="rounded-2xl border border-night-line bg-night-2 p-5 transition-shadow"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <Link
                                        href={route('admin.marketplace.vendors.show', v.id)}
                                        className="text-base font-semibold text-night-ink hover:text-accent-2"
                                    >
                                        {v.display_name}
                                    </Link>
                                    <p className="mt-1 text-xs text-night-ink-2">
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
                                        <span className="text-xs font-medium text-night-ink-2">
                                            comm. {parseFloat((Number(v.commission_rate_override) * 100).toFixed(2))} %
                                        </span>
                                    )}
                                    {statusPill(v.status)}
                                </div>
                            </div>

                            {/* ACTIONS EN ATTENTE */}
                            {v.status === 'pending' && (
                                <div className="mt-4 flex flex-wrap items-center gap-2.5 border-t border-night-line pt-3.5">
                                    <button
                                        type="button"
                                        onClick={() => handleApprove(v.id)}
                                        className="rounded-full border border-sage bg-sage-soft px-3.5 py-1.5 text-sm font-bold text-sage hover:bg-sage-soft transition-colors min-h-11"
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
                                            className="h-11 w-56 rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink placeholder:text-night-ink-2/70 focus:border-accent focus:outline-none"
                                        />
                                        <button
                                            type="submit"
                                            className="rounded-full border border-rust bg-rust-tint px-3 py-1.5 text-sm font-bold text-rust hover:bg-rust/35 transition-colors min-h-11"
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
                                    ? 'bg-accent text-white '
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
