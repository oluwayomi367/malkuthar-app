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
import ProductLayout, { ProductHeader, BuyBox } from '../../../Components/commerce/ProductLayout';

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

            <ProductLayout
                breadcrumb={<Breadcrumb product={product} />}
                gallery={<ProductGallery product={product} />}
                below={
                    <ProductDescription product={product} accordions={[
                        ['Déroulé de la séance', "La séance débute à l'heure réservée. Prévoyez un environnement calme et une connexion stable pour les rendez-vous en visio."],
                        ['Annulation / report', 'Report ou annulation possible depuis votre espace client. Un rappel vous est envoyé avant le rendez-vous.'],
                    ]} />
                }
            >
                <ProductHeader title={product.name} seller={{ label: 'Maison MALKUTHAR', house: true }} typeLabel="Service">
                    {product.category && <span>{product.category}</span>}
                    {product.duration_minutes && <span>{product.duration_minutes} min</span>}
                </ProductHeader>

                <BuyBox>
                    <Price price={price} compareAt={product.promotional_price ? product.price : null} currency={product.currency} size="display" />

                    <p className="mt-4 flex items-start gap-2.5 text-sm text-ink-2">
                        <svg className="mt-0.5 h-4 w-4 shrink-0 text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7.5V12l3 2" /></svg>
                        {product.delivery_mode === 'visioconférence' || product.meeting_link
                            ? 'Visioconférence — lien communiqué après réservation'
                            : product.location || 'Modalités communiquées après réservation'}
                    </p>

                    <BookingCalendar product={product} className="mt-5" onPick={(date, time) => { setRdvDate(date); setRdvTime(time); }} />

                    <div className="mt-6 hidden lg:block">
                        <Button variant="primary" full onClick={add} disabled={!ready}>
                            {ready ? 'Réserver ce créneau' : 'Choisissez un créneau'}
                        </Button>
                    </div>

                    <Reassurance variant="service" layout="stack" className="mt-6 border-t border-line pt-5" />
                </BuyBox>
            </ProductLayout>

            <StickyCta info={<span className="font-semibold text-ink">{money(price)} {product.currency}</span>}>
                <Button variant="primary" full onClick={add} disabled={!ready}>{ready ? 'Réserver' : 'Choisir un créneau'}</Button>
            </StickyCta>
        </>
    );
}

Service.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
