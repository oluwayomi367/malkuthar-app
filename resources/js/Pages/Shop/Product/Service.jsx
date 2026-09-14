import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import StorefrontLayout from '../../../Layouts/StorefrontLayout';
import Button from '../../../Components/ui/Button';
import Price from '../../../Components/commerce/Price';
import ProductGallery from '../../../Components/commerce/ProductGallery';
import Reassurance from '../../../Components/commerce/Reassurance';
import BookingCalendar from '../../../Components/commerce/BookingCalendar';
import ProductDescription, { Breadcrumb } from '../../../Components/commerce/ProductDescription';
import StickyCta from '../../../Components/layout/StickyCta';
import { addToCart } from '../../../lib/cart';
import { money } from '../../../lib/format';

export default function Service({ product }) {
    const price = product.promotional_price ?? product.price;
    const [rdvDate, setRdvDate] = useState('');
    const [rdvTime, setRdvTime] = useState('');
    const ready = Boolean(rdvDate && rdvTime);

    const add = () => {
        if (!ready) return;
        addToCart({
            product_id: product.id, name: product.name, type: 'service',
            price, currency: product.currency, quantity: 1,
            reservation_date: rdvDate, reservation_time: rdvTime,
        });
        router.visit(route('cart'));
    };

    return (
        <>
            <Head title={product.name}>
                <meta name="description" content={product.seo_description} />
                <script type="application/ld+json">{JSON.stringify({
                    '@context': 'https://schema.org', '@type': 'Service',
                    name: product.name, description: product.description_text,
                    offers: { '@type': 'Offer', price, priceCurrency: product.currency },
                })}</script>
            </Head>

            <div className="mx-auto max-w-[1180px] px-4 py-6 sm:px-6 lg:grid lg:grid-cols-[1.05fr_1fr] lg:gap-12 lg:py-10">
                <div className="lg:sticky lg:top-20 lg:self-start">
                    <Breadcrumb product={product} />
                    <ProductGallery product={product} className="mt-3" />
                </div>

                <div className="mt-6 lg:mt-0">
                    <h1 className="text-2xl font-bold tracking-[-0.01em] text-ink sm:text-[28px]">{product.name}</h1>

                    <div className="mt-3 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <Price price={price} compareAt={product.promotional_price ? product.price : null} currency={product.currency} size="lg" />
                        {product.duration_minutes && <span className="text-sm text-ink-3">· {product.duration_minutes} min</span>}
                    </div>

                    <p className="mt-2 inline-flex items-center gap-2 text-sm text-ink-2">
                        <svg className="h-4 w-4 text-ink-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5"><circle cx="8" cy="8" r="6.5" /><path d="M8 4.5V8l2.5 1.5" strokeLinecap="round" /></svg>
                        {product.delivery_mode === 'visioconférence' || product.meeting_link
                            ? 'Visioconférence — lien communiqué après réservation'
                            : product.location || 'Modalités communiquées après réservation'}
                    </p>

                    <div className="mt-5">
                        <BookingCalendar product={product} onPick={(date, time) => { setRdvDate(date); setRdvTime(time); }} />
                    </div>

                    <div className="mt-6 hidden lg:block">
                        <Button variant="primary" full onClick={add} disabled={!ready}>
                            {ready ? 'Réserver ce créneau' : 'Choisissez un créneau'}
                        </Button>
                    </div>
                </div>
            </div>

            <div className="mx-auto max-w-[1180px] px-4 pb-12 sm:px-6">
                <ProductDescription product={product} accordions={[
                    ['Déroulé de la séance', 'La séance débute à l\'heure réservée. Prévoyez un environnement calme et une connexion stable pour les rendez-vous en visio.'],
                    ['Annulation / report', 'Report ou annulation possible depuis votre espace client. Un rappel vous est envoyé avant le rendez-vous.'],
                ]} />
                <Reassurance variant="service" className="mt-10 border-t border-line pt-8" />
            </div>

            <StickyCta info={<span className="font-semibold text-ink">{money(price)} {product.currency}</span>}>
                <Button variant="primary" full onClick={add} disabled={!ready}>{ready ? 'Réserver' : 'Choisir un créneau'}</Button>
            </StickyCta>
        </>
    );
}

Service.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
