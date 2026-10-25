import React, { useState } from 'react';
import { Link, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({ categories = [], tree = [], editing = null }) {
    const types = [
        { id: 'physical', label: 'Physique' },
        { id: 'digital', label: 'Digital' },
        { id: 'service', label: 'Service' },
        { id: 'training', label: 'Formation' },
    ];

    const fieldTypes = [
        { id: 'text', label: 'Texte' },
        { id: 'textarea', label: 'Texte long' },
        { id: 'number', label: 'Nombre' },
        { id: 'boolean', label: 'Oui/Non' },
        { id: 'select', label: 'Liste (choix unique)' },
        { id: 'multiselect', label: 'Liste (choix multiples)' },
        { id: 'date', label: 'Date' },
        { id: 'url', label: 'Lien' },
        { id: 'file', label: 'Fichier' },
    ];

    const pct = (r) => (r === null || r === undefined ? '' : String(parseFloat((Number(r) * 100).toFixed(2))));

    // Category form
    const initialRuleset = editing?.ruleset || {
        require_approval: true,
        requires_shipping: false,
        allow_variants: false,
        min_price: '',
        max_price: '',
        max_images: '',
    };

    const categoryForm = useForm({
        name: editing?.name || '',
        parent_id: editing?.parent_id || '',
        description: editing?.description || '',
        applicable_offer_types: editing?.applicable_offer_types || ['physical', 'digital', 'service', 'training'],
        commission_rate_percent: pct(editing?.commission_rate),
        position: editing?.position || 0,
        is_active: editing ? Boolean(editing.is_active) : true,
        ruleset: {
            require_approval: initialRuleset.require_approval ?? true,
            requires_shipping: initialRuleset.requires_shipping ?? false,
            allow_variants: initialRuleset.allow_variants ?? false,
            min_price: initialRuleset.min_price || '',
            max_price: initialRuleset.max_price || '',
            max_images: initialRuleset.max_images || '',
        },
    });

    const handleCategorySubmit = (e) => {
        e.preventDefault();
        if (editing) {
            categoryForm.put(route('admin.marketplace.categories.update', editing.id));
        } else {
            categoryForm.post(route('admin.marketplace.categories.store'));
        }
    };

    const handleDeleteCategory = () => {
        if (!editing) return;
        if (confirm(`Supprimer la catégorie « ${editing.name} » ?`)) {
            router.delete(route('admin.marketplace.categories.destroy', editing.id));
        }
    };

    const toggleOfferType = (typeId) => {
        const cur = categoryForm.data.applicable_offer_types;
        if (cur.includes(typeId)) {
            categoryForm.setData(
                'applicable_offer_types',
                cur.filter((t) => t !== typeId)
            );
        } else {
            categoryForm.setData('applicable_offer_types', [...cur, typeId]);
        }
    };

    // Custom field form
    const fieldForm = useForm({
        label: '',
        type: 'text',
        key: '',
        options_text: '',
        is_required: false,
        is_filterable: false,
        is_variant_axis: false,
    });

    const handleFieldSubmit = (e) => {
        e.preventDefault();
        if (!editing) return;
        fieldForm.post(route('admin.marketplace.categories.fields.store', editing.id), {
            onSuccess: () => fieldForm.reset('label', 'key', 'options_text', 'is_required', 'is_filterable', 'is_variant_axis'),
        });
    };

    const handleDeleteField = (fieldId) => {
        if (!editing) return;
        if (confirm('Supprimer ce champ personnalisé ?')) {
            router.delete(route('admin.marketplace.categories.fields.destroy', [editing.id, fieldId]));
        }
    };

    return (
        <AdminLayout
            title="Catégories Marketplace"
            eyebrow="Marketplace"
            heading="Catalogue — catégories"
        >
            <div className="grid gap-6 lg:grid-cols-[1fr_1.3fr]">
                {/* ============ ARBRE DES CATEGORIES ============ */}
                <div>
                    <div className="rounded-2xl border border-night-line bg-night-2 p-5">
                        <div className="flex items-center justify-between pb-3 border-b border-night-line">
                            <h2 className="font-sans text-base font-semibold text-night-ink">
                                Arbre ({categories.length})
                            </h2>
                            <Link
                                href={route('admin.marketplace.categories.index')}
                                className="text-xs font-semibold text-accent-2 hover:underline"
                            >
                                + Nouvelle racine
                            </Link>
                        </div>

                        {tree.length === 0 ? (
                            <p className="mt-4 text-sm text-night-ink-2">
                                Aucune catégorie. Créez-en une avec le formulaire.
                            </p>
                        ) : (
                            <ul className="mt-3 space-y-1 text-sm">
                                {tree.map((row) => {
                                    const cat = row.category;
                                    const isSelected = editing && editing.id === cat.id;
                                    return (
                                        <li key={cat.id}>
                                            <Link
                                                href={route('admin.marketplace.categories.index', { edit: cat.id })}
                                                preserveScroll
                                                className={`flex items-center justify-between rounded-xl px-2.5 py-2 transition-colors ${
                                                    isSelected
                                                        ? 'bg-accent-soft font-bold text-accent-2'
                                                        : 'text-night-ink-2 hover:bg-night-line hover:text-night-ink'
                                                }`}
                                                style={{ paddingLeft: `${0.5 + row.depth * 1.1}rem` }}
                                            >
                                                <span className="flex items-center gap-1.5">
                                                    {row.depth > 0 && <span className="text-night-ink-2">└</span>}
                                                    {cat.name}
                                                    {!cat.is_active && (
                                                        <span className="rounded bg-rust-tint px-1.5 py-0.5 text-xs font-bold uppercase text-rust">
                                                            masquée
                                                        </span>
                                                    )}
                                                </span>
                                                <span className="text-xs font-medium text-night-ink-2">
                                                    {cat.fields_count ?? (cat.fields ? cat.fields.length : 0)} ch.
                                                </span>
                                            </Link>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </div>
                </div>

                {/* ============ FORMULAIRE CATEGORIE & CHAMPS ============ */}
                <div className="space-y-6">
                    <form
                        onSubmit={handleCategorySubmit}
                        className="rounded-2xl border border-night-line bg-night-2 p-6 "
                    >
                        <div className="flex items-center justify-between pb-3 border-b border-night-line">
                            <h2 className="font-sans text-base font-semibold text-night-ink">
                                {editing ? 'Modifier la catégorie' : 'Nouvelle catégorie'}
                            </h2>
                            {editing && (
                                <button
                                    type="button"
                                    onClick={handleDeleteCategory}
                                    className="text-xs font-semibold text-rust hover:underline"
                                >
                                    Supprimer
                                </button>
                            )}
                        </div>

                        <div className="mt-4 grid gap-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-night-ink-2">Nom *</label>
                                    <input
                                        type="text"
                                        required
                                        value={categoryForm.data.name}
                                        onChange={(e) => categoryForm.setData('name', e.target.value)}
                                        className="mt-1 h-11 w-full rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink focus:border-accent focus:outline-none min-h-11"
                                    />
                                    {categoryForm.errors.name && (
                                        <p className="mt-1 text-xs text-rust">{categoryForm.errors.name}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-night-ink-2">Parent</label>
                                    <select
                                        value={categoryForm.data.parent_id}
                                        onChange={(e) => categoryForm.setData('parent_id', e.target.value)}
                                        className="mt-1 h-10 w-full rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink focus:border-accent focus:outline-none min-h-11"
                                    >
                                        <option value="">— racine —</option>
                                        {categories.map((c) => {
                                            if (editing && c.id === editing.id) return null;
                                            return (
                                                <option key={c.id} value={c.id}>
                                                    {'— '.repeat(c.depth || 0)}
                                                    {c.name}
                                                </option>
                                            );
                                        })}
                                    </select>
                                    {categoryForm.errors.parent_id && (
                                        <p className="mt-1 text-xs text-rust">{categoryForm.errors.parent_id}</p>
                                    )}
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-night-ink-2">Description</label>
                                <textarea
                                    rows="2"
                                    value={categoryForm.data.description}
                                    onChange={(e) => categoryForm.setData('description', e.target.value)}
                                    className="mt-1 w-full rounded-xl border border-night-ink-2/60 bg-night px-3 py-2.5 text-sm text-night-ink focus:border-accent focus:outline-none"
                                />
                            </div>

                            <div>
                                <span className="block text-xs font-medium text-night-ink-2">Types d'offres admis *</span>
                                <div className="mt-2 flex flex-wrap gap-4">
                                    {types.map((t) => (
                                        <label key={t.id} className="inline-flex items-center gap-2 text-sm font-medium text-night-ink cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={categoryForm.data.applicable_offer_types.includes(t.id)}
                                                onChange={() => toggleOfferType(t.id)}
                                                className="h-4 w-4 rounded border-night-ink-2/60 text-accent-2 focus:ring-accent"
                                            />
                                            {t.label}
                                        </label>
                                    ))}
                                </div>
                                {categoryForm.errors.applicable_offer_types && (
                                    <p className="mt-1 text-xs text-rust">
                                        {categoryForm.errors.applicable_offer_types}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label className="block text-sm font-medium text-night-ink-2">Commission (%)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="90"
                                        placeholder="défaut"
                                        value={categoryForm.data.commission_rate_percent}
                                        onChange={(e) => categoryForm.setData('commission_rate_percent', e.target.value)}
                                        className="mt-1 h-11 w-full rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink focus:border-accent focus:outline-none min-h-11"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-night-ink-2">Position</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={categoryForm.data.position}
                                        onChange={(e) => categoryForm.setData('position', e.target.value)}
                                        className="mt-1 h-11 w-full rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink focus:border-accent focus:outline-none min-h-11"
                                    />
                                </div>

                                <div className="flex items-center pt-5">
                                    <label className="inline-flex items-center gap-2 text-sm font-medium text-night-ink cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={categoryForm.data.is_active}
                                            onChange={(e) => categoryForm.setData('is_active', e.target.checked)}
                                            className="h-4 w-4 rounded border-night-ink-2/60 text-accent-2 focus:ring-accent"
                                        />
                                        Active
                                    </label>
                                </div>
                            </div>

                            {/* REGLES */}
                            <fieldset className="rounded-xl border border-night-line bg-night p-4">
                                <legend className="px-1 text-xs font-bold uppercase tracking-wider text-night-ink-2">
                                    Règles
                                </legend>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <label className="inline-flex items-center gap-2 text-sm font-medium text-night-ink cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={categoryForm.data.ruleset.require_approval}
                                            onChange={(e) =>
                                                categoryForm.setData('ruleset', {
                                                    ...categoryForm.data.ruleset,
                                                    require_approval: e.target.checked,
                                                })
                                            }
                                            className="h-4 w-4 rounded border-night-ink-2/60 text-accent-2 focus:ring-accent"
                                        />
                                        Validation admin obligatoire
                                    </label>
                                    <label className="inline-flex items-center gap-2 text-sm font-medium text-night-ink cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={categoryForm.data.ruleset.requires_shipping}
                                            onChange={(e) =>
                                                categoryForm.setData('ruleset', {
                                                    ...categoryForm.data.ruleset,
                                                    requires_shipping: e.target.checked,
                                                })
                                            }
                                            className="h-4 w-4 rounded border-night-ink-2/60 text-accent-2 focus:ring-accent"
                                        />
                                        Livraison requise
                                    </label>
                                    <label className="inline-flex items-center gap-2 text-sm font-medium text-night-ink cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={categoryForm.data.ruleset.allow_variants}
                                            onChange={(e) =>
                                                categoryForm.setData('ruleset', {
                                                    ...categoryForm.data.ruleset,
                                                    allow_variants: e.target.checked,
                                                })
                                            }
                                            className="h-4 w-4 rounded border-night-ink-2/60 text-accent-2 focus:ring-accent"
                                        />
                                        Variantes autorisées
                                    </label>

                                    <div className="grid grid-cols-3 gap-2">
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            placeholder="Prix min"
                                            value={categoryForm.data.ruleset.min_price}
                                            onChange={(e) =>
                                                categoryForm.setData('ruleset', {
                                                    ...categoryForm.data.ruleset,
                                                    min_price: e.target.value,
                                                })
                                            }
                                            className="h-11 rounded-lg border border-night-ink-2/60 bg-night px-2 text-sm text-night-ink"
                                        />
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            placeholder="Prix max"
                                            value={categoryForm.data.ruleset.max_price}
                                            onChange={(e) =>
                                                categoryForm.setData('ruleset', {
                                                    ...categoryForm.data.ruleset,
                                                    max_price: e.target.value,
                                                })
                                            }
                                            className="h-11 rounded-lg border border-night-ink-2/60 bg-night px-2 text-sm text-night-ink"
                                        />
                                        <input
                                            type="number"
                                            min="1"
                                            max="20"
                                            placeholder="Img max"
                                            value={categoryForm.data.ruleset.max_images}
                                            onChange={(e) =>
                                                categoryForm.setData('ruleset', {
                                                    ...categoryForm.data.ruleset,
                                                    max_images: e.target.value,
                                                })
                                            }
                                            className="h-11 rounded-lg border border-night-ink-2/60 bg-night px-2 text-sm text-night-ink"
                                        />
                                    </div>
                                </div>
                            </fieldset>

                            <div className="pt-2">
                                <button
                                    type="submit"
                                    disabled={categoryForm.processing}
                                    className="rounded-full bg-accent px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-accent-2 disabled:opacity-50 min-h-11"
                                >
                                    {editing ? 'Enregistrer les modifications' : 'Créer la catégorie'}
                                </button>
                            </div>
                        </div>
                    </form>

                    {/* ============ CHAMPS PERSONNALISES ============ */}
                    {editing && (
                        <div className="rounded-2xl border border-night-line bg-night-2 p-6">
                            <h2 className="font-sans text-base font-semibold text-night-ink">
                                Champs personnalisés — {editing.name}
                            </h2>
                            <p className="mt-1 text-xs text-night-ink-2">
                                Les offres de cette catégorie héritent aussi des champs de ses catégories parentes.
                            </p>

                            {editing.fields && editing.fields.length > 0 && (
                                <table className="mt-4 w-full text-sm">
                                    <tbody className="divide-y divide-night-line">
                                        {editing.fields.map((f) => (
                                            <tr key={f.id} className="hover:bg-night-line/50">
                                                <td className="py-2.5">
                                                    <span className="font-mono text-xs text-night-ink-2">{f.key}</span>
                                                    <span className="ml-2 font-medium text-night-ink">{f.label}</span>
                                                </td>
                                                <td className="py-2.5 text-night-ink-2">
                                                    {fieldTypes.find((t) => t.id === f.type)?.label || f.type}
                                                </td>
                                                <td className="py-2.5 text-sm text-night-ink-2">
                                                    {f.is_required && <span className="mr-1 text-amber font-semibold">requis</span>}
                                                    {f.is_filterable && <span>· facette </span>}
                                                    {f.is_variant_axis && <span>· variante</span>}
                                                </td>
                                                <td className="py-2.5 text-right">
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDeleteField(f.id)}
                                                        className="text-xs font-semibold text-rust hover:underline"
                                                    >
                                                        Supprimer
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}

                            <form onSubmit={handleFieldSubmit} className="mt-5 border-t border-night-line pt-4 grid gap-3">
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label className="block text-sm font-medium text-night-ink-2">Libellé *</label>
                                        <input
                                            type="text"
                                            required
                                            value={fieldForm.data.label}
                                            onChange={(e) => fieldForm.setData('label', e.target.value)}
                                            className="mt-1 h-11 w-full rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink focus:border-accent focus:outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-night-ink-2">Type</label>
                                        <select
                                            value={fieldForm.data.type}
                                            onChange={(e) => fieldForm.setData('type', e.target.value)}
                                            className="mt-1 h-9 w-full rounded-xl border border-night-ink-2/60 bg-night px-3 text-sm text-night-ink focus:border-accent focus:outline-none"
                                        >
                                            {fieldTypes.map((ft) => (
                                                <option key={ft.id} value={ft.id}>
                                                    {ft.label}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-night-ink-2">
                                        Clé machine (facultatif — déduite du libellé)
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="ex. matiere"
                                        value={fieldForm.data.key}
                                        onChange={(e) => fieldForm.setData('key', e.target.value)}
                                        className="mt-1 h-11 w-full rounded-xl border border-night-ink-2/60 bg-night px-3 font-mono text-sm text-night-ink focus:border-accent focus:outline-none"
                                    />
                                    {fieldForm.errors.key && (
                                        <p className="mt-1 text-xs text-rust">{fieldForm.errors.key}</p>
                                    )}
                                </div>

                                {(fieldForm.data.type === 'select' || fieldForm.data.type === 'multiselect') && (
                                    <div>
                                        <label className="block text-sm font-medium text-night-ink-2">
                                            Options (une par ligne)
                                        </label>
                                        <textarea
                                            rows="3"
                                            value={fieldForm.data.options_text}
                                            onChange={(e) => fieldForm.setData('options_text', e.target.value)}
                                            className="mt-1 w-full rounded-xl border border-night-ink-2/60 bg-night px-3 py-2.5 text-sm text-night-ink focus:border-accent focus:outline-none"
                                        />
                                    </div>
                                )}

                                <div className="flex flex-wrap gap-4 pt-1 text-sm">
                                    <label className="inline-flex items-center gap-1.5 font-medium text-night-ink cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={fieldForm.data.is_required}
                                            onChange={(e) => fieldForm.setData('is_required', e.target.checked)}
                                            className="h-3.5 w-3.5 rounded border-night-ink-2/60 text-accent-2 focus:ring-accent"
                                        />
                                        Requis
                                    </label>
                                    <label className="inline-flex items-center gap-1.5 font-medium text-night-ink cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={fieldForm.data.is_filterable}
                                            onChange={(e) => fieldForm.setData('is_filterable', e.target.checked)}
                                            className="h-3.5 w-3.5 rounded border-night-ink-2/60 text-accent-2 focus:ring-accent"
                                        />
                                        Facette de recherche
                                    </label>
                                    {fieldForm.data.type === 'select' && (
                                        <label className="inline-flex items-center gap-1.5 font-medium text-night-ink cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={fieldForm.data.is_variant_axis}
                                                onChange={(e) => fieldForm.setData('is_variant_axis', e.target.checked)}
                                                className="h-3.5 w-3.5 rounded border-night-ink-2/60 text-accent-2 focus:ring-accent"
                                            />
                                            Axe de variante
                                        </label>
                                    )}
                                </div>

                                <div className="pt-2">
                                    <button
                                        type="submit"
                                        disabled={fieldForm.processing}
                                        className="rounded-full border border-night-ink-2/60 bg-night-line px-4 py-2 text-sm font-bold text-night-ink transition-colors hover:bg-night-ink-2/25 disabled:opacity-50 min-h-11"
                                    >
                                        Ajouter le champ
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
