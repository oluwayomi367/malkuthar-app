import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '../../Layouts/StorefrontLayout';
import EmptyState from '../../Components/ui/EmptyState';
import Pagination from '../../Components/ui/Pagination';
import OfferCard from '../../Components/commerce/OfferCard';

export default function Vendor({ vendor, offers }) {
    return (
        <>
            <Head title={`${vendor.display_name} — Place de marché`}>
                <meta name="description" content={vendor.seo_bio} />
            </Head>

            {vendor.banner_url && (
                <div className="h-40 w-full overflow-hidden bg-ground-alt sm:h-52">
                    <img src={vendor.banner_url} alt="" className="h-full w-full object-cover" />
                </div>
            )}

            <div className="mx-auto max-w-[1180px] px-4 py-10 sm:px-6 sm:py-14">
                <nav className="text-xs text-ink-3">
                    <Link href={route('marketplace.shop')} className="hover:text-ink">Place de marché</Link> / <span>{vendor.display_name}</span>
                </nav>

                <header className={['mt-4 flex flex-wrap items-start gap-4', vendor.banner_url ? '-mt-16' : ''].join(' ')}>
                    <div className="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-line bg-surface shadow-[0_2px_10px_-4px_rgba(46,44,40,0.2)]">
                        {vendor.logo_url ? (
                            <img src={vendor.logo_url} alt="" className="h-full w-full object-cover" />
                        ) : (
                            <span className="font-display text-2xl font-bold text-ink-3">{vendor.display_name.slice(0, 1).toUpperCase()}</span>
                        )}
                    </div>
                    <div className={vendor.banner_url ? 'pt-16' : ''}>
                        <h1 className="font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">{vendor.display_name}</h1>
                        <p className="mt-0.5 flex flex-wrap items-center gap-x-2 text-sm text-ink-3">
                            {vendor.region && <span>{vendor.region}{vendor.city && ` · ${vendor.city}`}</span>}
                            <span>· {offers.total} offre{offers.total > 1 ? 's' : ''}</span>
                            <span className="inline-flex items-center gap-1 rounded-full bg-brand-green/10 px-2 py-0.5 text-[11px] font-semibold text-brand-green">
                                <svg className="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M5 13l4 4L19 7" /></svg>Vendeur vérifié
                            </span>
                        </p>
                    </div>
                </header>

                {vendor.bio && <p className="mt-4 max-w-2xl text-ink-2">{vendor.bio}</p>}

                {offers.data.length === 0 ? (
                    <EmptyState className="mt-8" title="Aucune offre en ligne" text="Ce vendeur n'a pas d'offre publiée pour le moment." />
                ) : (
                    <>
                        <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {offers.data.map((offer) => <OfferCard key={offer.id} offer={offer} />)}
                        </div>
                        <Pagination paginator={offers} className="mt-8" />
                    </>
                )}
            </div>
        </>
    );
}

Vendor.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
