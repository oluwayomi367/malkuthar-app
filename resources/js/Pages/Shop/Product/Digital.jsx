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
import ProductLayout, { ProductHeader, BuyBox } from '../../../Components/commerce/ProductLayout';

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

            <ProductLayout
                breadcrumb={<Breadcrumb product={product} />}
                gallery={<ProductGallery product={product} />}
                below={
                    <ProductDescription product={product} accordions={[
                        ['Format & compatibilité', 'Fichier téléchargeable, lisible sur ordinateur, tablette et smartphone.'],
                        ['Livraison', 'Lien de téléchargement sécurisé envoyé par email, valable 7 jours, dans la limite de plusieurs téléchargements.'],
                        ['Remboursement', "Un produit digital consommé (téléchargé) n'est pas remboursable, sauf défaut. Contactez le support en cas de problème."],
                    ]} />
                }
            >
                <ProductHeader title={product.name} seller={{ label: 'Maison MALKUTHAR', house: true }} typeLabel="Digital">
                    {product.category && <span>{product.category}{product.sub_category && ` · ${product.sub_category}`}</span>}
                </ProductHeader>

                <BuyBox>
                    <Price price={price} compareAt={product.promotional_price ? product.price : null} currency={product.currency} size="display" />

                    <p className="mt-4 flex items-start gap-2.5 text-sm text-ink-2">
                        <svg className="mt-0.5 h-4 w-4 shrink-0 text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M12 4v11M7.5 10.5L12 15l4.5-4.5M5 19h14" /></svg>
                        Livraison immédiate par email après confirmation du paiement
                    </p>

                    <div className="mt-6 hidden lg:block">
                        <Button variant="primary" full onClick={add}>Acheter — {money(price)} {product.currency}</Button>
                    </div>

                    <Reassurance variant="digital" layout="stack" className="mt-6 border-t border-line pt-5" />
                </BuyBox>

                <AffiliateEligibility product={product} className="mt-4" />
            </ProductLayout>

            <StickyCta info={<span className="font-semibold text-ink">{money(price)} {product.currency}</span>}>
                <Button variant="primary" full onClick={add}>Acheter</Button>
            </StickyCta>
        </>
    );
}

Digital.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
