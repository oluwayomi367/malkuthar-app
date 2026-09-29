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
                    className="flex-1 rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-xs text-zinc-900 shadow-2xs placeholder:text-zinc-400 focus:border-accent focus:outline-none"
                />
                <button
                    type="submit"
                    className="rounded-xl bg-zinc-900 px-5 py-2.5 text-xs font-semibold text-white shadow-2xs hover:bg-zinc-800"
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
                        className="rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50"
                    >
                        Réinitialiser
                    </button>
                )}
            </form>

            {/* TABLEAU DES AFFILIÉS */}
            <div className="overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-xs">
                <table className="w-full text-left text-xs">
                    <thead className="border-b border-zinc-200 bg-zinc-50 text-[10px] font-bold uppercase tracking-wider text-zinc-500">
                        <tr>
                            <th className="px-4 py-3">Affilié</th>
                            <th className="px-4 py-3">Identifiant</th>
                            <th className="px-4 py-3">Filleuls N1</th>
                            <th className="px-4 py-3">Statut</th>
                            <th className="px-4 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-zinc-100">
                        {affiliates.data && affiliates.data.length > 0 ? (
                            affiliates.data.map((a) => (
                                <tr key={a.id} className="hover:bg-zinc-50/60">
                                    <td className="px-4 py-3.5">
                                        <p className="font-semibold text-zinc-900">
                                            {[a.first_name, a.last_name].filter(Boolean).join(' ') || 'Affilié'}
                                        </p>
                                        <p className="text-[11px] text-zinc-400">{a.email}</p>
                                    </td>
                                    <td className="px-4 py-3.5 font-mono text-zinc-700">
                                        <div>{a.affiliate_id}</div>
                                        {a.code_parrainage && (
                                            <div className="text-[11px] text-zinc-400">{a.code_parrainage}</div>
                                        )}
                                    </td>
                                    <td className="px-4 py-3.5 font-semibold text-zinc-700">
                                        {a.children_count || 0}
                                    </td>
                                    <td className="px-4 py-3.5">
                                        {a.status === 'active' ? (
                                            <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-800">
                                                Actif
                                            </span>
                                        ) : (
                                            <span className="rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-semibold text-rose-800">
                                                {a.status || 'Inactif'}
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3.5 text-right">
                                        <Link
                                            href={route('admin.affiliates.show', a.id)}
                                            className="font-semibold text-accent hover:text-accent-2"
                                        >
                                            Ouvrir →
                                        </Link>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan="5" className="px-4 py-12 text-center text-zinc-500">
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
