import React from 'react';
import { Link, Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

const formatNumber = (num) => {
    if (num === null || num === undefined) return '0';
    return Number(num).toLocaleString('fr-FR');
};

const formatDateTime = (value) =>
    value
        ? new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
        : '—';

/** Carte « file de décision » : un compteur cliquable vers la page qui permet d'agir. */
function DecisionQueue({ href, label, count, display, detail, tone = 'amber', emptyText = 'Rien en attente' }) {
    const pending = Number(count) > 0;
    const tones = {
        amber: 'bg-amber',
        rust: 'bg-rust',
    };

    return (
        <Link
            href={href}
            className="group flex flex-col justify-between rounded-2xl border border-night-line bg-night-2 p-5 transition-colors hover:border-accent/50"
        >
            <div className="flex items-start justify-between gap-3">
                <span className="text-sm font-medium text-night-ink-2">{label}</span>
                <svg className="h-4 w-4 shrink-0 text-night-ink-2 transition-colors group-hover:text-accent-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6" />
                </svg>
            </div>
            <p className={`mt-3 font-display text-4xl font-semibold leading-none ${pending ? 'text-night-ink' : 'text-night-ink-2'}`}>
                {display ?? formatNumber(count)}
            </p>
            <p className="mt-3 flex items-center gap-2 text-xs text-night-ink-2">
                {pending ? (
                    <>
                        <span className={`h-1.5 w-1.5 rounded-full ${tones[tone]}`} aria-hidden="true" />
                        <span className="font-semibold text-night-ink">À traiter</span>
                        {detail && <span>· {detail}</span>}
                    </>
                ) : (
                    <span>{emptyText}</span>
                )}
            </p>
        </Link>
    );
}

function Kpi({ label, value, unit, children }) {
    return (
        <div className="flex flex-col justify-between rounded-2xl border border-night-line bg-night-2 p-5">
            <div>
                <p className="text-xs font-semibold uppercase tracking-[0.08em] text-night-ink-2">{label}</p>
                <p className="mt-3 flex items-baseline gap-2">
                    <span className="font-display text-3xl font-semibold text-night-ink">{value}</span>
                    {unit && <span className="text-xs font-semibold text-night-ink-2">{unit}</span>}
                </p>
            </div>
            <div className="mt-4 border-t border-night-line pt-3 text-sm leading-relaxed text-night-ink-2">{children}</div>
        </div>
    );
}

function SectionHead({ title, href, linkLabel }) {
    return (
        <div className="mb-3 flex items-baseline justify-between gap-4">
            <h2 className="font-display text-2xl font-semibold text-night-ink">{title}</h2>
            {href && (
                <Link href={href} className="inline-flex min-h-11 items-center text-sm font-semibold text-accent-2 hover:text-night-ink">
                    {linkLabel}
                </Link>
            )}
        </div>
    );
}

const TH = 'px-4 py-3 font-semibold';
const TD = 'px-4 py-3';

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
    const f = (key) => formatted_stats[key] || formatNumber(stats[key]);

    return (
        <AdminLayout title="Tableau de bord" eyebrow={eyebrow} heading={heading}>
            <Head title="Administration — Tableau de bord" />

            {/* FILES DE DÉCISION — uniquement à partir des compteurs déjà fournis */}
            <section aria-labelledby="queues-title">
                <h2 id="queues-title" className="sr-only">À traiter</h2>
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <DecisionQueue
                        href={route('admin.withdrawals.index', { status: 'processing' })}
                        label="Retraits à verser"
                        count={stats.withdrawals_processing}
                        display={f('withdrawals_processing')}
                        detail="demandes en cours"
                    />
                    <DecisionQueue
                        href={route('admin.orders.index', { provider: 'cod', payment_status: 'pending' })}
                        label="Paiements à la livraison"
                        count={stats.cod_to_collect_count}
                        display={formatNumber(stats.cod_to_collect_count)}
                        detail={`${f('cod_to_collect_amount')} FCFA à encaisser`}
                    />
                    <DecisionQueue
                        href={route('admin.withdrawals.index', { status: 'failed' })}
                        label="Retraits échoués"
                        count={stats.withdrawals_failed}
                        display={f('withdrawals_failed')}
                        detail="à vérifier"
                        tone="rust"
                        emptyText="Aucun échec"
                    />
                    <DecisionQueue
                        href={route('admin.affiliates.index')}
                        label="Affiliés suspendus"
                        count={stats.affiliates_suspended}
                        display={f('affiliates_suspended')}
                        detail="comptes à réexaminer"
                        tone="rust"
                        emptyText="Aucun compte suspendu"
                    />
                </div>
            </section>

            {/* INDICATEURS */}
            <section className="mt-10">
                <SectionHead title="Indicateurs" />
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Kpi label={labels.affiliates || 'Affiliés'} value={f('affiliates_total')}>
                        <span className="font-semibold text-sage">{f('affiliates_active')} actifs</span>
                        {' · '}
                        {f('affiliates_new')} ce mois
                        {stats.affiliates_suspended > 0 && (
                            <span className="text-rust"> · {f('affiliates_suspended')} suspendus</span>
                        )}
                    </Kpi>

                    <Kpi label={labels.revenue || "Chiffre d'affaires"} value={f('revenue')} unit="FCFA">
                        {f('orders_paid')} commandes payées
                        {stats.cod_to_collect_count > 0 && (
                            <>
                                {' · '}
                                <Link
                                    href={route('admin.orders.index', { provider: 'cod', payment_status: 'pending' })}
                                    className="font-semibold text-amber underline decoration-amber/40 underline-offset-2 hover:decoration-amber"
                                >
                                    {f('cod_to_collect_amount')} à encaisser (COD)
                                </Link>
                            </>
                        )}
                    </Kpi>

                    <Kpi label="Commissions générées" value={f('commissions_generated')} unit="FCFA">
                        <span className="font-semibold text-amber">{f('commissions_pending')} en attente</span>
                        {' · '}
                        <span className="font-semibold text-sage">{f('commissions_available')} disponibles</span>
                    </Kpi>

                    <Kpi label="Retraits" value={f('withdrawals_requested')}>
                        <span className="font-semibold text-amber">{f('withdrawals_processing')} à traiter</span>
                        {' · '}
                        {f('withdrawals_paid')} payés
                        {stats.withdrawals_failed > 0 && (
                            <span className="text-rust"> · {f('withdrawals_failed')} échoués</span>
                        )}
                    </Kpi>
                </div>
            </section>

            {/* RETRAITS À TRAITER & DERNIERS AFFILIÉS */}
            <div className="mt-10 grid gap-10 xl:grid-cols-2">
                <section className="min-w-0">
                    <SectionHead title="Retraits à traiter" href={route('admin.withdrawals.index')} linkLabel="Tout voir" />
                    <div className="overflow-x-auto rounded-2xl border border-night-line bg-night-2">
                        {pendingWithdrawals && pendingWithdrawals.length > 0 ? (
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-night-line text-xs uppercase tracking-[0.08em] text-night-ink-2">
                                    <tr>
                                        <th className={TH}>Référence</th>
                                        <th className={TH}>Affilié</th>
                                        <th className={`${TH} text-right`}>Montant</th>
                                        <th className={TH}>Statut</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-night-line">
                                    {pendingWithdrawals.map((w) => (
                                        <tr key={w.id || w.reference} className="hover:bg-night-line/40">
                                            <td className={`${TD} font-mono text-night-ink`}>{w.reference}</td>
                                            <td className={`${TD} text-night-ink-2`}>
                                                <span className="font-mono">{w.user?.affiliate_id || '—'}</span>
                                                <span className="block text-xs">{w.method}</span>
                                            </td>
                                            <td className={`${TD} whitespace-nowrap text-right font-semibold text-night-ink`}>
                                                {formatNumber(w.amount)} {w.currency || 'FCFA'}
                                            </td>
                                            <td className={TD}>
                                                <span className="inline-flex items-center gap-1.5 rounded-full bg-amber/25 px-2.5 py-0.5 text-xs font-semibold text-amber">
                                                    <span className="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                                    En cours
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        ) : (
                            <p className="px-4 py-10 text-center text-sm text-night-ink-2">Aucun retrait en attente.</p>
                        )}
                    </div>
                </section>

                <section className="min-w-0">
                    <SectionHead title="Derniers affiliés" href={route('admin.affiliates.index')} linkLabel="Tout voir" />
                    <div className="overflow-x-auto rounded-2xl border border-night-line bg-night-2">
                        {recentAffiliates && recentAffiliates.length > 0 ? (
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-night-line text-xs uppercase tracking-[0.08em] text-night-ink-2">
                                    <tr>
                                        <th className={TH}>Nom</th>
                                        <th className={TH}>Identifiant</th>
                                        <th className={TH}>Statut</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-night-line">
                                    {recentAffiliates.map((a) => (
                                        <tr key={a.id} className="hover:bg-night-line/40">
                                            <td className={TD}>
                                                <Link
                                                    href={route('admin.affiliates.show', a.id)}
                                                    className="font-semibold text-night-ink hover:text-accent-2"
                                                >
                                                    {[a.first_name, a.last_name].filter(Boolean).join(' ') || 'Affilié'}
                                                </Link>
                                            </td>
                                            <td className={`${TD} font-mono text-night-ink-2`}>{a.affiliate_id}</td>
                                            <td className={TD}>
                                                {a.status === 'active' ? (
                                                    <span className="inline-flex items-center gap-1.5 rounded-full bg-sage-soft px-2.5 py-0.5 text-xs font-semibold text-sage">
                                                        <span className="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                                        Actif
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1.5 rounded-full bg-rust-tint px-2.5 py-0.5 text-xs font-semibold text-rust">
                                                        <span className="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                                                        {a.status === 'suspended' ? 'Suspendu' : a.status || 'Inactif'}
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        ) : (
                            <p className="px-4 py-10 text-center text-sm text-night-ink-2">Aucun affilié enregistré.</p>
                        )}
                    </div>
                </section>
            </div>

            {/* ACTIVITÉ RÉCENTE (JOURNAL D'AUDIT) */}
            <section className="mt-10">
                <SectionHead title="Activité récente" href={route('admin.audit-logs.index')} linkLabel="Journal complet" />
                <div className="overflow-x-auto rounded-2xl border border-night-line bg-night-2">
                    {recentAudit && recentAudit.length > 0 ? (
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-night-line text-xs uppercase tracking-[0.08em] text-night-ink-2">
                                <tr>
                                    <th className={TH}>Action</th>
                                    <th className={TH}>Par</th>
                                    <th className={`${TH} text-right`}>Date</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-night-line">
                                {recentAudit.map((log) => (
                                    <tr key={log.id} className="align-top hover:bg-night-line/40">
                                        <td className={`${TD} min-w-[240px]`}>
                                            <p className="text-night-ink">{log.description}</p>
                                            <p className="mt-0.5 font-mono text-xs text-night-ink-2">{log.action}</p>
                                        </td>
                                        <td className={`${TD} whitespace-nowrap text-night-ink-2`}>{log.admin?.first_name || 'Admin'}</td>
                                        <td className={`${TD} whitespace-nowrap text-right text-night-ink-2`}>{formatDateTime(log.created_at)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    ) : (
                        <p className="px-4 py-10 text-center text-sm text-night-ink-2">Aucune action enregistrée.</p>
                    )}
                </div>
            </section>
        </AdminLayout>
    );
}
