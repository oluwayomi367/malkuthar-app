import React from 'react';
import { useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({
    coupons = { data: [] },
    heading = 'Coupons',
    eyebrow = 'Marketing',
}) {
    const form = useForm({
        code: '',
        type: 'percent',
        value: '',
        max_uses: '',
        starts_at: '',
        expires_at: '',
        is_active: true,
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('admin.coupons.store'), {
            onSuccess: () => form.reset(),
        });
    };

    const handleDeactivate = (couponId, couponCode) => {
        if (confirm(`Désactiver le coupon « ${couponCode} » ?`)) {
            router.delete(route('admin.coupons.destroy', couponId));
        }
    };

    const formatNumber = (num) => {
        if (num === null || num === undefined) return '0';
        return Number(num).toLocaleString('fr-FR');
    };

    return (
        <AdminLayout title="Coupons" eyebrow={eyebrow} heading={heading}>
            <div className="grid gap-8 lg:grid-cols-[1fr_340px]">
                {/* LISTE DES COUPONS */}
                <div className="space-y-4">
                    <div className="overflow-x-auto rounded-2xl border border-night-line bg-night-2">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-night-line text-xs font-semibold uppercase tracking-[0.08em] text-night-ink-2">
                                <tr>
                                    <th className="px-4 py-3">Code</th>
                                    <th className="px-4 py-3">Remise</th>
                                    <th className="px-4 py-3">Utilisations</th>
                                    <th className="px-4 py-3">Statut</th>
                                    <th className="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-night-line">
                                {coupons.data && coupons.data.length > 0 ? (
                                    coupons.data.map((c) => (
                                        <tr key={c.id} className="hover:bg-night-line/50">
                                            <td className="px-4 py-3 font-mono font-bold text-night-ink">
                                                {c.code}
                                            </td>
                                            <td className="px-4 py-3 font-semibold text-night-ink">
                                                {c.type === 'percent'
                                                    ? `${formatNumber(c.value)} %`
                                                    : `${formatNumber(c.value)} FCFA`}
                                            </td>
                                            <td className="px-4 py-3 text-night-ink-2">
                                                {c.used_count || 0}
                                                {c.max_uses ? ` / ${c.max_uses}` : ''}
                                            </td>
                                            <td className="px-4 py-3">
                                                {c.is_active ? (
                                                    <span className="rounded-full bg-sage/30 px-2.5 py-0.5 text-xs font-semibold text-sage-soft">
                                                        Actif
                                                    </span>
                                                ) : (
                                                    <span className="rounded-full bg-night-line px-2.5 py-0.5 text-xs font-semibold text-night-ink-2">
                                                        Inactif
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {c.is_active && (
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDeactivate(c.id, c.code)}
                                                        className="font-semibold text-rust-tint hover:text-rust-tint hover:underline"
                                                    >
                                                        Désactiver
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="5" className="px-4 py-8 text-center text-night-ink-2">
                                            Aucun coupon créé pour le moment.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* FORMULAIRE NOUVEAU COUPON */}
                <div className="rounded-2xl border border-night-line bg-night-2 p-5 h-fit">
                    <h2 className="font-sans text-sm font-semibold text-night-ink">
                        Nouveau coupon
                    </h2>
                    <form onSubmit={submit} className="mt-4 space-y-3.5 text-sm">
                        <div>
                            <label className="font-medium text-night-ink-2">Code promo</label>
                            <input
                                type="text"
                                required
                                placeholder="Ex. BIENVENUE10"
                                value={form.data.code}
                                onChange={(e) => form.setData('code', e.target.value.toUpperCase())}
                                className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-3 py-2.5 font-mono uppercase focus:border-accent-light focus:outline-none min-h-11"
                            />
                            {form.errors.code && <p className="mt-1 text-rust-tint">{form.errors.code}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <label className="font-medium text-night-ink-2">Type</label>
                                <select
                                    value={form.data.type}
                                    onChange={(e) => form.setData('type', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-2 py-2.5 focus:border-accent-light focus:outline-none min-h-11"
                                >
                                    <option value="percent">Pourcentage (%)</option>
                                    <option value="fixed">Montant fixe (FCFA)</option>
                                </select>
                            </div>
                            <div>
                                <label className="font-medium text-night-ink-2">Valeur</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="any"
                                    required
                                    placeholder={form.data.type === 'percent' ? 'Ex. 10' : 'Ex. 2000'}
                                    value={form.data.value}
                                    onChange={(e) => form.setData('value', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-2 py-2.5 focus:border-accent-light focus:outline-none min-h-11"
                                />
                                {form.errors.value && <p className="mt-1 text-rust-tint">{form.errors.value}</p>}
                            </div>
                        </div>

                        <div>
                            <label className="font-medium text-night-ink-2">Utilisations max (optionnel)</label>
                            <input
                                type="number"
                                min="1"
                                placeholder="Illimité si vide"
                                value={form.data.max_uses}
                                onChange={(e) => form.setData('max_uses', e.target.value)}
                                className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-3 py-2.5 focus:border-accent-light focus:outline-none min-h-11"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <label className="font-medium text-night-ink-2">Début</label>
                                <input
                                    type="date"
                                    value={form.data.starts_at}
                                    onChange={(e) => form.setData('starts_at', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-2 py-2.5 focus:border-accent-light focus:outline-none min-h-11"
                                />
                            </div>
                            <div>
                                <label className="font-medium text-night-ink-2">Fin</label>
                                <input
                                    type="date"
                                    value={form.data.expires_at}
                                    onChange={(e) => form.setData('expires_at', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-2 py-2.5 focus:border-accent-light focus:outline-none min-h-11"
                                />
                            </div>
                        </div>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="w-full rounded-full bg-accent-light px-4 py-2 font-semibold text-night transition-colors hover:bg-accent-light/85 disabled:opacity-50 min-h-11"
                        >
                            Créer le coupon
                        </button>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
