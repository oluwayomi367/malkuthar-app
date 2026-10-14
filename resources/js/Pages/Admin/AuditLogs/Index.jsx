import React from 'react';
import { router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({
    logs = { data: [] },
    actions = [],
    action = '',
    heading = "Journal d'audit",
    eyebrow = 'Traçabilité',
}) {
    const handleActionChange = (e) => {
        const val = e.target.value;
        router.get(
            route('admin.audit-logs.index'),
            val ? { action: val } : {},
            { preserveState: true, preserveScroll: true }
        );
    };

    return (
        <AdminLayout title="Journal d'audit" eyebrow={eyebrow} heading={heading}>
            {/* FILTRE PAR ACTION */}
            <div className="mb-6 flex flex-wrap items-center gap-3">
                <select
                    value={action || ''}
                    onChange={handleActionChange}
                    className="rounded-xl border border-night-ink-2/60 bg-night px-3.5 py-2.5 text-sm font-medium text-night-ink focus:border-accent-light focus:outline-none min-h-11"
                >
                    <option value="">Toutes les actions</option>
                    {actions.map((a) => (
                        <option key={a} value={a}>
                            {a}
                        </option>
                    ))}
                </select>
                {action && (
                    <button
                        type="button"
                        onClick={() => router.get(route('admin.audit-logs.index'))}
                        className="text-xs text-night-ink-2 hover:text-night-ink underline"
                    >
                        Réinitialiser
                    </button>
                )}
            </div>

            {/* LISTE DES JOURNAUX */}
            <div className="overflow-hidden rounded-2xl border border-night-line bg-night-2">
                {logs.data && logs.data.length > 0 ? (
                    <div className="divide-y divide-night-line">
                        {logs.data.map((log) => (
                            <div
                                key={log.id}
                                className="flex flex-wrap items-start justify-between gap-4 px-5 py-4 text-sm transition-colors hover:bg-night-line/50"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="font-semibold text-night-ink">{log.description}</p>
                                    <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-sm text-night-ink-2">
                                        <span className="rounded bg-night-line px-1.5 py-0.5 font-bold text-night-ink-2">
                                            {log.action}
                                        </span>
                                        {log.ip_address && <span>IP : {log.ip_address}</span>}
                                        {log.subject_type && (
                                            <span>
                                                {log.subject_type.split('\\').pop()} #{String(log.subject_id).slice(0, 8)}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <div className="shrink-0 text-right text-night-ink-2">
                                    <p className="font-medium text-night-ink">
                                        {log.admin ? `${log.admin.first_name} ${log.admin.last_name}` : 'Système'}
                                    </p>
                                    <p className="mt-0.5 text-xs text-night-ink-2">
                                        {log.created_at
                                            ? new Date(log.created_at).toLocaleDateString('fr-FR', {
                                                  day: '2-digit',
                                                  month: '2-digit',
                                                  year: 'numeric',
                                                  hour: '2-digit',
                                                  minute: '2-digit',
                                              })
                                            : ''}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="px-5 py-12 text-center text-sm text-night-ink-2">
                        Aucune action enregistrée pour ce filtre.
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
