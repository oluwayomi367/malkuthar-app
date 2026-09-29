import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({ offers = { data: [] }, status = 'pending_review', counts = {} }) {
    const [changeNotes, setChangeNotes] = useState({});
    const [rejectReasons, setRejectReasons] = useState({});

    const handleFilter = (newStatus) => {
        router.get(
            route('admin.marketplace.offers.index'),
            { status: newStatus },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleApprove = (offerId) => {
        if (confirm('Approuver cette offre ?')) {
            router.post(route('admin.marketplace.offers.approve', offerId));
        }
    };

    const handleRequestChanges = (e, offerId) => {
        e.preventDefault();
        const note = changeNotes[offerId];
        if (!note || note.trim().length < 5) {
            alert('Veuillez indiquer les corrections demandées (au moins 5 caractères).');
            return;
        }
        router.post(route('admin.marketplace.offers.request-changes', offerId), { note });
    };

    const handleReject = (e, offerId) => {
        e.preventDefault();
        const reason = rejectReasons[offerId];
        if (!reason || reason.trim().length < 5) {
            alert('Veuillez indiquer un motif de refus (au moins 5 caractères).');
            return;
        }
        router.post(route('admin.marketplace.offers.reject', offerId), { reason });
    };

    const formatPrice = (val) => {
        if (val === null || val === undefined) return '0';
        return Number(val).toLocaleString('fr-FR');
    };

    const statusPill = (st) => {
        switch (st) {
            case 'approved':
                return (
                    <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
                        Approuvée
                    </span>
                );
            case 'pending_review':
                return (
                    <span className="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                        À valider
                    </span>
                );
            case 'changes_requested':
                return (
                    <span className="rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-semibold text-orange-800">
                        À corriger
                    </span>
                );
            case 'rejected':
                return (
                    <span className="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800">
                        Refusée
                    </span>
                );
            case 'suspended':
                return (
                    <span className="rounded-full bg-zinc-200 px-2.5 py-0.5 text-xs font-semibold text-zinc-700">
                        Suspendue
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
        { key: 'pending_review', label: 'À valider' },
        { key: 'changes_requested', label: 'À corriger' },
        { key: 'approved', label: 'Approuvées' },
        { key: 'rejected', label: 'Refusées' },
        { key: 'suspended', label: 'Suspendues' },
        { key: 'all', label: 'Toutes' },
    ];

    return (
        <AdminLayout
            title="Modération des offres"
            eyebrow="Marketplace"
            heading="Modération des offres"
        >
            {/* ONGLETS STATUS */}
            <nav className="mb-6 flex flex-wrap gap-2 text-xs font-medium">
                {tabs.map((tab) => {
                    const isActive = status === tab.key;
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
                            {tab.key !== 'all' && counts[tab.key] !== undefined && (
                                <span className={`ml-1.5 font-bold ${isActive ? 'text-white/90' : 'text-zinc-400'}`}>
                                    ({counts[tab.key]})
                                </span>
                            )}
                        </button>
                    );
                })}
            </nav>

            {/* LISTE DES OFFRES */}
            {(!offers.data || offers.data.length === 0) ? (
                <div className="rounded-2xl border border-dashed border-zinc-300 bg-white p-12 text-center shadow-xs">
                    <p className="font-serif text-base font-semibold text-zinc-800">Rien à afficher</p>
                    <p className="mt-1 text-xs text-zinc-500">Aucune offre ne correspond à ce filtre.</p>
                </div>
            ) : (
                <div className="space-y-3">
                    {offers.data.map((offer) => (
                        <div
                            key={offer.id}
                            className="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-shadow hover:shadow-sm"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <Link
                                        href={route('admin.marketplace.offers.show', offer.id)}
                                        className="font-serif text-base font-semibold text-zinc-900 hover:text-accent"
                                    >
                                        {offer.title}
                                    </Link>
                                    <p className="mt-1 text-xs text-zinc-500">
                                        {[
                                            offer.vendor?.display_name,
                                            offer.category?.name,
                                            offer.type ? offer.type.charAt(0).toUpperCase() + offer.type.slice(1) : null,
                                            `${formatPrice(offer.sale_price || offer.price)} ${offer.currency || 'XOF'}`,
                                            offer.submitted_at
                                                ? `soumis le ${new Date(offer.submitted_at).toLocaleDateString('fr-FR')}`
                                                : null,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                </div>
                                <div>{statusPill(offer.status)}</div>
                            </div>

                            {/* ACTIONS EN ATTENTE */}
                            {offer.status === 'pending_review' && (
                                <div className="mt-4 flex flex-wrap items-center gap-2.5 border-t border-zinc-100 pt-3.5">
                                    <button
                                        type="button"
                                        onClick={() => handleApprove(offer.id)}
                                        className="rounded-xl border border-emerald-300 bg-emerald-50 px-3.5 py-1.5 text-xs font-bold text-emerald-800 shadow-2xs hover:bg-emerald-100 transition-colors"
                                    >
                                        Approuver
                                    </button>

                                    <form
                                        onSubmit={(e) => handleRequestChanges(e, offer.id)}
                                        className="flex items-center gap-1.5"
                                    >
                                        <input
                                            type="text"
                                            required
                                            placeholder="Corrections demandées"
                                            value={changeNotes[offer.id] || ''}
                                            onChange={(e) =>
                                                setChangeNotes({ ...changeNotes, [offer.id]: e.target.value })
                                            }
                                            className="h-8 w-48 rounded-xl border border-zinc-300 bg-white px-3 text-xs text-zinc-900 shadow-2xs placeholder:text-zinc-400 focus:border-accent focus:outline-none"
                                        />
                                        <button
                                            type="submit"
                                            className="rounded-xl border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 shadow-2xs hover:bg-amber-100 transition-colors"
                                        >
                                            Demander
                                        </button>
                                    </form>

                                    <form
                                        onSubmit={(e) => handleReject(e, offer.id)}
                                        className="flex items-center gap-1.5"
                                    >
                                        <input
                                            type="text"
                                            required
                                            placeholder="Motif de refus"
                                            value={rejectReasons[offer.id] || ''}
                                            onChange={(e) =>
                                                setRejectReasons({ ...rejectReasons, [offer.id]: e.target.value })
                                            }
                                            className="h-8 w-44 rounded-xl border border-zinc-300 bg-white px-3 text-xs text-zinc-900 shadow-2xs placeholder:text-zinc-400 focus:border-accent focus:outline-none"
                                        />
                                        <button
                                            type="submit"
                                            className="rounded-xl border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-800 shadow-2xs hover:bg-rose-100 transition-colors"
                                        >
                                            Refuser
                                        </button>
                                    </form>

                                    <Link
                                        href={route('admin.marketplace.offers.show', offer.id)}
                                        className="rounded-xl border border-zinc-300 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 transition-colors"
                                    >
                                        Détail
                                    </Link>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {/* PAGINATION */}
            {offers.links && offers.links.length > 3 && (
                <div className="mt-6 flex flex-wrap justify-center gap-1.5">
                    {offers.links.map((link, idx) => (
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
