import { Head, router } from '@inertiajs/react';
import StorefrontLayout from '../../../Layouts/StorefrontLayout';
import Button from '../../../Components/ui/Button';
import Price from '../../../Components/commerce/Price';
import ProductGallery from '../../../Components/commerce/ProductGallery';
import Reassurance from '../../../Components/commerce/Reassurance';
import AffiliateEligibility from '../../../Components/commerce/AffiliateEligibility';
import ProductDescription, { Breadcrumb } from '../../../Components/commerce/ProductDescription';
import StickyCta from '../../../Components/layout/StickyCta';
import { addToCart } from '../../../lib/cart';
import { money } from '../../../lib/format';

export default function Digital({ product }) {
    const price = product.promotional_price ?? product.price;

    const add = () => {
        addToCart({
            product_id: product.id, name: product.name, type: 'digital',
            price, currency: product.currency, quantity: 1,
        });
        router.visit(route('cart'));
    };

    return (
        <>
            <Head title={product.name}>
                <meta name="description" content={product.seo_description} />
                <script type="application/ld+json">{JSON.stringify({
                    '@context': 'https://schema.org', '@type': 'Product',
                    name: product.name, description: product.description_text, category: product.category,
                    offers: {
                        '@type': 'Offer', price, priceCurrency: product.currency,
                        availability: product.is_available ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    },
                })}</script>
            </Head>

            <div className="mx-auto max-w-[1180px] px-4 py-6 sm:px-6 lg:grid lg:grid-cols-[1.05fr_1fr] lg:gap-12 lg:py-10">
                <div className="lg:sticky lg:top-20 lg:self-start">
                    <Breadcrumb product={product} />
                    <ProductGallery product={product} className="mt-3" />
                </div>

                <div className="mt-6 lg:mt-0">
                    <h1 className="text-2xl font-bold tracking-[-0.01em] text-ink sm:text-[28px]">{product.name}</h1>
                    {product.category && (
                        <p className="mt-1 text-sm text-ink-3">{product.category}{product.sub_category && ` · ${product.sub_category}`}</p>
                    )}

                    <div className="mt-3">
                        <Price price={price} compareAt={product.promotional_price ? product.price : null} currency={product.currency} size="lg" />
                    </div>

                    <p className="mt-4 inline-flex items-center gap-2 text-sm font-medium text-forest">
                        <svg className="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.75"><path d="M9 1L2 9h5l-1 6 7-8H8z" strokeLinejoin="round" /></svg>
                        Livraison immédiate par email après confirmation du paiement
                    </p>

                    <div className="mt-5"><AffiliateEligibility product={product} /></div>

                    <div className="mt-6 hidden lg:block">
                        <Button variant="primary" full onClick={add}>Acheter — {money(price)} {product.currency}</Button>
                    </div>
                </div>
            </div>

            <div className="mx-auto max-w-[1180px] px-4 pb-12 sm:px-6">
                <ProductDescription product={product} accordions={[
                    ['Format & compatibilité', 'Fichier téléchargeable, lisible sur ordinateur, tablette et smartphone.'],
                    ['Livraison', 'Lien de téléchargement sécurisé envoyé par email, valable 7 jours, dans la limite de plusieurs téléchargements.'],
                    ['Remboursement', "Un produit digital consommé (téléchargé) n'est pas remboursable, sauf défaut. Contactez le support en cas de problème."],
                ]} />
                <Reassurance variant="digital" className="mt-10 border-t border-line pt-8" />
            </div>

            <StickyCta info={<span className="font-semibold text-ink">{money(price)} {product.currency}</span>}>
                <Button variant="primary" full onClick={add}>Acheter</Button>
            </StickyCta>
        </>
    );
}

Digital.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
