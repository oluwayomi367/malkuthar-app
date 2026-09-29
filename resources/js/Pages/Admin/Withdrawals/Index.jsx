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
                                'rounded-full px-4 py-1.5 text-xs font-semibold shadow-2xs transition-colors',
                                active
                                    ? 'bg-zinc-900 text-white'
                                    : 'border border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50',
                            ].join(' ')}
                        >
                            {tab.label}
                        </Link>
                    );
                })}
            </div>

            {/* TABLEAU DES RETRAITS */}
            <div className="overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-xs">
                <table className="w-full text-left text-xs">
                    <thead className="border-b border-zinc-200 bg-zinc-50 text-[10px] font-bold uppercase tracking-wider text-zinc-500">
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
                    <tbody className="divide-y divide-zinc-100">
                        {withdrawals.data && withdrawals.data.length > 0 ? (
                            withdrawals.data.map((w) => (
                                <tr key={w.id} className="hover:bg-zinc-50/60">
                                    <td className="px-4 py-3 font-mono font-semibold text-zinc-900">
                                        {w.reference}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="font-medium text-zinc-900">
                                            {w.user ? `${w.user.first_name} ${w.user.last_name}` : '—'}
                                        </div>
                                        <div className="font-mono text-[11px] text-zinc-400">
                                            {w.user?.affiliate_id}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 font-bold text-zinc-900">
                                        {formatNumber(w.amount)} {w.currency || 'FCFA'}
                                    </td>
                                    <td className="px-4 py-3 text-zinc-600">{w.method || '—'}</td>
                                    <td className="px-4 py-3 text-zinc-500">
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
                                            <span className="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-semibold text-amber-800">
                                                À traiter
                                            </span>
                                        )}
                                        {w.status === 'paid' && (
                                            <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-800">
                                                Payé
                                            </span>
                                        )}
                                        {w.status === 'failed' && (
                                            <span className="rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-semibold text-rose-800">
                                                Échoué
                                            </span>
                                        )}
                                        {w.status === 'cancelled' && (
                                            <span className="rounded-full bg-zinc-100 px-2.5 py-0.5 text-[11px] font-semibold text-zinc-600">
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
                                                    className="rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-2xs hover:bg-emerald-700"
                                                >
                                                    Marquer payé
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => setSelectedFailed(w)}
                                                    className="rounded-lg border border-rose-300 bg-white px-2.5 py-1 text-xs font-semibold text-rose-700 shadow-2xs hover:bg-rose-50"
                                                >
                                                    Échouer
                                                </button>
                                            </div>
                                        )}
                                        {w.provider_reference && (
                                            <span className="font-mono text-[11px] text-zinc-500">
                                                Réf : {w.provider_reference}
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan="7" className="px-4 py-8 text-center text-zinc-500">
                                    Aucune demande de retrait trouvée.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* MODAL MARQUER PAYÉ */}
            {selectedPaid && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-xs">
                    <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-zinc-200">
                        <h3 className="font-display text-base font-bold text-zinc-900">
                            Confirmer le paiement
                        </h3>
                        <p className="mt-1 text-xs text-zinc-600">
                            Retrait {selectedPaid.reference} de {formatNumber(selectedPaid.amount)} {selectedPaid.currency} pour {selectedPaid.user?.first_name} {selectedPaid.user?.last_name}.
                        </p>
                        <form onSubmit={submitPaid} className="mt-4 space-y-3">
                            <div>
                                <label className="text-xs font-medium text-zinc-700">
                                    Référence de transaction PSP (optionnel)
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ex. FED-TRX-839201"
                                    value={paidForm.data.provider_reference}
                                    onChange={(e) => paidForm.setData('provider_reference', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-xs text-zinc-900 shadow-2xs focus:border-accent focus:outline-none"
                                />
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setSelectedPaid(null)}
                                    className="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    disabled={paidForm.processing}
                                    className="rounded-lg bg-emerald-600 px-4 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-emerald-700"
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
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-xs">
                    <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-zinc-200">
                        <h3 className="font-display text-base font-bold text-rose-900">
                            Marquer comme échoué
                        </h3>
                        <p className="mt-1 text-xs text-zinc-600">
                            Les fonds seront automatiquement recrédités sur le solde de l'affilié.
                        </p>
                        <form onSubmit={submitFailed} className="mt-4 space-y-3">
                            <div>
                                <label className="text-xs font-medium text-zinc-700">Motif du rejet</label>
                                <textarea
                                    required
                                    rows="3"
                                    placeholder="Ex. Compte destinataire invalide / Rejet opérateur Mobile Money"
                                    value={failedForm.data.reason}
                                    onChange={(e) => failedForm.setData('reason', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-zinc-300 p-2 text-xs text-zinc-900 shadow-2xs focus:border-rose-500 focus:outline-none"
                                />
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setSelectedFailed(null)}
                                    className="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50"
                                >
                                    Fermer
                                </button>
                                <button
                                    type="submit"
                                    disabled={failedForm.processing}
                                    className="rounded-lg bg-rose-600 px-4 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-rose-700"
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
