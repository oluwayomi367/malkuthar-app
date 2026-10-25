import React, { useState } from 'react';
import { Link, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Show({ vendor = {} }) {
    const [rejectReason, setRejectReason] = useState('');
    const [suspendReason, setSuspendReason] = useState('');

    const commissionForm = useForm({
        commission_rate_percent:
            vendor.commission_rate_override !== null && vendor.commission_rate_override !== undefined
                ? String(parseFloat((Number(vendor.commission_rate_override) * 100).toFixed(2)))
                : '',
    });

    const handleCommissionSubmit = (e) => {
        e.preventDefault();
        commissionForm.put(route('admin.marketplace.vendors.commission', vendor.id));
    };

    const handleApprove = () => {
        if (confirm(`Approuver le compte « ${vendor.display_name} » ?`)) {
            router.post(route('admin.marketplace.vendors.approve', vendor.id));
        }
    };

    const handleReject = (e) => {
        e.preventDefault();
        if (!rejectReason || rejectReason.trim().length < 5) {
            alert('Veuillez indiquer un motif de refus d’au moins 5 caractères.');
            return;
        }
        router.post(route('admin.marketplace.vendors.reject', vendor.id), { reason: rejectReason });
    };

    const handleSuspend = (e) => {
        e.preventDefault();
        if (!suspendReason || suspendReason.trim().length < 5) {
            alert('Veuillez indiquer un motif de suspension d’au moins 5 caractères.');
            return;
        }
        router.post(route('admin.marketplace.vendors.suspend', vendor.id), { reason: suspendReason });
    };

    const handleReactivate = () => {
        if (confirm(`Réactiver le compte « ${vendor.display_name} » ?`)) {
            router.post(route('admin.marketplace.vendors.reactivate', vendor.id));
        }
    };

    const statusPill = (st) => {
        switch (st) {
            case 'active':
                return (
                    <span className="rounded-full bg-sage-soft px-3 py-1 text-xs font-semibold text-sage">
                        Actif
                    </span>
                );
            case 'pending':
                return (
                    <span className="rounded-full bg-amber/25 px-3 py-1 text-xs font-semibold text-amber">
                        En attente
                    </span>
                );
            case 'suspended':
                return (
                    <span className="rounded-full bg-rust-tint px-3 py-1 text-xs font-semibold text-rust">
                        Suspendu
                    </span>
                );
            case 'closed':
                return (
                    <span className="rounded-full bg-night-line px-3 py-1 text-xs font-semibold text-night-ink-2">
                        Fermé
                    </span>
                );
            default:
                return (
                    <span className="rounded-full bg-night-line px-3 py-1 text-xs font-semibold text-night-ink-2">
                        {st}
                    </span>
                );
        }
    };

    return (
        <AdminLayout
            title={vendor.display_name || 'Vendeur'}
            eyebrow="Marketplace · Vendeur"
            heading={vendor.display_name}
        >
            <div className="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
                {/* COLONNE GAUCHE : PROFIL & KYC */}
                <div className="space-y-6">
                    {/* PROFIL */}
                    <section className="rounded-2xl border border-night-line bg-night-2 p-6 ">
                        <div className="flex items-center justify-between pb-4 border-b border-night-line">
                            <h2 className="font-sans text-base font-semibold text-night-ink">Profil</h2>
                            {statusPill(vendor.status)}
                        </div>

                        <dl className="mt-4 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-night-ink-2 font-medium">Compte utilisateur</dt>
                                <dd className="mt-0.5 text-night-ink font-semibold">
                                    {vendor.user
                                        ? `${vendor.user.first_name || ''} ${vendor.user.last_name || ''}`.trim()
                                        : '—'}{' '}
                                    — {vendor.user?.email || '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-night-ink-2 font-medium">Raison sociale</dt>
                                <dd className="mt-0.5 text-night-ink font-semibold">{vendor.legal_name || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-night-ink-2 font-medium">Région / Ville</dt>
                                <dd className="mt-0.5 text-night-ink font-semibold">
                                    {vendor.region || '—'}
                                    {vendor.city ? ` · ${vendor.city}` : ''}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-night-ink-2 font-medium">Contact direct</dt>
                                <dd className="mt-0.5 text-night-ink font-semibold">
                                    {vendor.contact_email || '—'} {vendor.phone ? ` · ${vendor.phone}` : ''}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-night-ink-2 font-medium">Devise de versement</dt>
                                <dd className="mt-0.5 text-night-ink font-semibold">
                                    {vendor.payout_currency || 'XOF'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-night-ink-2 font-medium">Validé par</dt>
                                <dd className="mt-0.5 text-night-ink font-semibold">
                                    {vendor.approver
                                        ? `${vendor.approver.first_name || ''} ${vendor.approver.last_name || ''}`.trim()
                                        : '—'}
                                    {vendor.approved_at
                                        ? ` le ${new Date(vendor.approved_at).toLocaleDateString('fr-FR')}`
                                        : ''}
                                </dd>
                            </div>
                        </dl>

                        {vendor.bio && (
                            <div className="mt-5 border-t border-night-line pt-4 text-sm leading-relaxed text-night-ink-2">
                                <span className="font-semibold text-night-ink block mb-1">Bio / Présentation :</span>
                                {vendor.bio}
                            </div>
                        )}

                        {vendor.suspension_reason && (
                            <div className="mt-4 rounded-xl border border-rust bg-rust-tint p-3 text-sm text-rust">
                                <strong>Motif de suspension :</strong> {vendor.suspension_reason}
                            </div>
                        )}
                    </section>

                    {/* JUSTIFICATIFS KYC */}
                    <section className="rounded-2xl border border-night-line bg-night-2 p-6 ">
                        <h2 className="font-sans text-base font-semibold text-night-ink">Justificatifs (KYC)</h2>
                        {!vendor.documents || vendor.documents.length === 0 ? (
                            <p className="mt-3 text-xs text-night-ink-2">Aucune pièce déposée pour ce vendeur.</p>
                        ) : (
                            <ul className="mt-4 divide-y divide-night-line text-sm">
                                {vendor.documents.map((doc) => (
                                    <li key={doc.id} className="flex items-center justify-between py-2.5">
                                        <span className="font-medium text-night-ink">
                                            {doc.type} — {doc.original_name || 'pièce'}
                                        </span>
                                        <span className="rounded-full bg-night-line px-2 py-0.5 text-xs font-semibold text-night-ink-2">
                                            {doc.status}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>

                {/* COLONNE DROITE : ACTIONS & COMMISSION */}
                <div className="space-y-6">
                    {/* STATUT ET ACTIONS */}
                    <section className="rounded-2xl border border-night-line bg-night-2 p-6 ">
                        <h2 className="font-sans text-base font-semibold text-night-ink">Gestion du compte</h2>
                        <div className="mt-4 space-y-3">
                            {vendor.status === 'pending' && (
                                <>
                                    <button
                                        type="button"
                                        onClick={handleApprove}
                                        className="h-10 w-full rounded-xl border border-sage bg-sage-soft text-xs font-bold text-sage hover:bg-sage-soft transition-colors"
                                    >
                                        Approuver le compte
                                    </button>
                                    <form onSubmit={handleReject} className="space-y-2 pt-2">
                                        <input
                                            type="text"
                                            required
                                            placeholder="Motif de refus"
                                            value={rejectReason}
                                            onChange={(e) => setRejectReason(e.target.value)}
                                            className="h-11 w-full rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink placeholder:text-night-ink-2/70 focus:border-accent focus:outline-none min-h-11"
                                        />
                                        <button
                                            type="submit"
                                            className="h-10 w-full rounded-xl border border-rust bg-rust-tint text-xs font-bold text-rust hover:bg-rust/35 transition-colors"
                                        >
                                            Refuser la candidature
                                        </button>
                                    </form>
                                </>
                            )}

                            {vendor.status === 'active' && (
                                <form onSubmit={handleSuspend} className="space-y-2">
                                    <input
                                        type="text"
                                        required
                                        placeholder="Motif de suspension"
                                        value={suspendReason}
                                        onChange={(e) => setSuspendReason(e.target.value)}
                                        className="h-11 w-full rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink placeholder:text-night-ink-2/70 focus:border-accent focus:outline-none min-h-11"
                                    />
                                    <button
                                        type="submit"
                                        className="h-10 w-full rounded-xl border border-rust bg-rust-tint text-xs font-bold text-rust hover:bg-rust/35 transition-colors"
                                    >
                                        Suspendre le compte
                                    </button>
                                </form>
                            )}

                            {(vendor.status === 'suspended' || vendor.status === 'closed') && (
                                <button
                                    type="button"
                                    onClick={handleReactivate}
                                    className="h-10 w-full rounded-xl border border-sage bg-sage-soft text-xs font-bold text-sage hover:bg-sage-soft transition-colors"
                                >
                                    Réactiver le compte
                                </button>
                            )}
                        </div>
                    </section>

                    {/* COMMISSION SPECIFIQUE */}
                    <section className="rounded-2xl border border-night-line bg-night-2 p-6 ">
                        <h2 className="font-sans text-base font-semibold text-night-ink">Commission</h2>
                        <p className="mt-1 text-xs text-night-ink-2 leading-relaxed">
                            Taux négocié spécifique au vendeur. Laisser vide pour utiliser le barème standard de la
                            catégorie ou plateforme.
                        </p>
                        <form onSubmit={handleCommissionSubmit} className="mt-4 flex items-center gap-2">
                            <div className="relative flex-1">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="90"
                                    placeholder="Standard"
                                    value={commissionForm.data.commission_rate_percent}
                                    onChange={(e) =>
                                        commissionForm.setData('commission_rate_percent', e.target.value)
                                    }
                                    className="h-11 w-full rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink focus:border-accent focus:outline-none min-h-11"
                                />
                                <span className="absolute right-3 top-2.5 text-xs text-night-ink-2 font-semibold">%</span>
                            </div>
                            <button
                                type="submit"
                                disabled={commissionForm.processing}
                                className="h-10 rounded-full bg-accent px-4 text-sm font-bold text-white hover:bg-accent-2 transition-colors disabled:opacity-50 min-h-11"
                            >
                                Enregistrer
                            </button>
                        </form>
                    </section>
                </div>
            </div>

            <div className="mt-8">
                <Link
                    href={route('admin.marketplace.vendors.index')}
                    className="text-xs font-semibold text-accent-2 hover:underline"
                >
                    Tous les vendeurs
                </Link>
            </div>
        </AdminLayout>
    );
}
