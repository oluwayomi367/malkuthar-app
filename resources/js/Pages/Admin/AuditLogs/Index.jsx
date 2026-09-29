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
                    className="rounded-xl border border-zinc-300 bg-white px-3.5 py-2 text-xs font-medium text-zinc-800 shadow-2xs focus:border-accent focus:outline-none"
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
                        className="text-xs text-zinc-500 hover:text-zinc-800 underline"
                    >
                        Réinitialiser
                    </button>
                )}
            </div>

            {/* LISTE DES JOURNAUX */}
            <div className="overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-xs">
                {logs.data && logs.data.length > 0 ? (
                    <div className="divide-y divide-zinc-100">
                        {logs.data.map((log) => (
                            <div
                                key={log.id}
                                className="flex flex-wrap items-start justify-between gap-4 px-5 py-4 text-xs transition-colors hover:bg-zinc-50/60"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="font-semibold text-zinc-900">{log.description}</p>
                                    <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-[11px] text-zinc-500">
                                        <span className="rounded bg-zinc-100 px-1.5 py-0.5 font-bold text-zinc-700">
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
                                <div className="shrink-0 text-right text-zinc-500">
                                    <p className="font-medium text-zinc-800">
                                        {log.admin ? `${log.admin.first_name} ${log.admin.last_name}` : 'Système'}
                                    </p>
                                    <p className="mt-0.5 text-[11px] text-zinc-400">
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
                    <div className="px-5 py-12 text-center text-xs text-zinc-500">
                        Aucune action enregistrée pour ce filtre.
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
