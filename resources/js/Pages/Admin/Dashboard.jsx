import React from 'react';
import { Link, Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Dashboard({
    stats = {},
    formatted_stats = {},
    labels = {},
    heading = 'Tableau de bord',
    eyebrow = 'MALKUTHAR',
    pendingWithdrawals = [],
    recentAffiliates = [],
    recentAudit = [],
}) {
    const formatNumber = (num) => {
        if (num === null || num === undefined) return '0';
        return Number(num).toLocaleString('fr-FR');
    };

    return (
        <AdminLayout title="Tableau de bord" eyebrow={eyebrow} heading={heading}>
            <Head title="Administration — Tableau de bord" />

            {/* GRILLE DES KPIS PRINCIPAUX */}
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {/* 1. Affiliés */}
                <div className="flex flex-col justify-between rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-xs transition-shadow hover:shadow-sm">
                    <div>
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                {labels.affiliates || 'Affiliés'}
                            </span>
                            <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600">
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </span>
                        </div>
                        <div className="mt-3 flex items-baseline gap-2">
                            <span className="font-display text-3xl font-extrabold tracking-tight text-zinc-900">
                                {formatted_stats.affiliates_total || formatNumber(stats.affiliates_total)}
                            </span>
                        </div>
                    </div>
                    <div className="mt-4 border-t border-zinc-100 pt-3 text-xs text-zinc-600">
                        <span className="font-medium text-emerald-600">
                            {formatted_stats.affiliates_active || formatNumber(stats.affiliates_active)} actifs
                        </span>{' '}
                        · {formatted_stats.affiliates_new || formatNumber(stats.affiliates_new)} ce mois
                        {stats.affiliates_suspended > 0 && (
                            <span className="ml-1 text-rose-600">
                                · {formatted_stats.affiliates_suspended || formatNumber(stats.affiliates_suspended)} suspendus
                            </span>
                        )}
                    </div>
                </div>

                {/* 2. Chiffre d'affaires */}
                <div className="flex flex-col justify-between rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-xs transition-shadow hover:shadow-sm">
                    <div>
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                {labels.revenue || "Chiffre d'affaires"}
                            </span>
                            <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <div className="mt-3 flex items-baseline gap-2">
                            <span className="font-display text-3xl font-extrabold tracking-tight text-zinc-900">
                                {formatted_stats.revenue || formatNumber(stats.revenue)}
                            </span>
                            <span className="text-xs font-semibold text-zinc-500">FCFA</span>
                        </div>
                    </div>
                    <div className="mt-4 border-t border-zinc-100 pt-3 text-xs text-zinc-600">
                        <span>{formatted_stats.orders_paid || formatNumber(stats.orders_paid)} commandes payées</span>
                        {stats.cod_to_collect_count > 0 && (
                            <Link
                                href={route('admin.orders.index', { provider: 'cod', payment_status: 'pending' })}
                                className="ml-1 text-amber-700 font-medium hover:underline"
                            >
                                · {formatted_stats.cod_to_collect_amount || formatNumber(stats.cod_to_collect_amount)} à encaisser (COD)
                            </Link>
                        )}
                    </div>
                </div>

                {/* 3. Commissions générées */}
                <div className="flex flex-col justify-between rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-xs transition-shadow hover:shadow-sm">
                    <div>
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                Commissions générées
                            </span>
                            <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </span>
                        </div>
                        <div className="mt-3 flex items-baseline gap-2">
                            <span className="font-display text-3xl font-extrabold tracking-tight text-zinc-900">
                                {formatted_stats.commissions_generated || formatNumber(stats.commissions_generated)}
                            </span>
                            <span className="text-xs font-semibold text-zinc-500">FCFA</span>
                        </div>
                    </div>
                    <div className="mt-4 border-t border-zinc-100 pt-3 text-xs text-zinc-600">
                        <span className="text-amber-700 font-medium">
                            {formatted_stats.commissions_pending || formatNumber(stats.commissions_pending)} en attente
                        </span>{' '}
                        ·{' '}
                        <span className="text-emerald-600 font-medium">
                            {formatted_stats.commissions_available || formatNumber(stats.commissions_available)} disponibles
                        </span>
                    </div>
                </div>

                {/* 4. Retraits */}
                <div className="flex flex-col justify-between rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-xs transition-shadow hover:shadow-sm">
                    <div>
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                Retraits
                            </span>
                            <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </span>
                        </div>
                        <div className="mt-3 flex items-baseline gap-2">
                            <span className="font-display text-3xl font-extrabold tracking-tight text-zinc-900">
                                {formatted_stats.withdrawals_requested || formatNumber(stats.withdrawals_requested)}
                            </span>
                        </div>
                    </div>
                    <div className="mt-4 border-t border-zinc-100 pt-3 text-xs text-zinc-600">
                        <span className="text-amber-700 font-medium">
                            {formatted_stats.withdrawals_processing || formatNumber(stats.withdrawals_processing)} à traiter
                        </span>{' '}
                        · {formatted_stats.withdrawals_paid || formatNumber(stats.withdrawals_paid)} payés
                        {stats.withdrawals_failed > 0 && (
                            <span className="ml-1 text-rose-600">
                                · {formatted_stats.withdrawals_failed || formatNumber(stats.withdrawals_failed)} échoués
                            </span>
                        )}
                    </div>
                </div>
            </div>

            {/* DEUX COLONNES : RETRAITS À TRAITER & DERNIERS AFFILIÉS */}
            <div className="mt-8 grid gap-8 lg:grid-cols-2">
                {/* 1. Retraits à traiter */}
                <section className="flex flex-col">
                    <div className="mb-3.5 flex items-center justify-between">
                        <h2 className="font-display text-base font-bold text-zinc-900">
                            Retraits à traiter
                        </h2>
                        <Link
                            href={route('admin.withdrawals.index')}
                            className="text-xs font-semibold text-accent hover:text-accent-2 transition-colors"
                        >
                            Tout voir →
                        </Link>
                    </div>

                    <div className="flex-1 space-y-2">
                        {pendingWithdrawals && pendingWithdrawals.length > 0 ? (
                            pendingWithdrawals.map((w) => (
                                <div
                                    key={w.id || w.reference}
                                    className="flex items-center justify-between rounded-xl border border-zinc-200/80 bg-white px-4 py-3 text-sm shadow-2xs transition-colors hover:border-zinc-300"
                                >
                                    <div>
                                        <p className="font-mono text-xs font-semibold text-zinc-900">
                                            {w.reference}
                                        </p>
                                        <p className="text-xs text-zinc-500">
                                            {w.user?.affiliate_id || '—'} · {w.method}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <p className="font-semibold text-zinc-900">
                                            {formatNumber(w.amount)} {w.currency || 'FCFA'}
                                        </p>
                                        <span className="inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800">
                                            En cours
                                        </span>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="rounded-xl border border-dashed border-zinc-300 bg-white/60 px-4 py-8 text-center text-xs text-zinc-500">
                                Aucun retrait en attente.
                            </div>
                        )}
                    </div>
                </section>

                {/* 2. Derniers affiliés */}
                <section className="flex flex-col">
                    <div className="mb-3.5 flex items-center justify-between">
                        <h2 className="font-display text-base font-bold text-zinc-900">
                            Derniers affiliés
                        </h2>
                        <Link
                            href={route('admin.affiliates.index')}
                            className="text-xs font-semibold text-accent hover:text-accent-2 transition-colors"
                        >
                            Tout voir →
                        </Link>
                    </div>

                    <div className="flex-1 space-y-2">
                        {recentAffiliates && recentAffiliates.length > 0 ? (
                            recentAffiliates.map((a) => (
                                <Link
                                    key={a.id}
                                    href={route('admin.affiliates.show', a.id)}
                                    className="flex items-center justify-between rounded-xl border border-zinc-200/80 bg-white px-4 py-3 text-sm shadow-2xs transition-all hover:border-accent hover:shadow-xs"
                                >
                                    <div>
                                        <p className="font-semibold text-zinc-900">
                                            {[a.first_name, a.last_name].filter(Boolean).join(' ') || 'Affilié'}
                                        </p>
                                        <p className="font-mono text-xs text-zinc-500">{a.affiliate_id}</p>
                                    </div>
                                    <div>
                                        {a.status === 'active' ? (
                                            <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
                                                Actif
                                            </span>
                                        ) : (
                                            <span className="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800">
                                                {a.status || 'Inactif'}
                                            </span>
                                        )}
                                    </div>
                                </Link>
                            ))
                        ) : (
                            <div className="rounded-xl border border-dashed border-zinc-300 bg-white/60 px-4 py-8 text-center text-xs text-zinc-500">
                                Aucun affilié enregistré.
                            </div>
                        )}
                    </div>
                </section>
            </div>

            {/* ACTIVITÉ RÉCENTE (JOURNAL D'AUDIT) */}
            <section className="mt-8">
                <div className="mb-3.5 flex items-center justify-between">
                    <h2 className="font-display text-base font-bold text-zinc-900">
                        Activité récente
                    </h2>
                    <Link
                        href={route('admin.audit-logs.index')}
                        className="text-xs font-semibold text-accent hover:text-accent-2 transition-colors"
                    >
                        Journal complet →
                    </Link>
                </div>

                <div className="overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-xs">
                    {recentAudit && recentAudit.length > 0 ? (
                        <div className="divide-y divide-zinc-100">
                            {recentAudit.map((log) => (
                                <div
                                    key={log.id}
                                    className="flex items-start justify-between gap-4 px-4 py-3.5 text-sm transition-colors hover:bg-zinc-50/60"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="text-zinc-900 font-medium">{log.description}</p>
                                        <p className="mt-0.5 font-mono text-[11px] text-zinc-500">{log.action}</p>
                                    </div>
                                    <div className="shrink-0 text-right text-xs text-zinc-500">
                                        <p className="font-medium text-zinc-700">
                                            {log.admin?.first_name || 'Admin'}
                                        </p>
                                        <p className="text-[11px] text-zinc-400">
                                            {log.created_at ? new Date(log.created_at).toLocaleDateString('fr-FR', {
                                                day: '2-digit',
                                                month: '2-digit',
                                                hour: '2-digit',
                                                minute: '2-digit',
                                            }) : ''}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="px-4 py-8 text-center text-xs text-zinc-500">
                            Aucune action enregistrée.
                        </div>
                    )}
                </div>
            </section>
        </AdminLayout>
    );
}
