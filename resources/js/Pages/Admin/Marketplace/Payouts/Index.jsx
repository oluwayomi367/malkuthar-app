import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({
    payouts = { data: [] },
    status = 'requested',
    summary = { commission_earned: 0, vendor_pending: 0, vendor_paid: 0 },
}) {
    const [providerRefs, setProviderRefs] = useState({});
    const [failReasons, setFailReasons] = useState({});

    const handleFilter = (newStatus) => {
        router.get(
            route('admin.marketplace.payouts.index'),
            { status: newStatus },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleApprove = (payoutId) => {
        if (confirm('Approuver cette demande de versement ?')) {
            router.post(route('admin.marketplace.payouts.approve', payoutId));
        }
    };

    const handleMarkPaid = (e, payoutId) => {
        e.preventDefault();
        router.post(route('admin.marketplace.payouts.mark-paid', payoutId), {
            provider_reference: providerRefs[payoutId] || undefined,
        });
    };

    const handleMarkFailed = (e, payoutId) => {
        e.preventDefault();
        const reason = failReasons[payoutId];
        if (!reason || reason.trim().length < 3) {
            alert('Veuillez indiquer un motif d’échec (au moins 3 caractères).');
            return;
        }
        router.post(route('admin.marketplace.payouts.mark-failed', payoutId), { reason });
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
                        Payé
                    </span>
                );
            case 'approved':
                return (
                    <span className="rounded-full bg-night-line px-2.5 py-0.5 text-xs font-semibold text-night-ink">
                        Approuvé
                    </span>
                );
            case 'requested':
                return (
                    <span className="rounded-full bg-amber/25 px-2.5 py-0.5 text-xs font-semibold text-amber-tint">
                        À traiter
                    </span>
                );
            case 'failed':
                return (
                    <span className="rounded-full bg-rust/20 px-2.5 py-0.5 text-xs font-semibold text-rust-tint">
                        Échoué
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
        { key: 'requested', label: 'À traiter' },
        { key: 'approved', label: 'Approuvés' },
        { key: 'paid', label: 'Payés' },
        { key: 'failed', label: 'Échoués' },
        { key: 'all', label: 'Tous' },
    ];

    return (
        <AdminLayout
            title="Versements vendeurs"
            eyebrow="Marketplace"
            heading="Versements vendeurs"
        >
            {/* SYNTHESE STATS */}
            <div className="mb-6 grid gap-4 sm:grid-cols-3">
                <div className="rounded-2xl border border-night-line bg-night-2 p-5">
                    <p className="text-xs font-medium text-night-ink-2">Commission plateforme (payé)</p>
                    <p className="mt-2 text-2xl font-bold text-night-ink">
                        {formatPrice(summary.commission_earned)} <span className="text-sm font-normal text-night-ink-2">XOF</span>
                    </p>
                </div>
                <div className="rounded-2xl border border-night-line bg-night-2 p-5">
                    <p className="text-xs font-medium text-night-ink-2">Dû aux vendeurs (en validation)</p>
                    <p className="mt-2 text-2xl font-bold text-amber-tint">
                        {formatPrice(summary.vendor_pending)} <span className="text-sm font-normal text-night-ink-2">XOF</span>
                    </p>
                </div>
                <div className="rounded-2xl border border-night-line bg-night-2 p-5">
                    <p className="text-xs font-medium text-night-ink-2">Versé aux vendeurs</p>
                    <p className="mt-2 text-2xl font-bold text-sage-soft">
                        {formatPrice(summary.vendor_paid)} <span className="text-sm font-normal text-night-ink-2">XOF</span>
                    </p>
                </div>
            </div>

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
                        </button>
                    );
                })}
            </nav>

            {/* LISTE DES VERSEMENTS */}
            {(!payouts.data || payouts.data.length === 0) ? (
                <div className="rounded-2xl border border-dashed border-night-ink-2/60 bg-night-2 p-12 text-center">
                    <p className="font-sans text-base font-semibold text-night-ink">Aucun versement</p>
                    <p className="mt-1 text-xs text-night-ink-2">Aucune demande de versement pour ce filtre.</p>
                </div>
            ) : (
                <div className="space-y-3">
                    {payouts.data.map((p) => (
                        <div
                            key={p.id}
                            className="rounded-2xl border border-night-line bg-night-2 p-5 transition-shadow"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p className="font-mono text-xs font-bold text-night-ink">{p.reference}</p>
                                    <p className="mt-1 text-xs text-night-ink-2">
                                        {[
                                            p.vendor?.display_name,
                                            p.method ? p.method.toUpperCase() : null,
                                            p.account?.label,
                                            p.created_at
                                                ? new Date(p.created_at).toLocaleString('fr-FR', {
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
                                        {formatPrice(p.net_amount)} {p.currency || 'XOF'}
                                    </span>
                                    {statusPill(p.status)}
                                </div>
                            </div>

                            {/* ACTIONS SELON STATUT */}
                            {['requested', 'approved'].includes(p.status) && (
                                <div className="mt-4 flex flex-wrap items-center gap-2.5 border-t border-night-line pt-3.5">
                                    {p.status === 'requested' && (
                                        <button
                                            type="button"
                                            onClick={() => handleApprove(p.id)}
                                            className="rounded-full border border-night-ink-2/60 bg-night px-3.5 py-1.5 text-sm font-bold text-night-ink hover:bg-night-line transition-colors min-h-11"
                                        >
                                            Approuver
                                        </button>
                                    )}

                                    <form onSubmit={(e) => handleMarkPaid(e, p.id)} className="flex items-center gap-1.5">
                                        <input
                                            type="text"
                                            placeholder="Réf. prestataire (fac.)"
                                            value={providerRefs[p.id] || ''}
                                            onChange={(e) =>
                                                setProviderRefs({ ...providerRefs, [p.id]: e.target.value })
                                            }
                                            className="h-11 w-44 rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink placeholder:text-night-ink-2/70 focus:border-accent-light focus:outline-none"
                                        />
                                        <button
                                            type="submit"
                                            className="rounded-full border border-sage bg-sage/30 px-3 py-1.5 text-sm font-bold text-sage-soft hover:bg-sage/45 transition-colors min-h-11"
                                        >
                                            Marquer payé
                                        </button>
                                    </form>

                                    <form onSubmit={(e) => handleMarkFailed(e, p.id)} className="flex items-center gap-1.5">
                                        <input
                                            type="text"
                                            required
                                            placeholder="Motif d'échec"
                                            value={failReasons[p.id] || ''}
                                            onChange={(e) =>
                                                setFailReasons({ ...failReasons, [p.id]: e.target.value })
                                            }
                                            className="h-11 w-44 rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink placeholder:text-night-ink-2/70 focus:border-accent-light focus:outline-none"
                                        />
                                        <button
                                            type="submit"
                                            className="rounded-full border border-rust bg-rust/20 px-3 py-1.5 text-sm font-bold text-rust-tint hover:bg-rust/35 transition-colors min-h-11"
                                        >
                                            Échoué
                                        </button>
                                    </form>
                                </div>
                            )}

                            {p.failure_reason && (
                                <p className="mt-3 text-xs font-medium text-rust-tint">
                                    Échec : {p.failure_reason}
                                </p>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {/* PAGINATION */}
            {payouts.links && payouts.links.length > 3 && (
                <div className="mt-6 flex flex-wrap justify-center gap-1.5">
                    {payouts.links.map((link, idx) => (
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
