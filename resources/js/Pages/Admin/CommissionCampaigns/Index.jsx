import React, { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({
    campaigns = [],
    resolution = {},
    validationDelayDays = 14,
    applied_rates_label = 'Taux appliqués en ce moment',
    current_rates_formatted = '',
    heading = 'Taux de commission',
    eyebrow = 'Affiliation',
}) {
    const current = resolution.rates || { 1: 0.15, 2: 0.1, 3: 0.05 };
    const r1 = Math.round((current[1] || 0.15) * 100);
    const r2 = Math.round((current[2] || 0.1) * 100);
    const r3 = Math.round((current[3] || 0.05) * 100);
    const ratesDisplay = current_rates_formatted || `${r1} % · ${r2} % · ${r3} %`;

    // Form pour le délai de validation
    const delayForm = useForm({
        validation_delay_days: validationDelayDays,
    });

    const submitDelay = (e) => {
        e.preventDefault();
        delayForm.put(route('admin.commission-campaigns.validation-delay'));
    };

    // Form pour créer une nouvelle campagne
    const campaignForm = useForm({
        name: '',
        rate_l1: 15,
        rate_l2: 10,
        rate_l3: 5,
        starts_at: '',
        ends_at: '',
        priority: 0,
        is_default: false,
    });

    const submitCampaign = (e) => {
        e.preventDefault();
        campaignForm.post(route('admin.commission-campaigns.store'), {
            onSuccess: () => campaignForm.reset(),
        });
    };

    const handleDelete = (campaignId, campaignName) => {
        if (confirm(`Supprimer la campagne « ${campaignName} » ?`)) {
            router.delete(route('admin.commission-campaigns.destroy', campaignId));
        }
    };

    return (
        <AdminLayout title="Taux de commission" eyebrow={eyebrow} heading={heading}>
            {/* EN-TÊTE : TAUX EN VIGUEUR + DÉLAI DE VALIDATION */}
            <div className="mb-6 grid gap-4 md:grid-cols-2">
                <div className="rounded-2xl border border-accent/40 bg-amber/25 p-5">
                    <p className="text-xs font-bold uppercase tracking-wider text-accent-2">
                        {applied_rates_label}
                    </p>
                    <p className="mt-2 font-display text-2xl font-semibold text-night-ink">
                        {ratesDisplay}
                    </p>
                    <p className="mt-1 text-xs text-night-ink-2">
                        {resolution.campaign
                            ? `Campagne active : « ${resolution.campaign.name} »`
                            : 'Taux standards (config) — aucune campagne personnalisée active'}
                    </p>
                </div>

                <div className="rounded-2xl border border-night-line bg-night-2 p-5">
                    <p className="text-xs font-bold uppercase tracking-wider text-night-ink-2">
                        Période de validation (§42)
                    </p>
                    <p className="mt-1 text-xs text-night-ink-2">
                        Nombre de jours après la commande avant qu'une commission en attente ne devienne retirable.
                    </p>
                    <form onSubmit={submitDelay} className="mt-3 flex items-end gap-2.5">
                        <div className="w-28">
                            <label className="text-sm font-medium text-night-ink-2">Jours</label>
                            <input
                                type="number"
                                min="0"
                                max="365"
                                required
                                value={delayForm.data.validation_delay_days}
                                onChange={(e) => delayForm.setData('validation_delay_days', e.target.value)}
                                className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-3 py-2.5 text-sm text-night-ink focus:border-accent focus:outline-none min-h-11"
                            />
                        </div>
                        <button
                            type="submit"
                            disabled={delayForm.processing}
                            className="rounded-full bg-accent px-3.5 py-1.5 text-sm font-semibold text-white transition-colors hover:bg-accent-2 disabled:opacity-50 min-h-11"
                        >
                            Enregistrer
                        </button>
                    </form>
                    {delayForm.errors.validation_delay_days && (
                        <p className="mt-1 text-xs text-rust">{delayForm.errors.validation_delay_days}</p>
                    )}
                </div>
            </div>

            {/* GRILLE : LISTE DES CAMPAGNES + FORMULAIRE D'AJOUT */}
            <div className="grid gap-8 lg:grid-cols-[1fr_340px]">
                {/* LISTE */}
                <div className="space-y-4">
                    <div className="overflow-x-auto rounded-2xl border border-night-line bg-night-2">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-night-line text-xs font-semibold uppercase tracking-[0.08em] text-night-ink-2">
                                <tr>
                                    <th className="px-4 py-3">Campagne</th>
                                    <th className="px-4 py-3">Taux (N1 / N2 / N3)</th>
                                    <th className="px-4 py-3">Période</th>
                                    <th className="px-4 py-3">Prio.</th>
                                    <th className="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-night-line">
                                {campaigns.length > 0 ? (
                                    campaigns.map((c) => (
                                        <tr key={c.id} className="hover:bg-night-line/50">
                                            <td className="px-4 py-3 font-semibold text-night-ink">
                                                <div className="flex items-center gap-2">
                                                    <span>{c.name}</span>
                                                    {c.is_default && (
                                                        <span className="rounded bg-accent-soft px-1.5 py-0.5 text-xs font-bold text-accent-2">
                                                            Par défaut
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 font-mono font-medium text-night-ink-2">
                                                {Math.round(c.rate_l1 * 100)} % · {Math.round(c.rate_l2 * 100)} % · {Math.round(c.rate_l3 * 100)} %
                                            </td>
                                            <td className="px-4 py-3 text-night-ink-2">
                                                {c.starts_at || c.ends_at ? (
                                                    <span>
                                                        {c.starts_at ? new Date(c.starts_at).toLocaleDateString('fr-FR') : '—'} au{' '}
                                                        {c.ends_at ? new Date(c.ends_at).toLocaleDateString('fr-FR') : '—'}
                                                    </span>
                                                ) : (
                                                    <span className="text-night-ink-2">Permanente</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 font-mono text-night-ink-2">{c.priority}</td>
                                            <td className="px-4 py-3 text-right">
                                                <button
                                                    type="button"
                                                    onClick={() => handleDelete(c.id, c.name)}
                                                    className="font-medium text-rust hover:text-rust hover:underline"
                                                >
                                                    Supprimer
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="5" className="px-4 py-8 text-center text-night-ink-2">
                                            Aucune campagne créée. Les taux standards 15 % · 10 % · 5 % s'appliquent.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* FORMULAIRE NOUVELLE CAMPAGNE */}
                <div className="rounded-2xl border border-night-line bg-night-2 p-5 h-fit">
                    <h2 className="font-sans text-sm font-semibold text-night-ink">
                        Nouvelle campagne
                    </h2>
                    <form onSubmit={submitCampaign} className="mt-4 space-y-3.5 text-sm">
                        <div>
                            <label className="font-medium text-night-ink-2">Nom de la campagne</label>
                            <input
                                type="text"
                                required
                                placeholder="Ex. Promotion Rentrée"
                                value={campaignForm.data.name}
                                onChange={(e) => campaignForm.setData('name', e.target.value)}
                                className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-3 py-2.5 focus:border-accent focus:outline-none min-h-11"
                            />
                        </div>

                        <div className="grid grid-cols-3 gap-2">
                            <div>
                                <label className="font-medium text-night-ink-2">Niveau 1 (%)</label>
                                <input
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                    required
                                    value={campaignForm.data.rate_l1}
                                    onChange={(e) => campaignForm.setData('rate_l1', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-2 py-2.5 focus:border-accent focus:outline-none min-h-11"
                                />
                            </div>
                            <div>
                                <label className="font-medium text-night-ink-2">Niveau 2 (%)</label>
                                <input
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                    required
                                    value={campaignForm.data.rate_l2}
                                    onChange={(e) => campaignForm.setData('rate_l2', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-2 py-2.5 focus:border-accent focus:outline-none min-h-11"
                                />
                            </div>
                            <div>
                                <label className="font-medium text-night-ink-2">Niveau 3 (%)</label>
                                <input
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                    required
                                    value={campaignForm.data.rate_l3}
                                    onChange={(e) => campaignForm.setData('rate_l3', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-2 py-2.5 focus:border-accent focus:outline-none min-h-11"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <label className="font-medium text-night-ink-2">Début</label>
                                <input
                                    type="date"
                                    value={campaignForm.data.starts_at}
                                    onChange={(e) => campaignForm.setData('starts_at', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-2 py-2.5 focus:border-accent focus:outline-none min-h-11"
                                />
                            </div>
                            <div>
                                <label className="font-medium text-night-ink-2">Fin</label>
                                <input
                                    type="date"
                                    value={campaignForm.data.ends_at}
                                    onChange={(e) => campaignForm.setData('ends_at', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-2 py-2.5 focus:border-accent focus:outline-none min-h-11"
                                />
                            </div>
                        </div>

                        <div>
                            <label className="font-medium text-night-ink-2">Priorité (0-100)</label>
                            <input
                                type="number"
                                min="0"
                                max="100"
                                value={campaignForm.data.priority}
                                onChange={(e) => campaignForm.setData('priority', e.target.value)}
                                className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-3 py-2.5 focus:border-accent focus:outline-none min-h-11"
                            />
                        </div>

                        <div className="flex items-center gap-2 pt-1">
                            <input
                                type="checkbox"
                                id="is_default"
                                checked={campaignForm.data.is_default}
                                onChange={(e) => campaignForm.setData('is_default', e.target.checked)}
                                className="rounded border-night-ink-2/60 text-accent-2 focus:ring-accent"
                            />
                            <label htmlFor="is_default" className="text-night-ink-2">
                                Définir comme campagne par défaut
                            </label>
                        </div>

                        <button
                            type="submit"
                            disabled={campaignForm.processing}
                            className="w-full rounded-full bg-accent px-4 py-2 font-semibold text-white transition-colors hover:bg-accent-2 disabled:opacity-50 min-h-11"
                        >
                            Créer la campagne
                        </button>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
