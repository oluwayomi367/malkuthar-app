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
                    <span className="rounded-full bg-sage/30 px-2.5 py-0.5 text-xs font-semibold text-sage-soft">
                        Approuvée
                    </span>
                );
            case 'pending_review':
                return (
                    <span className="rounded-full bg-amber/25 px-2.5 py-0.5 text-xs font-semibold text-amber-tint">
                        À valider
                    </span>
                );
            case 'changes_requested':
                return (
                    <span className="rounded-full bg-amber/25 px-2.5 py-0.5 text-xs font-semibold text-amber-tint">
                        À corriger
                    </span>
                );
            case 'rejected':
                return (
                    <span className="rounded-full bg-rust/20 px-2.5 py-0.5 text-xs font-semibold text-rust-tint">
                        Refusée
                    </span>
                );
            case 'suspended':
                return (
                    <span className="rounded-full bg-night-line px-2.5 py-0.5 text-xs font-semibold text-night-ink-2">
                        Suspendue
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
            <nav className="mb-6 flex flex-wrap gap-2 text-sm font-medium">
                {tabs.map((tab) => {
                    const isActive = status === tab.key;
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
                            {tab.key !== 'all' && counts[tab.key] !== undefined && (
                                <span className={`ml-1.5 font-bold ${isActive ? 'text-night' : 'text-night-ink-2'}`}>
                                    ({counts[tab.key]})
                                </span>
                            )}
                        </button>
                    );
                })}
            </nav>

            {/* LISTE DES OFFRES */}
            {(!offers.data || offers.data.length === 0) ? (
                <div className="rounded-2xl border border-dashed border-night-ink-2/60 bg-night-2 p-12 text-center">
                    <p className="font-sans text-base font-semibold text-night-ink">Rien à afficher</p>
                    <p className="mt-1 text-xs text-night-ink-2">Aucune offre ne correspond à ce filtre.</p>
                </div>
            ) : (
                <div className="space-y-3">
                    {offers.data.map((offer) => (
                        <div
                            key={offer.id}
                            className="rounded-2xl border border-night-line bg-night-2 p-5 transition-shadow"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <Link
                                        href={route('admin.marketplace.offers.show', offer.id)}
                                        className="text-base font-semibold text-night-ink hover:text-accent-light"
                                    >
                                        {offer.title}
                                    </Link>
                                    <p className="mt-1 text-xs text-night-ink-2">
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
                                <div className="mt-4 flex flex-wrap items-center gap-2.5 border-t border-night-line pt-3.5">
                                    <button
                                        type="button"
                                        onClick={() => handleApprove(offer.id)}
                                        className="rounded-full border border-sage bg-sage/30 px-3.5 py-1.5 text-sm font-bold text-sage-soft hover:bg-sage/45 transition-colors min-h-11"
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
                                            className="h-11 w-48 rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink placeholder:text-night-ink-2/70 focus:border-accent-light focus:outline-none"
                                        />
                                        <button
                                            type="submit"
                                            className="rounded-full border border-amber bg-amber/25 px-3 py-1.5 text-sm font-bold text-amber-tint hover:bg-amber/40 transition-colors min-h-11"
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
                                            className="h-11 w-44 rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink placeholder:text-night-ink-2/70 focus:border-accent-light focus:outline-none"
                                        />
                                        <button
                                            type="submit"
                                            className="rounded-full border border-rust bg-rust/20 px-3 py-1.5 text-sm font-bold text-rust-tint hover:bg-rust/35 transition-colors min-h-11"
                                        >
                                            Refuser
                                        </button>
                                    </form>

                                    <Link
                                        href={route('admin.marketplace.offers.show', offer.id)}
                                        className="rounded-full border border-night-ink-2/60 bg-night px-3 py-1.5 text-sm font-semibold text-night-ink-2 hover:bg-night-line transition-colors min-h-11 inline-flex items-center justify-center"
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
