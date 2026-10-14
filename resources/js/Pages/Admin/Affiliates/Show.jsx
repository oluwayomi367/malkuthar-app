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
            className="rounded-full border border-night-line bg-night-2 px-3.5 py-1.5 text-sm font-semibold text-night-ink-2 hover:bg-night-line/50 min-h-11 inline-flex items-center justify-center"
        >
            Liste
        </Link>
    );

    return (
        <AdminLayout title={affiliate.affiliate_id} eyebrow={eyebrow} heading={heading || name} actions={actions}>
            {/* BADGES HEADER */}
            <div className="mb-6 flex flex-wrap items-center gap-3 text-sm text-night-ink-2">
                <span className="font-mono font-bold text-night-ink">{affiliate.affiliate_id}</span>
                <span>·</span>
                <span>{affiliate.email}</span>
                <span>·</span>
                {affiliate.status === 'active' ? (
                    <span className="rounded-full bg-sage/30 px-2.5 py-0.5 font-semibold text-sage-soft">
                        Actif
                    </span>
                ) : (
                    <span className="rounded-full bg-rust/20 px-2.5 py-0.5 font-semibold text-rust-tint">
                        Suspendu
                    </span>
                )}
            </div>

            {/* GRILLE : PROFIL + PARRAIN */}
            <div className="grid gap-6 md:grid-cols-2">
                {/* PROFIL */}
                <div className="rounded-2xl border border-night-line bg-night-2 p-5">
                    <h2 className="font-sans text-xs font-semibold uppercase tracking-wider text-night-ink-2">
                        Profil
                    </h2>
                    <dl className="mt-3.5 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <dt className="text-night-ink-2">Nom</dt>
                        <dd className="font-medium text-night-ink">{name}</dd>
                        <dt className="text-night-ink-2">E-mail</dt>
                        <dd className="break-all font-medium text-night-ink">{affiliate.email}</dd>
                        <dt className="text-night-ink-2">Téléphone</dt>
                        <dd className="font-medium text-night-ink">{affiliate.phone || '—'}</dd>
                        <dt className="text-night-ink-2">Pays</dt>
                        <dd className="font-medium text-night-ink">{affiliate.country || '—'}</dd>
                        <dt className="text-night-ink-2">Identifiant affilié</dt>
                        <dd className="font-mono font-bold text-night-ink">{affiliate.affiliate_id}</dd>
                        <dt className="text-night-ink-2">Code de parrainage</dt>
                        <dd className="font-mono font-medium text-night-ink">{affiliate.code_parrainage || '—'}</dd>
                        <dt className="text-night-ink-2">Statut</dt>
                        <dd className="font-medium text-night-ink">{affiliate.status}</dd>
                    </dl>
                </div>

                {/* PARRAIN & PORTEFEUILLE */}
                <div className="rounded-2xl border border-night-line bg-night-2 p-5 flex flex-col justify-between">
                    <div>
                        <h2 className="font-sans text-xs font-semibold uppercase tracking-wider text-night-ink-2">
                            Parrain
                        </h2>
                        {affiliate.parent ? (
                            <div className="mt-3 flex items-center justify-between rounded-xl border border-night-line bg-night p-3">
                                <div>
                                    <p className="font-semibold text-night-ink">
                                        {[affiliate.parent.first_name, affiliate.parent.last_name].join(' ')}
                                    </p>
                                    <p className="font-mono text-xs text-night-ink-2">{affiliate.parent.affiliate_id}</p>
                                </div>
                                <Link
                                    href={route('admin.affiliates.show', affiliate.parent.id)}
                                    className="text-xs font-semibold text-accent-light hover:underline"
                                >
                                    Ouvrir la fiche
                                </Link>
                            </div>
                        ) : (
                            <p className="mt-2 text-xs text-night-ink-2">
                                Aucun parrain — cet affilié est une racine de réseau.
                            </p>
                        )}
                    </div>

                    <div className="mt-6 border-t border-night-line pt-4">
                        <h3 className="font-sans text-xs font-semibold uppercase tracking-wider text-night-ink-2">
                            Portefeuille
                        </h3>
                        <div className="mt-2.5 grid grid-cols-3 gap-2 text-center text-sm">
                            <div className="rounded-xl border border-night-line bg-night p-2.5">
                                <p className="font-sans text-base font-semibold text-night-ink">
                                    {formatNumber(totals.ledger_balance)}
                                </p>
                                <p className="text-xs uppercase text-night-ink-2">Solde (ledger)</p>
                            </div>
                            <div className="rounded-xl border border-night-line bg-night p-2.5">
                                <p className="font-sans text-base font-semibold text-night-ink">
                                    {formatNumber(totals.commissions)}
                                </p>
                                <p className="text-xs uppercase text-night-ink-2">Commissions</p>
                            </div>
                            <div className="rounded-xl border border-night-line bg-night p-2.5">
                                <p className="font-sans text-base font-semibold text-night-ink">
                                    {formatNumber(totals.withdrawn)}
                                </p>
                                <p className="text-xs uppercase text-night-ink-2">Retiré</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* GRILLE : SUSPENSION & AJUSTEMENT */}
            <div className="mt-6 grid gap-6 md:grid-cols-2">
                {/* SUSPENSION */}
                <div className="rounded-2xl border border-night-line bg-night-2 p-5">
                    <h2 className="font-sans text-xs font-semibold uppercase tracking-wider text-night-ink-2">
                        Compte — suspension (§63)
                    </h2>
                    <p className="mt-0.5 text-xs text-night-ink-2">
                        Suspension de {name}
                    </p>

                    {affiliate.status !== 'suspended' ? (
                        <form onSubmit={submitSuspend} className="mt-4 space-y-3 text-sm">
                            <div>
                                <label className="font-medium text-night-ink-2">Motif</label>
                                <select
                                    value={suspendForm.data.category}
                                    onChange={(e) => suspendForm.setData('category', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-night-ink-2/60 p-2 focus:border-rust focus:outline-none min-h-11"
                                >
                                    <option value="fraud">Fraude avérée</option>
                                    <option value="abuse">Abus du programme</option>
                                    <option value="terms_violation">Non-respect des CGU</option>
                                    <option value="inactivity">Inactivité prolongée</option>
                                    <option value="other">Autre motif</option>
                                </select>
                            </div>

                            <div>
                                <label className="font-medium text-night-ink-2">Détail</label>
                                <textarea
                                    required
                                    rows="2"
                                    placeholder="Description de la fraude ou manipulation..."
                                    value={suspendForm.data.reason}
                                    onChange={(e) => suspendForm.setData('reason', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-night-ink-2/60 p-2 focus:border-rust focus:outline-none"
                                />
                            </div>

                            <p className="pt-1 font-semibold uppercase text-xs text-night-ink-2">
                                Traitement financier
                            </p>
                            <div className="grid grid-cols-3 gap-2">
                                <div>
                                    <label className="text-sm text-night-ink-2">Commissions en attente</label>
                                    <select
                                        value={suspendForm.data.treatment_pending}
                                        onChange={(e) => suspendForm.setData('treatment_pending', e.target.value)}
                                        className="mt-1 block w-full rounded border border-night-ink-2/60 p-1 text-sm min-h-11"
                                    >
                                        <option value="freeze">Geler</option>
                                        <option value="cancel">Annuler</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="text-sm text-night-ink-2">Commissions dispo</label>
                                    <select
                                        value={suspendForm.data.treatment_available}
                                        onChange={(e) => suspendForm.setData('treatment_available', e.target.value)}
                                        className="mt-1 block w-full rounded border border-night-ink-2/60 p-1 text-sm min-h-11"
                                    >
                                        <option value="freeze">Geler</option>
                                        <option value="clawback">Reprendre</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="text-sm text-night-ink-2">Retraits en cours</label>
                                    <select
                                        value={suspendForm.data.treatment_withdrawals}
                                        onChange={(e) => suspendForm.setData('treatment_withdrawals', e.target.value)}
                                        className="mt-1 block w-full rounded border border-night-ink-2/60 p-1 text-sm min-h-11"
                                    >
                                        <option value="hold">Maintenir</option>
                                        <option value="rollback">Annuler</option>
                                    </select>
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={suspendForm.processing}
                                className="mt-2 rounded-full bg-rust px-4 py-2 font-semibold text-white hover:bg-rust/85 min-h-11"
                            >
                                Suspendre le compte
                            </button>
                        </form>
                    ) : (
                        <div className="mt-4 rounded-xl border border-rust bg-rust/20 p-4 text-sm">
                            <p className="font-bold text-rust-tint">Compte actuellement suspendu</p>
                            <p className="mt-1 text-night-ink-2">
                                Motif : {affiliate.suspension_category || 'Non renseigné'}
                            </p>
                            {affiliate.suspension_reason && (
                                <p className="mt-1 italic text-night-ink-2">« {affiliate.suspension_reason} »</p>
                            )}
                            <button
                                type="button"
                                onClick={handleReactivate}
                                className="mt-3 rounded-full bg-sage px-4 py-1.5 font-semibold text-white hover:bg-sage/85 min-h-11"
                            >
                                Réactiver le compte
                            </button>
                        </div>
                    )}
                </div>

                {/* AJUSTEMENT DU SOLDE */}
                <div className="rounded-2xl border border-night-line bg-night-2 p-5">
                    <h2 className="font-sans text-xs font-semibold uppercase tracking-wider text-night-ink-2">
                        Ajustement du solde (§49)
                    </h2>
                    <p className="mt-0.5 text-xs text-night-ink-2">
                        Crée une écriture de ledger tracée. Jamais d'écriture directe sans trace.
                    </p>

                    <form onSubmit={submitWallet} className="mt-4 space-y-3 text-sm">
                        <div className="flex gap-2">
                            <select
                                value={walletForm.data.direction}
                                onChange={(e) => walletForm.setData('direction', e.target.value)}
                                className="rounded-lg border border-night-ink-2/60 p-2 focus:border-accent-light focus:outline-none min-h-11"
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
                                className="flex-1 rounded-lg border border-night-ink-2/60 px-3 py-2.5 focus:border-accent-light focus:outline-none min-h-11"
                            />
                        </div>

                        <div>
                            <input
                                type="text"
                                required
                                placeholder="Motif obligatoire de l'ajustement..."
                                value={walletForm.data.reason}
                                onChange={(e) => walletForm.setData('reason', e.target.value)}
                                className="block w-full rounded-lg border border-night-ink-2/60 px-3 py-2.5 focus:border-accent-light focus:outline-none min-h-11"
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={walletForm.processing}
                            className="rounded-full bg-accent-light px-4 py-2 font-semibold text-night hover:bg-accent-light/85 min-h-11"
                        >
                            Enregistrer l'ajustement
                        </button>
                    </form>
                </div>
            </div>

            {/* LIGNÉE D'AFFILIATION */}
            <section className="mt-8">
                <h2 className="mb-3 font-sans text-sm font-semibold text-night-ink">
                    Lignée
                </h2>
                <div className="grid gap-3 sm:grid-cols-3">
                    {lineage.map((level) => (
                        <div key={level.level} className="rounded-2xl border border-night-line bg-night-2 p-4 text-sm">
                            <div className="flex items-center justify-between">
                                <p className="font-bold text-night-ink">Niveau {level.level}</p>
                                <span className="rounded bg-accent-light/15 px-2 py-0.5 font-mono font-bold text-accent-light">
                                    {level.rate} %
                                </span>
                            </div>
                            <dl className="mt-3 space-y-1.5 text-night-ink-2">
                                <div className="flex justify-between">
                                    <dt>Filleuls</dt>
                                    <dd className="font-semibold text-night-ink">{formatNumber(level.members)}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt>Actifs</dt>
                                    <dd className="font-semibold text-sage-soft">{formatNumber(level.active)}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt>Ventes réalisées</dt>
                                    <dd className="font-semibold text-night-ink">{formatNumber(level.sales || 0)}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt>CA généré</dt>
                                    <dd className="font-bold text-night-ink">{formatNumber(level.revenue)} FCFA</dd>
                                </div>
                            </dl>
                        </div>
                    ))}
                </div>

                {children && children.length > 0 && (
                    <div className="mt-4">
                        <p className="mb-2 text-xs font-bold uppercase tracking-wider text-night-ink-2">
                            Filleuls directs
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {children.map((c) => (
                                <Link
                                    key={c.id}
                                    href={route('admin.affiliates.show', c.id)}
                                    className="inline-flex items-center gap-1.5 rounded-full border border-night-line bg-night-2 px-2.5 py-1 text-sm text-night-ink-2 hover:border-accent-light min-h-11"
                                >
                                    <span>{[c.first_name, c.last_name].join(' ')}</span>
                                    <span className="font-mono text-xs text-night-ink-2">({c.affiliate_id})</span>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </section>

            {/* VENTES */}
            <section className="mt-8">
                <h2 className="mb-3 font-sans text-sm font-semibold text-night-ink">
                    Ventes
                </h2>
                <div className="overflow-x-auto rounded-2xl border border-night-line bg-night-2">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-night-line text-xs font-semibold uppercase tracking-[0.08em] text-night-ink-2">
                            <tr>
                                <th className="px-4 py-3">Commande</th>
                                <th className="px-4 py-3">Date</th>
                                <th className="px-4 py-3">Articles</th>
                                <th className="px-4 py-3">Montant</th>
                                <th className="px-4 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-night-line">
                            {orders.length > 0 ? (
                                orders.map((o) => (
                                    <tr key={o.id} className="hover:bg-night-line/50">
                                        <td className="px-4 py-3 font-mono font-semibold text-night-ink">
                                            {o.order_number}
                                        </td>
                                        <td className="px-4 py-3 text-night-ink-2">
                                            {o.created_at ? new Date(o.created_at).toLocaleDateString('fr-FR') : '—'}
                                        </td>
                                        <td className="px-4 py-3 text-night-ink-2">{o.items_count || 1} article(s)</td>
                                        <td className="px-4 py-3 font-bold text-night-ink">
                                            {formatNumber(o.total_amount)} {o.currency || 'FCFA'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="rounded-full bg-sage/30 px-2 py-0.5 text-xs font-semibold text-sage-soft">
                                                {o.payment_status}
                                            </span>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="5" className="px-4 py-6 text-center text-night-ink-2">
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
                <h2 className="mb-3 font-sans text-sm font-semibold text-night-ink">
                    Commissions
                </h2>
                <div className="overflow-x-auto rounded-2xl border border-night-line bg-night-2">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-night-line text-xs font-semibold uppercase tracking-[0.08em] text-night-ink-2">
                            <tr>
                                <th className="px-4 py-3">Référence</th>
                                <th className="px-4 py-3">Date</th>
                                <th className="px-4 py-3">Niveau</th>
                                <th className="px-4 py-3">Montant</th>
                                <th className="px-4 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-night-line">
                            {commissions.length > 0 ? (
                                commissions.map((c) => (
                                    <tr key={c.id} className="hover:bg-night-line/50">
                                        <td className="px-4 py-3 font-mono font-semibold text-night-ink">
                                            {c.reference || `COM-${c.id}`}
                                        </td>
                                        <td className="px-4 py-3 text-night-ink-2">
                                            {c.created_at ? new Date(c.created_at).toLocaleDateString('fr-FR') : '—'}
                                        </td>
                                        <td className="px-4 py-3 text-night-ink-2">Niveau {c.level || 1}</td>
                                        <td className="px-4 py-3 font-bold text-accent-light">
                                            +{formatNumber(c.amount)} {c.currency || 'FCFA'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="rounded-full bg-night-line px-2 py-0.5 text-xs font-semibold text-night-ink-2">
                                                {c.status}
                                            </span>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="5" className="px-4 py-6 text-center text-night-ink-2">
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
                <h2 className="mb-3 font-sans text-sm font-semibold text-night-ink">
                    Retraits
                </h2>
                <div className="overflow-x-auto rounded-2xl border border-night-line bg-night-2">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-night-line text-xs font-semibold uppercase tracking-[0.08em] text-night-ink-2">
                            <tr>
                                <th className="px-4 py-3">Référence</th>
                                <th className="px-4 py-3">Date</th>
                                <th className="px-4 py-3">Méthode</th>
                                <th className="px-4 py-3">Montant</th>
                                <th className="px-4 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-night-line">
                            {withdrawals.length > 0 ? (
                                withdrawals.map((w) => (
                                    <tr key={w.id} className="hover:bg-night-line/50">
                                        <td className="px-4 py-3 font-mono font-semibold text-night-ink">
                                            {w.reference}
                                        </td>
                                        <td className="px-4 py-3 text-night-ink-2">
                                            {w.requested_at ? new Date(w.requested_at).toLocaleDateString('fr-FR') : '—'}
                                        </td>
                                        <td className="px-4 py-3 text-night-ink-2">{w.method}</td>
                                        <td className="px-4 py-3 font-bold text-night-ink">
                                            {formatNumber(w.amount)} {w.currency || 'FCFA'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="rounded-full bg-night-line px-2 py-0.5 text-xs font-semibold text-night-ink-2">
                                                {w.status}
                                            </span>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="5" className="px-4 py-6 text-center text-night-ink-2">
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
