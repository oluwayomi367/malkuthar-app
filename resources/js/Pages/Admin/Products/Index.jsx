import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({
    products = { data: [] },
    lowStock = [],
    reports = {},
    type = '',
    status = '',
    q = '',
    actions = {},
    columns = {},
    heading = 'Produits',
    eyebrow = 'Catalogue',
}) {
    const [search, setSearch] = useState(q || '');

    const handleFilter = (newType, newStatus) => {
        router.get(
            route('admin.products.index'),
            {
                q: search,
                type: newType !== undefined ? newType : type,
                status: newStatus !== undefined ? newStatus : status,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        handleFilter(type, status);
    };

    const formatNumber = (num) => {
        if (num === null || num === undefined) return '0';
        return Number(num).toLocaleString('fr-FR');
    };

    const typeLabels = {
        digital: 'Digital',
        physical: 'Physique',
        service: 'Service',
    };

    const headerActions = (
        <Link
            href={route('admin.products.create')}
            className="rounded-xl bg-accent px-4 py-2 text-xs font-bold text-white shadow-xs transition-colors hover:bg-accent-2"
        >
            + Nouveau produit
        </Link>
    );

    return (
        <AdminLayout title="Produits" eyebrow={eyebrow} heading={heading} actions={headerActions}>
            {/* ALERTE STOCK FAIBLE */}
            {lowStock && lowStock.length > 0 && (
                <div className="mb-6 rounded-2xl border border-amber-300 bg-amber-50/70 p-4 shadow-2xs">
                    <p className="text-[11px] font-bold uppercase tracking-wider text-amber-800">
                        Alerte Stock faible
                    </p>
                    <ul className="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-xs text-amber-900">
                        {lowStock.map((p) => (
                            <li key={p.id}>
                                <Link href={route('admin.products.edit', p.id)} className="hover:underline">
                                    {p.name} — <strong className="text-amber-800">{p.stock_available}</strong> restant(s)
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            {/* RECHERCHE ET FILTRES */}
            <form onSubmit={handleSearch} className="mb-6 flex flex-wrap items-center gap-3">
                <input
                    type="search"
                    placeholder="Nom, SKU, slug…"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="flex-1 min-w-[220px] rounded-xl border border-zinc-300 bg-white px-3.5 py-2 text-xs text-zinc-900 shadow-2xs placeholder:text-zinc-400 focus:border-accent focus:outline-none"
                />

                <select
                    value={type || ''}
                    onChange={(e) => handleFilter(e.target.value, undefined)}
                    className="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-700 shadow-2xs focus:border-accent focus:outline-none"
                >
                    <option value="">Tous les types</option>
                    <option value="digital">Digital</option>
                    <option value="physical">Physique</option>
                    <option value="service">Service</option>
                </select>

                <select
                    value={status || ''}
                    onChange={(e) => handleFilter(undefined, e.target.value)}
                    className="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-700 shadow-2xs focus:border-accent focus:outline-none"
                >
                    <option value="">Tous les statuts</option>
                    <option value="published">Publié</option>
                    <option value="draft">Brouillon</option>
                    <option value="archived">Archivé</option>
                </select>

                <button
                    type="submit"
                    className="rounded-xl bg-zinc-900 px-4 py-2 text-xs font-semibold text-white shadow-2xs hover:bg-zinc-800"
                >
                    Filtrer
                </button>
                {(q || type || status) && (
                    <button
                        type="button"
                        onClick={() => {
                            setSearch('');
                            router.get(route('admin.products.index'));
                        }}
                        className="text-xs text-zinc-500 hover:text-zinc-800 underline"
                    >
                        Réinitialiser
                    </button>
                )}
            </form>

            {/* TABLEAU DES PRODUITS */}
            <div className="overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-xs">
                <table className="w-full text-left text-xs">
                    <thead className="border-b border-zinc-200 bg-zinc-50 text-[10px] font-bold uppercase tracking-wider text-zinc-500">
                        <tr>
                            <th className="px-4 py-3">Produit</th>
                            <th className="px-4 py-3">Type</th>
                            <th className="px-4 py-3">Prix</th>
                            <th className="px-4 py-3">{columns.stock || 'Stock'}</th>
                            <th className="px-4 py-3">{columns.sales || 'Ventes'}</th>
                            <th className="px-4 py-3">{columns.commissions || 'Commissions'}</th>
                            <th className="px-4 py-3">Statut</th>
                            <th className="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-zinc-100">
                        {products.data && products.data.length > 0 ? (
                            products.data.map((p) => {
                                const rep = reports[p.id] || {};
                                return (
                                    <tr key={p.id} className="hover:bg-zinc-50/60">
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-3">
                                                <div className="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100">
                                                    {p.main_image ? (
                                                        <img
                                                            src={p.main_image}
                                                            alt=""
                                                            className="h-full w-full object-cover"
                                                        />
                                                    ) : (
                                                        <div className="flex h-full w-full items-center justify-center font-bold text-zinc-300">
                                                            M
                                                        </div>
                                                    )}
                                                </div>
                                                <div className="min-w-0">
                                                    <Link
                                                        href={route('admin.products.show', p.id)}
                                                        className="block truncate font-semibold text-zinc-900 hover:text-accent"
                                                    >
                                                        {p.name}
                                                    </Link>
                                                    <span className="block truncate text-[11px] text-zinc-400 font-mono">
                                                        {p.sku || p.slug}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="rounded bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-700">
                                                {typeLabels[p.type] || p.type}
                                            </span>
                                            {p.is_affiliable && (
                                                <span className="ml-1 rounded bg-accent/15 px-1.5 py-0.5 text-[9px] font-bold text-accent">
                                                    Affiliable
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 font-semibold text-zinc-900">
                                            {formatNumber(p.promotional_price || p.price)} {p.currency || 'FCFA'}
                                            {p.promotional_price && (
                                                <span className="block text-[10px] text-zinc-400 line-through">
                                                    {formatNumber(p.price)}
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            {p.type === 'physical' ? (
                                                <div>
                                                    <span
                                                        className={[
                                                            'font-bold',
                                                            p.stock_available <= 5 ? 'text-rose-600' : 'text-zinc-900',
                                                        ].join(' ')}
                                                    >
                                                        {p.stock_available}
                                                    </span>
                                                    <span className="text-zinc-400 text-[11px]"> dispo</span>
                                                </div>
                                            ) : (
                                                <span className="text-zinc-400 text-[11px]">—</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 font-medium text-zinc-800">
                                            {rep.units_sold || 0}
                                        </td>
                                        <td className="px-4 py-3 font-mono text-zinc-700">
                                            {formatNumber(rep.commissions_amount || 0)}
                                        </td>
                                        <td className="px-4 py-3">
                                            {p.status === 'published' ? (
                                                <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-800">
                                                    Publié
                                                </span>
                                            ) : p.status === 'draft' ? (
                                                <span className="rounded-full bg-zinc-100 px-2.5 py-0.5 text-[10px] font-semibold text-zinc-600">
                                                    Brouillon
                                                </span>
                                            ) : (
                                                <span className="rounded-full bg-rose-100 px-2.5 py-0.5 text-[10px] font-semibold text-rose-800">
                                                    Archivé
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link
                                                    href={route('admin.products.edit', p.id)}
                                                    className="font-medium text-zinc-600 hover:text-zinc-900 hover:underline"
                                                >
                                                    Modifier
                                                </Link>
                                                <Link
                                                    href={route('admin.products.show', p.id)}
                                                    className="font-semibold text-accent hover:text-accent-2"
                                                >
                                                    Voir →
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })
                        ) : (
                            <tr>
                                <td colSpan="8" className="px-4 py-12 text-center text-zinc-500">
                                    Aucun produit ne correspond aux filtres.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
