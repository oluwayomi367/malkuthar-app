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
            className="rounded-full bg-accent px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-accent-2 min-h-11 inline-flex items-center justify-center"
        >
            + Nouveau produit
        </Link>
    );

    return (
        <AdminLayout title="Produits" eyebrow={eyebrow} heading={heading} actions={headerActions}>
            {/* ALERTE STOCK FAIBLE */}
            {lowStock && lowStock.length > 0 && (
                <div className="mb-6 rounded-2xl border border-amber bg-amber/25 p-4">
                    <p className="text-xs font-bold uppercase tracking-wider text-amber">
                        Alerte Stock faible
                    </p>
                    <ul className="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-sm text-amber">
                        {lowStock.map((p) => (
                            <li key={p.id}>
                                <Link href={route('admin.products.edit', p.id)} className="hover:underline">
                                    {p.name} — <strong className="text-amber">{p.stock_available}</strong> restant(s)
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
                    className="flex-1 min-w-[220px] rounded-xl border border-night-ink-2/60 bg-night px-3.5 py-2.5 text-sm text-night-ink placeholder:text-night-ink-2/70 focus:border-accent focus:outline-none min-h-11"
                />

                <select
                    value={type || ''}
                    onChange={(e) => handleFilter(e.target.value, undefined)}
                    className="rounded-xl border border-night-ink-2/60 bg-night px-3 py-2.5 text-sm text-night-ink-2 focus:border-accent focus:outline-none min-h-11"
                >
                    <option value="">Tous les types</option>
                    <option value="digital">Digital</option>
                    <option value="physical">Physique</option>
                    <option value="service">Service</option>
                </select>

                <select
                    value={status || ''}
                    onChange={(e) => handleFilter(undefined, e.target.value)}
                    className="rounded-xl border border-night-ink-2/60 bg-night px-3 py-2.5 text-sm text-night-ink-2 focus:border-accent focus:outline-none min-h-11"
                >
                    <option value="">Tous les statuts</option>
                    <option value="published">Publié</option>
                    <option value="draft">Brouillon</option>
                    <option value="archived">Archivé</option>
                </select>

                <button
                    type="submit"
                    className="rounded-full bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-2 min-h-11"
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
                        className="text-xs text-night-ink-2 hover:text-night-ink underline"
                    >
                        Réinitialiser
                    </button>
                )}
            </form>

            {/* TABLEAU DES PRODUITS */}
            <div className="overflow-x-auto rounded-2xl border border-night-line bg-night-2">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-night-line text-xs font-semibold uppercase tracking-[0.08em] text-night-ink-2">
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
                    <tbody className="divide-y divide-night-line">
                        {products.data && products.data.length > 0 ? (
                            products.data.map((p) => {
                                const rep = reports[p.id] || {};
                                return (
                                    <tr key={p.id} className="hover:bg-night-line/50">
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-3">
                                                <div className="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-night-line bg-night-line">
                                                    {p.main_image ? (
                                                        <img
                                                            src={p.main_image}
                                                            alt=""
                                                            className="h-full w-full object-cover"
                                                        />
                                                    ) : (
                                                        <div className="flex h-full w-full items-center justify-center font-bold text-night-ink-2">
                                                            M
                                                        </div>
                                                    )}
                                                </div>
                                                <div className="min-w-0">
                                                    <Link
                                                        href={route('admin.products.show', p.id)}
                                                        className="block truncate font-semibold text-night-ink hover:text-accent-2"
                                                    >
                                                        {p.name}
                                                    </Link>
                                                    <span className="block truncate text-xs text-night-ink-2 font-mono">
                                                        {p.sku || p.slug}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="rounded bg-night-line px-2 py-0.5 text-xs font-medium text-night-ink-2">
                                                {typeLabels[p.type] || p.type}
                                            </span>
                                            {p.is_affiliable && (
                                                <span className="ml-1 rounded bg-accent-soft px-1.5 py-0.5 text-xs font-bold text-accent-2">
                                                    Affiliable
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 font-semibold text-night-ink">
                                            {formatNumber(p.promotional_price || p.price)} {p.currency || 'FCFA'}
                                            {p.promotional_price && (
                                                <span className="block text-xs text-night-ink-2 line-through">
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
                                                            p.stock_available <= 5 ? 'text-rust' : 'text-night-ink',
                                                        ].join(' ')}
                                                    >
                                                        {p.stock_available}
                                                    </span>
                                                    <span className="text-night-ink-2 text-xs"> dispo</span>
                                                </div>
                                            ) : (
                                                <span className="text-night-ink-2 text-xs">—</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 font-medium text-night-ink">
                                            {rep.units_sold || 0}
                                        </td>
                                        <td className="px-4 py-3 font-mono text-night-ink-2">
                                            {formatNumber(rep.commissions_amount || 0)}
                                        </td>
                                        <td className="px-4 py-3">
                                            {p.status === 'published' ? (
                                                <span className="rounded-full bg-sage-soft px-2.5 py-0.5 text-xs font-semibold text-sage">
                                                    Publié
                                                </span>
                                            ) : p.status === 'draft' ? (
                                                <span className="rounded-full bg-night-line px-2.5 py-0.5 text-xs font-semibold text-night-ink-2">
                                                    Brouillon
                                                </span>
                                            ) : (
                                                <span className="rounded-full bg-rust-tint px-2.5 py-0.5 text-xs font-semibold text-rust">
                                                    Archivé
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link
                                                    href={route('admin.products.edit', p.id)}
                                                    className="font-medium text-night-ink-2 hover:text-night-ink hover:underline"
                                                >
                                                    Modifier
                                                </Link>
                                                <Link
                                                    href={route('admin.products.show', p.id)}
                                                    className="font-semibold text-accent-2 hover:text-night-ink"
                                                >
                                                    Voir
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })
                        ) : (
                            <tr>
                                <td colSpan="8" className="px-4 py-12 text-center text-night-ink-2">
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
