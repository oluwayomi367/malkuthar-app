import { Head } from '@inertiajs/react';
import StorefrontLayout from '../../Layouts/StorefrontLayout';
import EmptyState from '../../Components/ui/EmptyState';
import Pagination from '../../Components/ui/Pagination';
import OfferCard from '../../Components/commerce/OfferCard';
import { Breadcrumb } from '../../Components/commerce/ProductDescription';

export default function Vendor({ vendor, offers }) {
    const location = [vendor.city, vendor.region].filter(Boolean).join(', ');

    return (
        <>
            <Head title={`${vendor.display_name} — Place de marché`}>
                <meta name="description" content={vendor.seo_bio} />
            </Head>

            <div className="mx-auto max-w-[1280px] px-4 pb-16 pt-6 sm:px-6 lg:pt-10">
                <Breadcrumb items={[
                    { label: 'Place de marché', href: route('marketplace.shop') },
                    { label: vendor.display_name },
                ]} />

                {vendor.banner_url && (
                    <div className="mt-5 aspect-[3/1] w-full overflow-hidden rounded-2xl border border-line bg-ground-alt sm:aspect-[4/1]">
                        <img src={vendor.banner_url} alt="" className="h-full w-full object-cover" />
                    </div>
                )}

                <header className="mt-6 flex flex-col gap-5 border-b border-line pb-8 sm:flex-row sm:items-start">
                    <div className="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-line bg-surface">
                        {vendor.logo_url ? (
                            <img src={vendor.logo_url} alt={`Logo de ${vendor.display_name}`} className="h-full w-full object-cover" />
                        ) : (
                            <span className="font-display text-[32px] font-semibold text-ink-2" aria-hidden="true">
                                {vendor.display_name.slice(0, 1).toUpperCase()}
                            </span>
                        )}
                    </div>
                    <div className="min-w-0">
                        <h1 className="font-display text-[36px] font-semibold leading-[1.1] text-ink sm:text-[44px]">{vendor.display_name}</h1>
                        <p className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-sm text-ink-2">
                            <span className="inline-flex items-center gap-1.5 rounded-full border border-sage/25 bg-sage-soft px-2.5 py-0.5 text-xs font-semibold text-sage">
                                <svg className="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
                                Vendeur vérifié
                            </span>
                            {location && <span>{location}</span>}
                            <span>{offers.total} offre{offers.total > 1 ? 's' : ''} en ligne</span>
                        </p>
                        {vendor.bio && <p className="mt-4 max-w-prose whitespace-pre-line text-base leading-[1.7] text-ink-2">{vendor.bio}</p>}
                    </div>
                </header>

                <section className="mt-10" aria-labelledby="offres-vendeur">
                    <h2 id="offres-vendeur" className="font-display text-[28px] font-semibold text-ink">Ses offres</h2>

                    {offers.data.length === 0 ? (
                        <EmptyState className="mt-6" title="Aucune offre en ligne" text="Ce vendeur n'a pas d'offre publiée pour le moment." />
                    ) : (
                        <>
                            <div className="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                {offers.data.map((offer) => <OfferCard key={offer.id} offer={offer} />)}
                            </div>
                            <Pagination paginator={offers} className="mt-10" />
                        </>
                    )}
                </section>
            </div>
        </>
    );
}

Vendor.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
