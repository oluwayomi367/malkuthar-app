import { Head, Link, router } from '@inertiajs/react';
import StorefrontLayout from '../../Layouts/StorefrontLayout';
import Card from '../../Components/ui/Card';
import Field from '../../Components/ui/Field';
import Select from '../../Components/ui/Select';
import Button from '../../Components/ui/Button';
import EmptyState from '../../Components/ui/EmptyState';
import Pagination from '../../Components/ui/Pagination';
import OfferCard from '../../Components/commerce/OfferCard';

const TYPE_LABELS = { physical: 'Produits physiques', digital: 'Produits digitaux', service: 'Services', training: 'Formations' };

export default function Shop({ offers, rootCategories, category, facetFields, activeType, activeFacets, query }) {
    const q = query ?? {};

    const buildParams = (overrides) => ({
        q: q.q, categorie: category?.slug, type: activeType, prix_min: q.prix_min, prix_max: q.prix_max,
        region: q.region, f: activeFacets, tri: q.tri, ...overrides,
    });

    const apply = (overrides) => router.get(route('marketplace.shop'), buildParams(overrides), { preserveState: true, preserveScroll: true, replace: true });

    const submit = (e) => {
        e.preventDefault();
        const form = new FormData(e.target);
        apply({
            q: form.get('q') || undefined, prix_min: form.get('prix_min') || undefined,
            prix_max: form.get('prix_max') || undefined, region: form.get('region') || undefined,
        });
    };

    const activeChips = [];
    if (q.q) activeChips.push({ label: `« ${q.q} »`, remove: () => apply({ q: undefined }) });
    if (category) activeChips.push({ label: category.name, remove: () => apply({ categorie: undefined }) });
    if (activeType) activeChips.push({ label: TYPE_LABELS[activeType] ?? activeType, remove: () => apply({ type: undefined }) });
    if (q.prix_min || q.prix_max) {
        activeChips.push({ label: `Prix : ${q.prix_min ?? '0'} – ${q.prix_max ?? '∞'}`, remove: () => apply({ prix_min: undefined, prix_max: undefined }) });
    }
    if (q.region) activeChips.push({ label: `Région : ${q.region}`, remove: () => apply({ region: undefined }) });
    Object.entries(activeFacets ?? {}).forEach(([fk, fv]) => {
        const field = facetFields.find((f) => f.key === fk);
        const optLabel = field?.options?.find((o) => o.value === fv)?.label ?? fv;
        activeChips.push({
            label: `${field?.label ?? fk} : ${optLabel}`,
            remove: () => apply({ f: { ...activeFacets, [fk]: undefined } }),
        });
    });

    const hasActiveFilters = activeChips.length > 0;

    return (
        <>
            <Head title={`Place de marché${category ? ` — ${category.name}` : ''}`}>
                <meta name="description" content="Les offres des praticiens, créateurs et artisans de la région sur MALKUTHAR : produits, services et formations validés par notre équipe." />
            </Head>

            <section className="border-b border-line bg-ground-alt/50">
                <div className="mx-auto max-w-[1180px] px-4 py-10 sm:px-6 sm:py-14">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Place de marché</p>
                    <h1 className="mt-2 font-display text-2xl font-bold tracking-[-0.012em] text-ink sm:text-4xl">
                        {category?.name ?? 'Les offres de nos vendeurs'}
                    </h1>
                    <p className="mt-2 max-w-xl text-ink-2">
                        {category?.description ?? "Praticiens, créateurs, formateurs et artisans de la région. Chaque offre est validée par notre équipe avant sa mise en ligne."}
                    </p>

                    {!category && (
                        <div className="mt-5 flex flex-wrap gap-2">
                            {rootCategories.map((c) => (
                                <Link key={c.slug} href={route('marketplace.shop', { categorie: c.slug })}
                                      className="rounded-full border border-line bg-surface px-3 py-1.5 text-sm text-ink-2 transition-colors hover:border-line-strong hover:text-ink">
                                    {c.name}
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </section>

            <div className="mx-auto grid max-w-[1180px] gap-8 px-4 pb-20 pt-8 sm:px-6 lg:grid-cols-[260px_1fr]">
                <aside className="lg:sticky lg:top-24 lg:self-start">
                    <Card>
                        <div className="flex items-center justify-between">
                            <p className="font-display text-[15px] font-semibold text-ink">Filtrer</p>
                            {hasActiveFilters && (
                                <Link href={route('marketplace.shop')} className="text-xs font-medium text-ink-3 hover:text-ink">Tout effacer</Link>
                            )}
                        </div>

                        <form onSubmit={submit} className="mt-4 space-y-5">
                            <Field name="q" label="Recherche" defaultValue={q.q ?? ''} placeholder="Nom de l'offre…" />

                            <div className="border-t border-line pt-5">
                                <p className="mb-2 text-[13px] font-medium text-ink-2">Catégorie</p>
                                <ul className="space-y-0.5 text-sm">
                                    <li>
                                        <Link href={route('marketplace.shop')}
                                              className={['flex items-center rounded-lg px-2.5 py-1.5 transition-colors', !category ? 'bg-accent-soft/60 font-semibold text-ink' : 'text-ink-2 hover:bg-black/[0.03] hover:text-ink'].join(' ')}>
                                            Toutes les catégories
                                        </Link>
                                    </li>
                                    {rootCategories.map((c) => (
                                        <li key={c.slug}>
                                            <Link href={route('marketplace.shop', { categorie: c.slug })}
                                                  className={['flex items-center rounded-lg px-2.5 py-1.5 transition-colors', category?.id === c.id ? 'bg-accent-soft/60 font-semibold text-ink' : 'text-ink-2 hover:bg-black/[0.03] hover:text-ink'].join(' ')}>
                                                {c.name}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            <div className="border-t border-line pt-5">
                                <Select name="type" label="Nature de l'offre" placeholder="Toutes" value={activeType ?? ''}
                                        options={TYPE_LABELS} onChange={(e) => apply({ type: e.target.value || undefined })} />
                            </div>

                            <div className="border-t border-line pt-5">
                                <p className="mb-2 text-[13px] font-medium text-ink-2">Prix</p>
                                <div className="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                                    <div className="min-w-0"><Field name="prix_min" type="number" min="0" className="min-w-0" defaultValue={q.prix_min ?? ''} placeholder="Min" /></div>
                                    <span className="text-ink-3">–</span>
                                    <div className="min-w-0"><Field name="prix_max" type="number" min="0" className="min-w-0" defaultValue={q.prix_max ?? ''} placeholder="Max" /></div>
                                </div>
                            </div>

                            <div className="border-t border-line pt-5">
                                <Field name="region" label="Région du vendeur" defaultValue={q.region ?? ''} placeholder="Ex. : Littoral" />
                            </div>

                            {facetFields.map((field) => (
                                <div key={field.key} className="border-t border-line pt-5">
                                    <Select name={`f_${field.key}`} label={field.label} placeholder="Toutes"
                                            value={activeFacets?.[field.key] ?? ''}
                                            options={(field.options ?? []).reduce((acc, o) => ({ ...acc, [o.value]: o.label }), {})}
                                            onChange={(e) => apply({ f: { ...activeFacets, [field.key]: e.target.value || undefined } })} />
                                </div>
                            ))}

                            <div className="space-y-2 border-t border-line pt-5">
                                <Button type="submit" variant="primary" full>Appliquer</Button>
                                {hasActiveFilters && (
                                    <Link href={route('marketplace.shop')} className="block text-center text-sm text-ink-3 hover:text-ink">Réinitialiser les filtres</Link>
                                )}
                            </div>
                        </form>
                    </Card>
                </aside>

                <div>
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <span className="text-sm text-ink-2">{offers.total} offre{offers.total > 1 ? 's' : ''}</span>
                        <div className="w-40">
                            <Select name="tri" value={q.tri ?? ''} onChange={(e) => apply({ tri: e.target.value || undefined })}
                                    options={{ '': 'Plus récentes', prix_asc: 'Prix croissant', prix_desc: 'Prix décroissant', populaire: 'Les plus vendues' }} />
                        </div>
                    </div>

                    {hasActiveFilters && (
                        <div className="mb-5 flex flex-wrap gap-2">
                            {activeChips.map((chip) => (
                                <button key={chip.label} type="button" onClick={chip.remove}
                                        className="inline-flex items-center gap-1.5 rounded-full border border-line-strong bg-surface py-1 pl-3 pr-2 text-xs font-medium text-ink transition-colors hover:border-ink-3">
                                    {chip.label}
                                    <svg className="h-3.5 w-3.5 text-ink-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.6">
                                        <path d="M4 4l8 8M12 4l-8 8" strokeLinecap="round" />
                                    </svg>
                                </button>
                            ))}
                        </div>
                    )}

                    {offers.data.length === 0 ? (
                        <EmptyState icon="search" title="Aucune offre trouvée" text="Essayez d'élargir votre recherche ou de changer de catégorie."
                                    cta={<Button variant="secondary" href={route('marketplace.shop')}>Voir toutes les offres</Button>} />
                    ) : (
                        <>
                            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                {offers.data.map((offer) => <OfferCard key={offer.id} offer={offer} />)}
                            </div>
                            <Pagination paginator={offers} className="mt-8" />
                        </>
                    )}
                </div>
            </div>
        </>
    );
}

Shop.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
