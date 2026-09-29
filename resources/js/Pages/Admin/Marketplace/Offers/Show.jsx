import React, { useState } from 'react';
import { Link, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Show({ offer = {}, pendingRevision = null, liveRevision = null }) {
    const candidate = pendingRevision?.payload;
    const c = candidate || {
        title: offer.title,
        summary: offer.summary,
        description: offer.description,
        price: offer.price,
        sale_price: offer.sale_price,
        custom_fields: offer.custom_fields,
        detail: offer.detail || {},
    };

    const [approveNote, setApproveNote] = useState('');
    const [changesNote, setChangesNote] = useState('');
    const [rejectReason, setRejectReason] = useState('');
    const [suspendReason, setSuspendReason] = useState('');

    const commissionForm = useForm({
        commission_rate_percent:
            offer.commission_rate !== null && offer.commission_rate !== undefined
                ? String(parseFloat((Number(offer.commission_rate) * 100).toFixed(2)))
                : '',
    });

    const handleCommissionSubmit = (e) => {
        e.preventDefault();
        commissionForm.put(route('admin.marketplace.offers.commission', offer.id));
    };

    const handleApprove = (e) => {
        e.preventDefault();
        router.post(route('admin.marketplace.offers.approve', offer.id), {
            note: approveNote || undefined,
        });
    };

    const handleRequestChanges = (e) => {
        e.preventDefault();
        if (!changesNote || changesNote.trim().length < 5) {
            alert('Veuillez indiquer les corrections à apporter (au moins 5 caractères).');
            return;
        }
        router.post(route('admin.marketplace.offers.request-changes', offer.id), {
            note: changesNote,
        });
    };

    const handleReject = (e) => {
        e.preventDefault();
        if (!rejectReason || rejectReason.trim().length < 5) {
            alert('Veuillez indiquer un motif de refus (au moins 5 caractères).');
            return;
        }
        router.post(route('admin.marketplace.offers.reject', offer.id), {
            reason: rejectReason,
        });
    };

    const handleSuspend = (e) => {
        e.preventDefault();
        if (!suspendReason || suspendReason.trim().length < 5) {
            alert('Veuillez indiquer un motif de suspension (au moins 5 caractères).');
            return;
        }
        router.post(route('admin.marketplace.offers.suspend', offer.id), {
            reason: suspendReason,
        });
    };

    const handleUnsuspend = () => {
        if (confirm('Rétablir cette offre en ligne ?')) {
            router.post(route('admin.marketplace.offers.unsuspend', offer.id));
        }
    };

    const formatPrice = (val) => {
        if (val === null || val === undefined) return '0';
        return Number(val).toLocaleString('fr-FR');
    };

    const statusPill = (st) => {
        switch (st) {
            case 'approved':
                return (
                    <span className="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                        Approuvée
                    </span>
                );
            case 'pending_review':
                return (
                    <span className="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                        À valider
                    </span>
                );
            case 'changes_requested':
                return (
                    <span className="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-800">
                        À corriger
                    </span>
                );
            case 'rejected':
                return (
                    <span className="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-800">
                        Refusée
                    </span>
                );
            case 'suspended':
                return (
                    <span className="rounded-full bg-zinc-200 px-3 py-1 text-xs font-semibold text-zinc-700">
                        Suspendue
                    </span>
                );
            default:
                return (
                    <span className="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600">
                        {st}
                    </span>
                );
        }
    };

    const decisionLabels = {
        approved: 'Approuvée',
        rejected: 'Refusée',
        changes_requested: 'Corrections',
    };

    return (
        <AdminLayout
            title={offer.title || 'Offre'}
            eyebrow="Marketplace · Modération"
            heading={offer.title}
        >
            <div className="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
                {/* COLONNE GAUCHE : CONTENU ET HISTORIQUE */}
                <div className="space-y-6">
                    {/* CONTENU */}
                    <section className="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs">
                        <div className="flex items-center justify-between pb-4 border-b border-zinc-100">
                            <h2 className="font-serif text-base font-semibold text-zinc-900">
                                Contenu {candidate ? 'soumis' : 'actuel'}
                            </h2>
                            {statusPill(offer.status)}
                        </div>

                        <dl className="mt-4 space-y-3 text-xs">
                            <div>
                                <dt className="text-zinc-500 font-medium">Titre</dt>
                                <dd className="mt-0.5 text-zinc-900 font-semibold text-sm">{c.title}</dd>
                            </div>
                            <div>
                                <dt className="text-zinc-500 font-medium">Accroche</dt>
                                <dd className="mt-0.5 text-zinc-800">{c.summary || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-zinc-500 font-medium">Prix</dt>
                                <dd className="mt-0.5 text-zinc-900 font-semibold text-sm">
                                    {formatPrice(c.price)} {offer.currency || 'XOF'}
                                    {c.sale_price && (
                                        <span className="ml-2 text-xs font-normal text-amber-700">
                                            (promo {formatPrice(c.sale_price)})
                                        </span>
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-zinc-500 font-medium">Vendeur</dt>
                                <dd className="mt-0.5 text-zinc-900 font-semibold">
                                    {offer.vendor?.display_name} — {offer.vendor?.user?.email || ''}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-zinc-500 font-medium">Catégorie · Type</dt>
                                <dd className="mt-0.5 text-zinc-900 font-semibold">
                                    {offer.category?.name} ·{' '}
                                    {offer.type ? offer.type.charAt(0).toUpperCase() + offer.type.slice(1) : ''}
                                </dd>
                            </div>
                        </dl>

                        <div className="mt-5 border-t border-zinc-100 pt-4">
                            <p className="text-xs font-medium text-zinc-500">Description</p>
                            <p className="mt-1 whitespace-pre-line text-xs leading-relaxed text-zinc-800">
                                {c.description}
                            </p>
                        </div>

                        {c.custom_fields && Object.keys(c.custom_fields).length > 0 && (
                            <div className="mt-5 border-t border-zinc-100 pt-4">
                                <p className="text-xs font-medium text-zinc-500 mb-2">Caractéristiques</p>
                                <ul className="space-y-1 text-xs text-zinc-800">
                                    {Object.entries(c.custom_fields).map(([k, v]) => (
                                        <li key={k}>
                                            <span className="font-semibold text-zinc-600">{k} :</span>{' '}
                                            {Array.isArray(v) ? v.join(', ') : String(v)}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {candidate && offer.first_published_at && (
                            <div className="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                                Une version est déjà en ligne. L’approbation remplacera son contenu par celui-ci.
                            </div>
                        )}
                    </section>

                    {/* HISTORIQUE DE MODERATION */}
                    <section className="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs">
                        <h2 className="font-serif text-base font-semibold text-zinc-900">
                            Historique de modération
                        </h2>
                        {!offer.reviews || offer.reviews.length === 0 ? (
                            <p className="mt-3 text-xs text-zinc-500">Aucune décision encore.</p>
                        ) : (
                            <ul className="mt-4 space-y-3 text-xs">
                                {offer.reviews.map((r) => (
                                    <li
                                        key={r.id}
                                        className="flex items-start justify-between gap-3 border-b border-zinc-100 pb-2.5 last:border-b-0"
                                    >
                                        <div>
                                            <span className="font-semibold text-zinc-900">
                                                {decisionLabels[r.decision] || r.decision}
                                            </span>
                                            {r.note && <span className="ml-1 text-zinc-600"> — {r.note}</span>}
                                        </div>
                                        <span className="shrink-0 text-[11px] font-medium text-zinc-400">
                                            v{r.offer_version} ·{' '}
                                            {r.created_at ? new Date(r.created_at).toLocaleDateString('fr-FR') : ''}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>

                {/* COLONNE DROITE : DECISION & COMMISSION */}
                <div className="space-y-6">
                    {/* DECISION SECTION */}
                    <section className="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs">
                        <h2 className="font-serif text-base font-semibold text-zinc-900">Décision</h2>

                        {offer.status === 'pending_review' && (
                            <div className="mt-4 space-y-4">
                                <form onSubmit={handleApprove} className="space-y-2">
                                    <input
                                        type="text"
                                        placeholder="Note (facultatif)"
                                        value={approveNote}
                                        onChange={(e) => setApproveNote(e.target.value)}
                                        className="h-10 w-full rounded-xl border border-zinc-300 bg-white px-3 text-xs text-zinc-900 shadow-2xs placeholder:text-zinc-400 focus:border-accent focus:outline-none"
                                    />
                                    <button
                                        type="submit"
                                        className="h-10 w-full rounded-xl border border-emerald-300 bg-emerald-50 text-xs font-bold text-emerald-800 shadow-2xs hover:bg-emerald-100 transition-colors"
                                    >
                                        Approuver
                                    </button>
                                </form>

                                <form onSubmit={handleRequestChanges} className="space-y-2 pt-2 border-t border-zinc-100">
                                    <input
                                        type="text"
                                        required
                                        placeholder="Corrections à apporter"
                                        value={changesNote}
                                        onChange={(e) => setChangesNote(e.target.value)}
                                        className="h-10 w-full rounded-xl border border-zinc-300 bg-white px-3 text-xs text-zinc-900 shadow-2xs placeholder:text-zinc-400 focus:border-accent focus:outline-none"
                                    />
                                    <button
                                        type="submit"
                                        className="h-10 w-full rounded-xl border border-amber-300 bg-amber-50 text-xs font-bold text-amber-800 shadow-2xs hover:bg-amber-100 transition-colors"
                                    >
                                        Demander des corrections
                                    </button>
                                </form>

                                <form onSubmit={handleReject} className="space-y-2 pt-2 border-t border-zinc-100">
                                    <input
                                        type="text"
                                        required
                                        placeholder="Motif de refus"
                                        value={rejectReason}
                                        onChange={(e) => setRejectReason(e.target.value)}
                                        className="h-10 w-full rounded-xl border border-zinc-300 bg-white px-3 text-xs text-zinc-900 shadow-2xs placeholder:text-zinc-400 focus:border-accent focus:outline-none"
                                    />
                                    <button
                                        type="submit"
                                        className="h-10 w-full rounded-xl border border-rose-300 bg-rose-50 text-xs font-bold text-rose-800 shadow-2xs hover:bg-rose-100 transition-colors"
                                    >
                                        Refuser
                                    </button>
                                </form>
                            </div>
                        )}

                        {offer.status === 'approved' && (
                            <form onSubmit={handleSuspend} className="mt-4 space-y-2">
                                <input
                                    type="text"
                                    required
                                    placeholder="Motif de suspension"
                                    value={suspendReason}
                                    onChange={(e) => setSuspendReason(e.target.value)}
                                    className="h-10 w-full rounded-xl border border-zinc-300 bg-white px-3 text-xs text-zinc-900 shadow-2xs placeholder:text-zinc-400 focus:border-accent focus:outline-none"
                                />
                                <button
                                    type="submit"
                                    className="h-10 w-full rounded-xl border border-rose-300 bg-rose-50 text-xs font-bold text-rose-800 shadow-2xs hover:bg-rose-100 transition-colors"
                                >
                                    Suspendre l’offre
                                </button>
                            </form>
                        )}

                        {offer.status === 'suspended' && (
                            <div className="mt-4">
                                <button
                                    type="button"
                                    onClick={handleUnsuspend}
                                    className="h-10 w-full rounded-xl border border-emerald-300 bg-emerald-50 text-xs font-bold text-emerald-800 shadow-2xs hover:bg-emerald-100 transition-colors"
                                >
                                    Rétablir l’offre
                                </button>
                            </div>
                        )}

                        {offer.status === 'rejected' && (
                            <p className="mt-3 text-xs text-zinc-500">
                                Cette offre a été refusée et ne peut pas être modifiée par l’administrateur directement.
                            </p>
                        )}
                    </section>

                    {/* COMMISSION PRODUIT */}
                    <section className="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs">
                        <div className="flex items-center justify-between">
                            <h2 className="font-serif text-base font-semibold text-zinc-900">Commission produit</h2>
                            {offer.commission_rate !== null && offer.commission_rate !== undefined ? (
                                <span className="rounded-full bg-accent/10 px-2.5 py-0.5 text-xs font-semibold text-accent">
                                    {parseFloat((Number(offer.commission_rate) * 100).toFixed(2))} % (spécifique)
                                </span>
                            ) : (
                                <span className="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs text-zinc-600">
                                    Barème standard
                                </span>
                            )}
                        </div>
                        <p className="mt-2 text-xs text-zinc-500 leading-relaxed">
                            Pourcentage perçu par la plateforme sur ce produit spécifique. Prioritaire sur les règles
                            vendeur et catégorie. Laissez vide pour appliquer le barème standard.
                        </p>
                        <form onSubmit={handleCommissionSubmit} className="mt-4 flex items-center gap-2">
                            <div className="relative flex-1">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="90"
                                    placeholder="Ex : 15"
                                    value={commissionForm.data.commission_rate_percent}
                                    onChange={(e) =>
                                        commissionForm.setData('commission_rate_percent', e.target.value)
                                    }
                                    className="h-10 w-full rounded-xl border border-zinc-300 bg-white px-3 text-xs text-zinc-900 shadow-2xs focus:border-accent focus:outline-none"
                                />
                                <span className="absolute right-3 top-2.5 text-xs font-semibold text-zinc-400">%</span>
                            </div>
                            <button
                                type="submit"
                                disabled={commissionForm.processing}
                                className="h-10 rounded-xl bg-accent px-4 text-xs font-bold text-white shadow-2xs hover:bg-accent-2 transition-colors disabled:opacity-50"
                            >
                                Enregistrer
                            </button>
                        </form>
                        {offer.commission_rate !== null && offer.commission_rate !== undefined && (
                            <p className="mt-3 text-[11px] font-medium text-accent">
                                ✓ Surcharge active : {parseFloat((Number(offer.commission_rate) * 100).toFixed(2))} % prélevés sur chaque vente de cette offre.
                            </p>
                        )}
                    </section>
                </div>
            </div>

            <div className="mt-8">
                <Link
                    href={route('admin.marketplace.offers.index')}
                    className="text-xs font-semibold text-accent hover:underline"
                >
                    ← File de modération
                </Link>
            </div>
        </AdminLayout>
    );
}
