import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({
    affiliates = { data: [] },
    q = '',
    heading = 'Affiliés',
    eyebrow = 'Gestion',
}) {
    const [search, setSearch] = useState(q || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route('admin.affiliates.index'),
            search ? { q: search } : {},
            { preserveState: true }
        );
    };

    return (
        <AdminLayout title="Affiliés" eyebrow={eyebrow} heading={heading}>
            {/* RECHERCHE */}
            <form onSubmit={handleSearch} className="mb-6 flex gap-2">
                <input
                    type="search"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Nom, email, identifiant, code de parrainage…"
                    className="flex-1 rounded-xl border border-night-ink-2/60 bg-night px-4 py-2.5 text-sm text-night-ink placeholder:text-night-ink-2/70 focus:border-accent-light focus:outline-none min-h-11"
                />
                <button
                    type="submit"
                    className="rounded-full bg-accent-light px-5 py-2.5 text-sm font-semibold text-night hover:bg-accent-light/85 min-h-11"
                >
                    Rechercher
                </button>
                {q && (
                    <button
                        type="button"
                        onClick={() => {
                            setSearch('');
                            router.get(route('admin.affiliates.index'));
                        }}
                        className="rounded-full border border-night-ink-2/60 bg-night-2 px-3 py-2.5 text-sm font-medium text-night-ink-2 hover:bg-night-line/50 min-h-11"
                    >
                        Réinitialiser
                    </button>
                )}
            </form>

            {/* TABLEAU DES AFFILIÉS */}
            <div className="overflow-x-auto rounded-2xl border border-night-line bg-night-2">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-night-line text-xs font-semibold uppercase tracking-[0.08em] text-night-ink-2">
                        <tr>
                            <th className="px-4 py-3">Affilié</th>
                            <th className="px-4 py-3">Identifiant</th>
                            <th className="px-4 py-3">Filleuls N1</th>
                            <th className="px-4 py-3">Statut</th>
                            <th className="px-4 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-night-line">
                        {affiliates.data && affiliates.data.length > 0 ? (
                            affiliates.data.map((a) => (
                                <tr key={a.id} className="hover:bg-night-line/50">
                                    <td className="px-4 py-3.5">
                                        <p className="font-semibold text-night-ink">
                                            {[a.first_name, a.last_name].filter(Boolean).join(' ') || 'Affilié'}
                                        </p>
                                        <p className="text-xs text-night-ink-2">{a.email}</p>
                                    </td>
                                    <td className="px-4 py-3.5 font-mono text-night-ink-2">
                                        <div>{a.affiliate_id}</div>
                                        {a.code_parrainage && (
                                            <div className="text-sm text-night-ink-2">{a.code_parrainage}</div>
                                        )}
                                    </td>
                                    <td className="px-4 py-3.5 font-semibold text-night-ink-2">
                                        {a.children_count || 0}
                                    </td>
                                    <td className="px-4 py-3.5">
                                        {a.status === 'active' ? (
                                            <span className="rounded-full bg-sage/30 px-2.5 py-0.5 text-xs font-semibold text-sage-soft">
                                                Actif
                                            </span>
                                        ) : (
                                            <span className="rounded-full bg-rust/20 px-2.5 py-0.5 text-xs font-semibold text-rust-tint">
                                                {a.status || 'Inactif'}
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3.5 text-right">
                                        <Link
                                            href={route('admin.affiliates.show', a.id)}
                                            className="font-semibold text-accent-light hover:text-night-ink"
                                        >
                                            Ouvrir
                                        </Link>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan="5" className="px-4 py-12 text-center text-night-ink-2">
                                    {q ? 'Aucun résultat pour cette recherche.' : 'Aucun affilié enregistré.'}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
