import React from 'react';
import { useForm, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({
    enabled = false,
    starterRate = 0.15,
    growthRate = 0.1,
    growthThreshold = 3000000,
    announcementTicker = '',
    announcementTickerEnabled = true,
    vendorsWithOverride = [],
    heading = 'Réglages Vitrine & Marketplace',
    eyebrow = 'Place de marché',
}) {
    // Formulaire bandeau d'annonces
    const tickerForm = useForm({
        announcement_ticker: announcementTicker || '',
        announcement_ticker_enabled: Boolean(announcementTickerEnabled),
    });

    const submitTicker = (e) => {
        e.preventDefault();
        tickerForm.put(route('admin.marketplace.settings.announcement.update'));
    };

    // Formulaire Marketplace
    const mpForm = useForm({
        enabled: Boolean(enabled),
        starter_rate_percent: Math.round(starterRate * 100 * 100) / 100,
        growth_rate_percent: Math.round(growthRate * 100 * 100) / 100,
        growth_threshold_amount: growthThreshold,
    });

    const submitMp = (e) => {
        e.preventDefault();
        mpForm.put(route('admin.marketplace.settings.update'));
    };

    return (
        <AdminLayout title="Réglages Vitrine & Marketplace" eyebrow={eyebrow} heading={heading}>
            <div className="grid gap-8 lg:grid-cols-2">
                {/* 1. BANDEAU DÉFILANT D'ANNONCES VITRINE */}
                <div className="rounded-2xl border border-night-line bg-night-2 p-6 flex flex-col justify-between">
                    <div>
                        <div className="flex items-center justify-between">
                            <h2 className="font-sans text-base font-semibold text-night-ink">
                                Bandeau d'annonces défilant
                            </h2>
                            <span className="rounded-full bg-accent-soft px-2.5 py-0.5 text-xs font-bold text-accent-2 uppercase tracking-wider">
                                Vitrine
                            </span>
                        </div>
                        <p className="mt-1 text-xs text-night-ink-2">
                            Texte défilant affiché en haut de toutes les pages sous le menu de navigation.
                        </p>

                        <form onSubmit={submitTicker} className="mt-5 space-y-4 text-sm">
                            <div>
                                <label className="font-semibold text-night-ink-2">Texte à faire défiler</label>
                                <textarea
                                    rows="4"
                                    required
                                    value={tickerForm.data.announcement_ticker}
                                    onChange={(e) => tickerForm.setData('announcement_ticker', e.target.value)}
                                    placeholder="Ex. Téléchargement immédiat · Expédition sous 48 h · Affiliation sur 3 niveaux"
                                    className="mt-1.5 block w-full rounded-xl border bg-night border-night-ink-2/60 p-3 text-sm leading-relaxed text-night-ink focus:border-accent focus:outline-none"
                                />
                                {tickerForm.errors.announcement_ticker && (
                                    <p className="mt-1 text-rust">{tickerForm.errors.announcement_ticker}</p>
                                )}
                            </div>

                            <div className="flex items-center gap-2.5 pt-1">
                                <input
                                    type="checkbox"
                                    id="ticker_enabled"
                                    checked={tickerForm.data.announcement_ticker_enabled}
                                    onChange={(e) =>
                                        tickerForm.setData('announcement_ticker_enabled', e.target.checked)
                                    }
                                    className="rounded border-night-ink-2/60 text-accent-2 focus:ring-accent"
                                />
                                <label htmlFor="ticker_enabled" className="text-night-ink-2 font-medium">
                                    Activer l'affichage du bandeau d'annonces
                                </label>
                            </div>

                            <button
                                type="submit"
                                disabled={tickerForm.processing}
                                className="rounded-full bg-accent px-4 py-2 font-semibold text-white transition-colors hover:bg-accent-2 disabled:opacity-50 min-h-11"
                            >
                                Enregistrer le bandeau
                            </button>
                        </form>
                    </div>
                </div>

                {/* 2. RÉGLAGES GLOBAUX MARKETPLACE */}
                <div className="rounded-2xl border border-night-line bg-night-2 p-6 flex flex-col justify-between">
                    <div>
                        <div className="flex items-center justify-between">
                            <h2 className="font-sans text-base font-semibold text-night-ink">
                                Place de marché multi-vendeurs
                            </h2>
                            <span
                                className={[
                                    'rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider',
                                    mpForm.data.enabled
                                        ? 'bg-sage-soft text-sage'
                                        : 'bg-night-line text-night-ink-2',
                                ].join(' ')}
                            >
                                {mpForm.data.enabled ? 'Module Actif' : 'Module Inactif'}
                            </span>
                        </div>
                        <p className="mt-1 text-xs text-night-ink-2">
                            Gère l'ouverture de l'espace vendeurs tiers et les paliers de commission plateforme.
                        </p>

                        <form onSubmit={submitMp} className="mt-5 space-y-4 text-sm">
                            <div className="rounded-xl border border-night-line bg-night p-3.5 flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    id="mp_enabled"
                                    checked={mpForm.data.enabled}
                                    onChange={(e) => mpForm.setData('enabled', e.target.checked)}
                                    className="h-4 w-4 rounded border-night-ink-2/60 text-accent-2 focus:ring-accent"
                                />
                                <label htmlFor="mp_enabled" className="cursor-pointer">
                                    <span className="block font-semibold text-night-ink">
                                        Activer la place de marché multi-vendeurs
                                    </span>
                                    <span className="block text-xs text-night-ink-2">
                                        Si décoché, les pages vendeurs et offres externes sont masquées du public.
                                    </span>
                                </label>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="font-semibold text-night-ink-2">Taux Starter (%)</label>
                                    <input
                                        type="number"
                                        min="0"
                                        max="90"
                                        step="0.1"
                                        required
                                        value={mpForm.data.starter_rate_percent}
                                        onChange={(e) => mpForm.setData('starter_rate_percent', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-3 py-2.5 focus:border-accent focus:outline-none min-h-11"
                                    />
                                </div>
                                <div>
                                    <label className="font-semibold text-night-ink-2">Taux Growth (%)</label>
                                    <input
                                        type="number"
                                        min="0"
                                        max="90"
                                        step="0.1"
                                        required
                                        value={mpForm.data.growth_rate_percent}
                                        onChange={(e) => mpForm.setData('growth_rate_percent', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-3 py-2.5 focus:border-accent focus:outline-none min-h-11"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="font-semibold text-night-ink-2">Seuil palier Growth (FCFA)</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="1000"
                                    required
                                    value={mpForm.data.growth_threshold_amount}
                                    onChange={(e) => mpForm.setData('growth_threshold_amount', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-3 py-2.5 focus:border-accent focus:outline-none min-h-11"
                                />
                            </div>

                            <button
                                type="submit"
                                disabled={mpForm.processing}
                                className="rounded-full bg-accent px-4 py-2 font-semibold text-white transition-colors hover:bg-accent-2 disabled:opacity-50 min-h-11"
                            >
                                Enregistrer les réglages
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {/* 3. DÉROGATIONS DE COMMISSION PAR VENDEUR */}
            <div className="mt-8 rounded-2xl border border-night-line bg-night-2 p-6">
                <h3 className="font-sans text-sm font-semibold text-night-ink">
                    Dérogations manuelles par vendeur
                </h3>
                <p className="mt-1 text-xs text-night-ink-2">
                    Vendeurs pour lesquels un taux de commission négocié individuel est appliqué en priorité sur le catalogue.
                </p>

                <div className="mt-4 overflow-x-auto rounded-xl border border-night-line">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-night-line text-xs font-semibold uppercase tracking-[0.08em] text-night-ink-2">
                            <tr>
                                <th className="px-4 py-2.5">Boutique vendeur</th>
                                <th className="px-4 py-2.5">Taux dérogatoire</th>
                                <th className="px-4 py-2.5">Dernière modification</th>
                                <th className="px-4 py-2.5 text-right">Fiche</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-night-line">
                            {vendorsWithOverride && vendorsWithOverride.length > 0 ? (
                                vendorsWithOverride.map((v) => (
                                    <tr key={v.id} className="hover:bg-night-line/50">
                                        <td className="px-4 py-2.5 font-semibold text-night-ink">
                                            {v.display_name}
                                        </td>
                                        <td className="px-4 py-2.5 font-mono font-bold text-accent-2">
                                            {Math.round(v.commission_rate_override * 100 * 100) / 100} %
                                        </td>
                                        <td className="px-4 py-2.5 text-night-ink-2">
                                            {v.updated_at ? new Date(v.updated_at).toLocaleDateString('fr-FR') : '—'}
                                        </td>
                                        <td className="px-4 py-2.5 text-right">
                                            <Link
                                                href={route('admin.marketplace.vendors.show', v.id)}
                                                className="font-semibold text-accent-2 hover:text-night-ink"
                                            >
                                                Voir
                                            </Link>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="4" className="px-4 py-6 text-center text-night-ink-2">
                                        Aucun vendeur n'a de taux dérogatoire (tous au barème Starter/Growth).
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}
