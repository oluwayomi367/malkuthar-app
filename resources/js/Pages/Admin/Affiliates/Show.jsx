import React, { useState } from 'react';
import { Link, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Show({
    affiliate,
    lineage = [],
    children = [],
    orders = [],
    commissions = [],
    withdrawals = [],
    totals = {},
    heading,
    eyebrow = 'Affilié',
}) {
    const formatNumber = (num) => {
        if (num === null || num === undefined) return '0';
        return Number(num).toLocaleString('fr-FR');
    };

    const name = [affiliate.first_name, affiliate.last_name].filter(Boolean).join(' ') || affiliate.affiliate_id;

    // Formulaire d'ajustement de solde
    const walletForm = useForm({
        direction: 'credit',
        amount: '',
        reason: '',
    });

    const submitWallet = (e) => {
        e.preventDefault();
        walletForm.post(route('admin.affiliates.wallet-adjustments.store', affiliate.id), {
            onSuccess: () => walletForm.reset(),
        });
    };

    // Formulaire de suspension
    const suspendForm = useForm({
        category: 'fraud',
        reason: '',
        treatment_pending: 'freeze',
        treatment_available: 'freeze',
        treatment_withdrawals: 'hold',
    });

    const submitSuspend = (e) => {
        e.preventDefault();
        suspendForm.post(route('admin.affiliates.suspend', affiliate.id));
    };

    const handleReactivate = () => {
        if (confirm(`Réactiver le compte de ${name} ?`)) {
            router.post(route('admin.affiliates.reactivate', affiliate.id));
        }
    };

    const actions = (
        <Link
            href={route('admin.affiliates.index')}
            className="rounded-xl border border-zinc-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-zinc-700 shadow-2xs hover:bg-zinc-50"
        >
            ← Liste
        </Link>
    );

    return (
        <AdminLayout title={affiliate.affiliate_id} eyebrow={eyebrow} heading={heading || name} actions={actions}>
            {/* BADGES HEADER */}
            <div className="mb-6 flex flex-wrap items-center gap-3 text-xs text-zinc-500">
                <span className="font-mono font-bold text-zinc-800">{affiliate.affiliate_id}</span>
                <span>·</span>
                <span>{affiliate.email}</span>
                <span>·</span>
                {affiliate.status === 'active' ? (
                    <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 font-semibold text-emerald-800">
                        Actif
                    </span>
                ) : (
                    <span className="rounded-full bg-rose-100 px-2.5 py-0.5 font-semibold text-rose-800">
                        Suspendu
                    </span>
                )}
            </div>

            {/* GRILLE : PROFIL + PARRAIN */}
            <div className="grid gap-6 md:grid-cols-2">
                {/* PROFIL */}
                <div className="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-xs">
                    <h2 className="font-display text-xs font-bold uppercase tracking-wider text-zinc-500">
                        Profil
                    </h2>
                    <dl className="mt-3.5 grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                        <dt className="text-zinc-400">Nom</dt>
                        <dd className="font-medium text-zinc-900">{name}</dd>
                        <dt className="text-zinc-400">E-mail</dt>
                        <dd className="break-all font-medium text-zinc-900">{affiliate.email}</dd>
                        <dt className="text-zinc-400">Téléphone</dt>
                        <dd className="font-medium text-zinc-900">{affiliate.phone || '—'}</dd>
                        <dt className="text-zinc-400">Pays</dt>
                        <dd className="font-medium text-zinc-900">{affiliate.country || '—'}</dd>
                        <dt className="text-zinc-400">Identifiant affilié</dt>
                        <dd className="font-mono font-bold text-zinc-900">{affiliate.affiliate_id}</dd>
                        <dt className="text-zinc-400">Code de parrainage</dt>
                        <dd className="font-mono font-medium text-zinc-900">{affiliate.code_parrainage || '—'}</dd>
                        <dt className="text-zinc-400">Statut</dt>
                        <dd className="font-medium text-zinc-900">{affiliate.status}</dd>
                    </dl>
                </div>

                {/* PARRAIN & PORTEFEUILLE */}
                <div className="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-xs flex flex-col justify-between">
                    <div>
                        <h2 className="font-display text-xs font-bold uppercase tracking-wider text-zinc-500">
                            Parrain
                        </h2>
                        {affiliate.parent ? (
                            <div className="mt-3 flex items-center justify-between rounded-xl border border-zinc-200 bg-zinc-50/50 p-3">
                                <div>
                                    <p className="font-semibold text-zinc-900">
                                        {[affiliate.parent.first_name, affiliate.parent.last_name].join(' ')}
                                    </p>
                                    <p className="font-mono text-[11px] text-zinc-400">{affiliate.parent.affiliate_id}</p>
                                </div>
                                <Link
                                    href={route('admin.affiliates.show', affiliate.parent.id)}
                                    className="text-xs font-semibold text-accent hover:underline"
                                >
                                    Ouvrir la fiche →
                                </Link>
                            </div>
                        ) : (
                            <p className="mt-2 text-xs text-zinc-500">
                                Aucun parrain — cet affilié est une racine de réseau.
                            </p>
                        )}
                    </div>

                    <div className="mt-6 border-t border-zinc-100 pt-4">
                        <h3 className="font-display text-xs font-bold uppercase tracking-wider text-zinc-500">
                            Portefeuille
                        </h3>
                        <div className="mt-2.5 grid grid-cols-3 gap-2 text-center text-xs">
                            <div className="rounded-xl border border-zinc-200 bg-zinc-50/60 p-2.5">
                                <p className="font-display text-base font-bold text-zinc-900">
                                    {formatNumber(totals.ledger_balance)}
                                </p>
                                <p className="text-[10px] uppercase text-zinc-400">Solde (ledger)</p>
                            </div>
                            <div className="rounded-xl border border-zinc-200 bg-zinc-50/60 p-2.5">
                                <p className="font-display text-base font-bold text-zinc-800">
                                    {formatNumber(totals.commissions)}
                                </p>
                                <p className="text-[10px] uppercase text-zinc-400">Commissions</p>
                            </div>
                            <div className="rounded-xl border border-zinc-200 bg-zinc-50/60 p-2.5">
                                <p className="font-display text-base font-bold text-zinc-800">
                                    {formatNumber(totals.withdrawn)}
                                </p>
                                <p className="text-[10px] uppercase text-zinc-400">Retiré</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* GRILLE : SUSPENSION & AJUSTEMENT */}
            <div className="mt-6 grid gap-6 md:grid-cols-2">
                {/* SUSPENSION */}
                <div className="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-xs">
                    <h2 className="font-display text-xs font-bold uppercase tracking-wider text-zinc-500">
                        Compte — suspension (§63)
                    </h2>
                    <p className="mt-0.5 text-xs text-zinc-400">
                        Suspension de {name}
                    </p>

                    {affiliate.status !== 'suspended' ? (
                        <form onSubmit={submitSuspend} className="mt-4 space-y-3 text-xs">
                            <div>
                                <label className="font-medium text-zinc-700">Motif</label>
                                <select
                                    value={suspendForm.data.category}
                                    onChange={(e) => suspendForm.setData('category', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-zinc-300 p-2 shadow-2xs focus:border-rose-500 focus:outline-none"
                                >
                                    <option value="fraud">Fraude avérée</option>
                                    <option value="abuse">Abus du programme</option>
                                    <option value="terms_violation">Non-respect des CGU</option>
                                    <option value="inactivity">Inactivité prolongée</option>
                                    <option value="other">Autre motif</option>
                                </select>
                            </div>

                            <div>
                                <label className="font-medium text-zinc-700">Détail</label>
                                <textarea
                                    required
                                    rows="2"
                                    placeholder="Description de la fraude ou manipulation..."
                                    value={suspendForm.data.reason}
                                    onChange={(e) => suspendForm.setData('reason', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-zinc-300 p-2 shadow-2xs focus:border-rose-500 focus:outline-none"
                                />
                            </div>

                            <p className="pt-1 font-semibold uppercase text-[10px] text-zinc-400">
                                Traitement financier
                            </p>
                            <div className="grid grid-cols-3 gap-2">
                                <div>
                                    <label className="text-[10px] text-zinc-600">Commissions en attente</label>
                                    <select
                                        value={suspendForm.data.treatment_pending}
                                        onChange={(e) => suspendForm.setData('treatment_pending', e.target.value)}
                                        className="mt-1 block w-full rounded border border-zinc-300 p-1 text-[11px]"
                                    >
                                        <option value="freeze">Geler</option>
                                        <option value="cancel">Annuler</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="text-[10px] text-zinc-600">Commissions dispo</label>
                                    <select
                                        value={suspendForm.data.treatment_available}
                                        onChange={(e) => suspendForm.setData('treatment_available', e.target.value)}
                                        className="mt-1 block w-full rounded border border-zinc-300 p-1 text-[11px]"
                                    >
                                        <option value="freeze">Geler</option>
                                        <option value="clawback">Reprendre</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="text-[10px] text-zinc-600">Retraits en cours</label>
                                    <select
                                        value={suspendForm.data.treatment_withdrawals}
                                        onChange={(e) => suspendForm.setData('treatment_withdrawals', e.target.value)}
                                        className="mt-1 block w-full rounded border border-zinc-300 p-1 text-[11px]"
                                    >
                                        <option value="hold">Maintenir</option>
                                        <option value="rollback">Annuler</option>
                                    </select>
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={suspendForm.processing}
                                className="mt-2 rounded-xl bg-rose-600 px-4 py-2 font-semibold text-white shadow-2xs hover:bg-rose-700"
                            >
                                Suspendre le compte
                            </button>
                        </form>
                    ) : (
                        <div className="mt-4 rounded-xl border border-rose-200 bg-rose-50/70 p-4 text-xs">
                            <p className="font-bold text-rose-800">Compte actuellement suspendu</p>
                            <p className="mt-1 text-zinc-600">
                                Motif : {affiliate.suspension_category || 'Non renseigné'}
                            </p>
                            {affiliate.suspension_reason && (
                                <p className="mt-1 italic text-zinc-500">« {affiliate.suspension_reason} »</p>
                            )}
                            <button
                                type="button"
                                onClick={handleReactivate}
                                className="mt-3 rounded-xl bg-emerald-600 px-4 py-1.5 font-semibold text-white shadow-2xs hover:bg-emerald-700"
                            >
                                Réactiver le compte
                            </button>
                        </div>
                    )}
                </div>

                {/* AJUSTEMENT DU SOLDE */}
                <div className="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-xs">
                    <h2 className="font-display text-xs font-bold uppercase tracking-wider text-zinc-500">
                        Ajustement du solde (§49)
                    </h2>
                    <p className="mt-0.5 text-xs text-zinc-400">
                        Crée une écriture de ledger tracée. Jamais d'écriture directe sans trace.
                    </p>

                    <form onSubmit={submitWallet} className="mt-4 space-y-3 text-xs">
                        <div className="flex gap-2">
                            <select
                                value={walletForm.data.direction}
                                onChange={(e) => walletForm.setData('direction', e.target.value)}
                                className="rounded-lg border border-zinc-300 p-2 shadow-2xs focus:border-accent focus:outline-none"
                            >
                                <option value="credit">Créditer (+)</option>
                                <option value="debit">Débiter (−)</option>
                            </select>
                            <input
                                type="number"
                                min="0.01"
                                step="any"
                                required
                                placeholder="Montant (FCFA)"
                                value={walletForm.data.amount}
                                onChange={(e) => walletForm.setData('amount', e.target.value)}
                                className="flex-1 rounded-lg border border-zinc-300 px-3 py-2 shadow-2xs focus:border-accent focus:outline-none"
                            />
                        </div>

                        <div>
                            <input
                                type="text"
                                required
                                placeholder="Motif obligatoire de l'ajustement..."
                                value={walletForm.data.reason}
                                onChange={(e) => walletForm.setData('reason', e.target.value)}
                                className="block w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-2xs focus:border-accent focus:outline-none"
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={walletForm.processing}
                            className="rounded-xl bg-zinc-900 px-4 py-2 font-semibold text-white shadow-2xs hover:bg-zinc-800"
                        >
                            Enregistrer l'ajustement
                        </button>
                    </form>
                </div>
            </div>

            {/* LIGNÉE D'AFFILIATION */}
            <section className="mt-8">
                <h2 className="mb-3 font-display text-sm font-bold text-zinc-900">
                    Lignée
                </h2>
                <div className="grid gap-3 sm:grid-cols-3">
                    {lineage.map((level) => (
                        <div key={level.level} className="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-xs text-xs">
                            <div className="flex items-center justify-between">
                                <p className="font-bold text-zinc-900">Niveau {level.level}</p>
                                <span className="rounded bg-accent/15 px-2 py-0.5 font-mono font-bold text-accent">
                                    {level.rate} %
                                </span>
                            </div>
                            <dl className="mt-3 space-y-1.5 text-zinc-600">
                                <div className="flex justify-between">
                                    <dt>Filleuls</dt>
                                    <dd className="font-semibold text-zinc-900">{formatNumber(level.members)}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt>Actifs</dt>
                                    <dd className="font-semibold text-emerald-600">{formatNumber(level.active)}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt>Ventes réalisées</dt>
                                    <dd className="font-semibold text-zinc-900">{formatNumber(level.sales || 0)}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt>CA généré</dt>
                                    <dd className="font-bold text-zinc-900">{formatNumber(level.revenue)} FCFA</dd>
                                </div>
                            </dl>
                        </div>
                    ))}
                </div>

                {children && children.length > 0 && (
                    <div className="mt-4">
                        <p className="mb-2 text-[11px] font-bold uppercase tracking-wider text-zinc-400">
                            Filleuls directs
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {children.map((c) => (
                                <Link
                                    key={c.id}
                                    href={route('admin.affiliates.show', c.id)}
                                    className="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-2.5 py-1 text-xs text-zinc-700 shadow-2xs hover:border-accent"
                                >
                                    <span>{[c.first_name, c.last_name].join(' ')}</span>
                                    <span className="font-mono text-[10px] text-zinc-400">({c.affiliate_id})</span>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </section>

            {/* VENTES */}
            <section className="mt-8">
                <h2 className="mb-3 font-display text-sm font-bold text-zinc-900">
                    Ventes
                </h2>
                <div className="overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-xs">
                    <table className="w-full text-left text-xs">
                        <thead className="border-b border-zinc-200 bg-zinc-50 text-[10px] font-bold uppercase tracking-wider text-zinc-500">
                            <tr>
                                <th className="px-4 py-3">Commande</th>
                                <th className="px-4 py-3">Date</th>
                                <th className="px-4 py-3">Articles</th>
                                <th className="px-4 py-3">Montant</th>
                                <th className="px-4 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-100">
                            {orders.length > 0 ? (
                                orders.map((o) => (
                                    <tr key={o.id} className="hover:bg-zinc-50/60">
                                        <td className="px-4 py-3 font-mono font-semibold text-zinc-900">
                                            {o.order_number}
                                        </td>
                                        <td className="px-4 py-3 text-zinc-500">
                                            {o.created_at ? new Date(o.created_at).toLocaleDateString('fr-FR') : '—'}
                                        </td>
                                        <td className="px-4 py-3 text-zinc-600">{o.items_count || 1} article(s)</td>
                                        <td className="px-4 py-3 font-bold text-zinc-900">
                                            {formatNumber(o.total_amount)} {o.currency || 'FCFA'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800">
                                                {o.payment_status}
                                            </span>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="5" className="px-4 py-6 text-center text-zinc-500">
                                        Aucune vente enregistrée pour cet affilié.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </section>

            {/* COMMISSIONS */}
            <section className="mt-8">
                <h2 className="mb-3 font-display text-sm font-bold text-zinc-900">
                    Commissions
                </h2>
                <div className="overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-xs">
                    <table className="w-full text-left text-xs">
                        <thead className="border-b border-zinc-200 bg-zinc-50 text-[10px] font-bold uppercase tracking-wider text-zinc-500">
                            <tr>
                                <th className="px-4 py-3">Référence</th>
                                <th className="px-4 py-3">Date</th>
                                <th className="px-4 py-3">Niveau</th>
                                <th className="px-4 py-3">Montant</th>
                                <th className="px-4 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-100">
                            {commissions.length > 0 ? (
                                commissions.map((c) => (
                                    <tr key={c.id} className="hover:bg-zinc-50/60">
                                        <td className="px-4 py-3 font-mono font-semibold text-zinc-900">
                                            {c.reference || `COM-${c.id}`}
                                        </td>
                                        <td className="px-4 py-3 text-zinc-500">
                                            {c.created_at ? new Date(c.created_at).toLocaleDateString('fr-FR') : '—'}
                                        </td>
                                        <td className="px-4 py-3 text-zinc-600">Niveau {c.level || 1}</td>
                                        <td className="px-4 py-3 font-bold text-accent">
                                            +{formatNumber(c.amount)} {c.currency || 'FCFA'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-semibold text-zinc-700">
                                                {c.status}
                                            </span>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="5" className="px-4 py-6 text-center text-zinc-500">
                                        Aucune commission enregistrée.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </section>

            {/* RETRAITS */}
            <section className="mt-8">
                <h2 className="mb-3 font-display text-sm font-bold text-zinc-900">
                    Retraits
                </h2>
                <div className="overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-xs">
                    <table className="w-full text-left text-xs">
                        <thead className="border-b border-zinc-200 bg-zinc-50 text-[10px] font-bold uppercase tracking-wider text-zinc-500">
                            <tr>
                                <th className="px-4 py-3">Référence</th>
                                <th className="px-4 py-3">Date</th>
                                <th className="px-4 py-3">Méthode</th>
                                <th className="px-4 py-3">Montant</th>
                                <th className="px-4 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-100">
                            {withdrawals.length > 0 ? (
                                withdrawals.map((w) => (
                                    <tr key={w.id} className="hover:bg-zinc-50/60">
                                        <td className="px-4 py-3 font-mono font-semibold text-zinc-900">
                                            {w.reference}
                                        </td>
                                        <td className="px-4 py-3 text-zinc-500">
                                            {w.requested_at ? new Date(w.requested_at).toLocaleDateString('fr-FR') : '—'}
                                        </td>
                                        <td className="px-4 py-3 text-zinc-600">{w.method}</td>
                                        <td className="px-4 py-3 font-bold text-zinc-900">
                                            {formatNumber(w.amount)} {w.currency || 'FCFA'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-semibold text-zinc-700">
                                                {w.status}
                                            </span>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="5" className="px-4 py-6 text-center text-zinc-500">
                                        Aucun retrait enregistré.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </section>
        </AdminLayout>
    );
}
