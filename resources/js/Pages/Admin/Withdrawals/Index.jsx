import React, { useState } from 'react';
import { Link, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({
    withdrawals = { data: [] },
    status = null,
    heading = 'Demandes de retrait',
    eyebrow = 'Finances',
}) {
    const [selectedPaid, setSelectedPaid] = useState(null);
    const [selectedFailed, setSelectedFailed] = useState(null);

    const paidForm = useForm({
        provider_reference: '',
    });

    const failedForm = useForm({
        reason: '',
    });

    const submitPaid = (e) => {
        e.preventDefault();
        paidForm.post(route('admin.withdrawals.mark-paid', selectedPaid.id), {
            onSuccess: () => {
                setSelectedPaid(null);
                paidForm.reset();
            },
        });
    };

    const submitFailed = (e) => {
        e.preventDefault();
        failedForm.post(route('admin.withdrawals.mark-failed', selectedFailed.id), {
            onSuccess: () => {
                setSelectedFailed(null);
                failedForm.reset();
            },
        });
    };

    const formatNumber = (num) => {
        if (num === null || num === undefined) return '0';
        return Number(num).toLocaleString('fr-FR');
    };

    const tabs = [
        { label: 'Tous', value: '' },
        { label: 'À traiter', value: 'processing' },
        { label: 'Payés', value: 'paid' },
        { label: 'Échoués', value: 'failed' },
    ];

    return (
        <AdminLayout title="Retraits" eyebrow={eyebrow} heading={heading}>
            {/* ONGLETS DE FILTRAGE */}
            <div className="mb-6 flex flex-wrap items-center gap-2">
                {tabs.map((tab) => {
                    const active = (status || '') === tab.value;
                    return (
                        <Link
                            key={tab.value}
                            href={route('admin.withdrawals.index', tab.value ? { status: tab.value } : {})}
                            preserveScroll
                            className={[
                                'rounded-full px-4 py-1.5 text-xs font-semibold transition-colors',
                                active
                                    ? 'bg-accent text-white'
                                    : 'border border-night-line bg-night-2 text-night-ink-2 hover:bg-night-line/50',
                            ].join(' ')}
                        >
                            {tab.label}
                        </Link>
                    );
                })}
            </div>

            {/* TABLEAU DES RETRAITS */}
            <div className="overflow-x-auto rounded-2xl border border-night-line bg-night-2">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-night-line text-xs font-semibold uppercase tracking-[0.08em] text-night-ink-2">
                        <tr>
                            <th className="px-4 py-3">Référence</th>
                            <th className="px-4 py-3">Affilié</th>
                            <th className="px-4 py-3">Montant</th>
                            <th className="px-4 py-3">Méthode</th>
                            <th className="px-4 py-3">Date</th>
                            <th className="px-4 py-3">Statut</th>
                            <th className="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-night-line">
                        {withdrawals.data && withdrawals.data.length > 0 ? (
                            withdrawals.data.map((w) => (
                                <tr key={w.id} className="hover:bg-night-line/50">
                                    <td className="px-4 py-3 font-mono font-semibold text-night-ink">
                                        {w.reference}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="font-medium text-night-ink">
                                            {w.user ? `${w.user.first_name} ${w.user.last_name}` : '—'}
                                        </div>
                                        <div className="font-mono text-sm text-night-ink-2">
                                            {w.user?.affiliate_id}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 font-bold text-night-ink">
                                        {formatNumber(w.amount)} {w.currency || 'FCFA'}
                                    </td>
                                    <td className="px-4 py-3 text-night-ink-2">{w.method || '—'}</td>
                                    <td className="px-4 py-3 text-night-ink-2">
                                        {w.requested_at
                                            ? new Date(w.requested_at).toLocaleDateString('fr-FR', {
                                                  day: '2-digit',
                                                  month: '2-digit',
                                                  year: 'numeric',
                                                  hour: '2-digit',
                                                  minute: '2-digit',
                                              })
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        {w.status === 'processing' && (
                                            <span className="rounded-full bg-amber/25 px-2.5 py-0.5 text-xs font-semibold text-amber">
                                                À traiter
                                            </span>
                                        )}
                                        {w.status === 'paid' && (
                                            <span className="rounded-full bg-sage-soft px-2.5 py-0.5 text-xs font-semibold text-sage">
                                                Payé
                                            </span>
                                        )}
                                        {w.status === 'failed' && (
                                            <span className="rounded-full bg-rust-tint px-2.5 py-0.5 text-xs font-semibold text-rust">
                                                Échoué
                                            </span>
                                        )}
                                        {w.status === 'cancelled' && (
                                            <span className="rounded-full bg-night-line px-2.5 py-0.5 text-xs font-semibold text-night-ink-2">
                                                Annulé
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {w.status === 'processing' && (
                                            <div className="flex items-center justify-end gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => setSelectedPaid(w)}
                                                    className="rounded-full bg-sage px-2.5 py-1 text-sm font-semibold text-white hover:bg-sage/85 min-h-11"
                                                >
                                                    Marquer payé
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => setSelectedFailed(w)}
                                                    className="rounded-full border border-rust bg-night-2 px-2.5 py-1 text-sm font-semibold text-rust hover:bg-rust-tint min-h-11"
                                                >
                                                    Échouer
                                                </button>
                                            </div>
                                        )}
                                        {w.provider_reference && (
                                            <span className="font-mono text-xs text-night-ink-2">
                                                Réf : {w.provider_reference}
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan="7" className="px-4 py-8 text-center text-night-ink-2">
                                    Aucune demande de retrait trouvée.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* MODAL MARQUER PAYÉ */}
            {selectedPaid && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-night/80 p-4">
                    <div className="w-full max-w-md rounded-2xl bg-night-2 p-6 border border-night-line">
                        <h3 className="font-sans text-base font-semibold text-night-ink">
                            Confirmer le paiement
                        </h3>
                        <p className="mt-1 text-xs text-night-ink-2">
                            Retrait {selectedPaid.reference} de {formatNumber(selectedPaid.amount)} {selectedPaid.currency} pour {selectedPaid.user?.first_name} {selectedPaid.user?.last_name}.
                        </p>
                        <form onSubmit={submitPaid} className="mt-4 space-y-3">
                            <div>
                                <label className="text-sm font-medium text-night-ink-2">
                                    Référence de transaction PSP (optionnel)
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ex. FED-TRX-839201"
                                    value={paidForm.data.provider_reference}
                                    onChange={(e) => paidForm.setData('provider_reference', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-3 py-2.5 text-sm text-night-ink focus:border-accent focus:outline-none min-h-11"
                                />
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setSelectedPaid(null)}
                                    className="rounded-full border border-night-ink-2/60 bg-night-2 px-3 py-1.5 text-sm font-medium text-night-ink-2 hover:bg-night-line/50 min-h-11"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    disabled={paidForm.processing}
                                    className="rounded-full bg-sage px-4 py-1.5 text-sm font-semibold text-white hover:bg-sage/85 min-h-11"
                                >
                                    Valider le paiement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* MODAL MARQUER ÉCHOUÉ */}
            {selectedFailed && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-night/80 p-4">
                    <div className="w-full max-w-md rounded-2xl bg-night-2 p-6 border border-night-line">
                        <h3 className="font-sans text-base font-semibold text-rust">
                            Marquer comme échoué
                        </h3>
                        <p className="mt-1 text-xs text-night-ink-2">
                            Les fonds seront automatiquement recrédités sur le solde de l'affilié.
                        </p>
                        <form onSubmit={submitFailed} className="mt-4 space-y-3">
                            <div>
                                <label className="text-sm font-medium text-night-ink-2">Motif du rejet</label>
                                <textarea
                                    required
                                    rows="3"
                                    placeholder="Ex. Compte destinataire invalide / Rejet opérateur Mobile Money"
                                    value={failedForm.data.reason}
                                    onChange={(e) => failedForm.setData('reason', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-night-ink-2/60 p-2 text-sm text-night-ink focus:border-rust focus:outline-none"
                                />
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setSelectedFailed(null)}
                                    className="rounded-full border border-night-ink-2/60 bg-night-2 px-3 py-1.5 text-sm font-medium text-night-ink-2 hover:bg-night-line/50 min-h-11"
                                >
                                    Fermer
                                </button>
                                <button
                                    type="submit"
                                    disabled={failedForm.processing}
                                    className="rounded-full bg-rust px-4 py-1.5 text-sm font-semibold text-white hover:bg-rust/85 min-h-11"
                                >
                                    Confirmer l'échec & recréditer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
