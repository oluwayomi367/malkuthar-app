import { Head } from '@inertiajs/react';
import StorefrontLayout from '../Layouts/StorefrontLayout';
import Button from '../Components/ui/Button';
import SectionHeading from '../Components/ui/SectionHeading';

const METIERS = [
    ['Digital', 'Ebooks, formations, audios, programmes numériques. Livrés immédiatement après paiement, protégés par des liens temporaires.'],
    ['Physique', 'Livres, coffrets, accessoires. Gestion des stocks, des variantes et de la livraison suivie, du panier au colis.'],
    ['Service', "Consultations, séances, accompagnements. Réservation d'un créneau réel, en visioconférence ou en présentiel."],
];

const VALEURS = [
    ['Immédiateté', "Pour le digital, aucun délai : le lien de téléchargement part dès la confirmation du paiement."],
    ['Fiabilité', "Chaque paiement est revérifié côté serveur auprès du prestataire avant d'être considéré comme définitif."],
    ['Transparence', 'Pour les affiliés, tout mouvement est tracé dans un registre : commissions, retraits, ajustements.'],
];

export default function About() {
    return (
        <>
            <Head title="À propos">
                <meta name="description" content="MALKUTHAR SARL réunit trois métiers sous un même toit : produits digitaux, produits physiques et services, avec un programme d'affiliation à trois niveaux." />
            </Head>

            <section className="mx-auto max-w-[1180px] px-4 pb-12 pt-14 sm:px-6 sm:pt-20">
                <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-gold">À propos</p>
                <h1 className="mt-3 max-w-2xl text-[30px] font-bold leading-[1.12] tracking-[-0.012em] text-ink sm:text-5xl">
                    Une maison, trois métiers.
                </h1>
                <p className="mt-4 max-w-xl text-ink-2">
                    MALKUTHAR SARL est une plateforme de vente propriétaire, pensée pour commercialiser directement ses produits
                    digitaux, ses produits physiques et ses services — sans dépendre de places de marché tierces. Une place de
                    marché maison, ouverte à des praticiens et vendeurs sélectionnés de la région, est en préparation.
                </p>
            </section>

            <section className="border-y border-line bg-ground-alt">
                <div className="mx-auto grid max-w-[1180px] gap-px bg-line sm:grid-cols-3">
                    {METIERS.map(([title, text]) => (
                        <div key={title} className="bg-ground-alt px-4 py-8 sm:px-6">
                            <p className="text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-3">◇ {title}</p>
                            <p className="mt-3 text-[15px] text-ink-2">{text}</p>
                        </div>
                    ))}
                </div>
            </section>

            <section className="mx-auto max-w-[1180px] px-4 py-14 sm:px-6">
                <SectionHeading eyebrow="Notre approche" title="Ce à quoi nous tenons" />
                <div className="grid gap-8 sm:grid-cols-3">
                    {VALEURS.map(([title, text]) => (
                        <div key={title}>
                            <p className="text-[15px] font-semibold text-ink">{title}</p>
                            <p className="mt-1 text-sm text-ink-2">{text}</p>
                        </div>
                    ))}
                </div>
            </section>

            <section className="border-t border-line bg-ground-alt">
                <div className="mx-auto max-w-[1180px] px-4 py-14 sm:px-6">
                    <div className="max-w-2xl">
                        <p className="text-[11px] font-semibold uppercase tracking-[0.14em] text-gold">Programme d'affiliation</p>
                        <h2 className="mt-2 text-2xl font-bold tracking-[-0.01em] text-ink">Partagez, gagnez sur trois niveaux</h2>
                        <p className="mt-3 text-ink-2">
                            Réservé aux produits digitaux éligibles : <span className="font-semibold text-ink">15 % · 10 % · 5 %</span>{' '}
                            répartis sur trois niveaux de parrainage. Lien personnel, tableau de bord et retraits déclenchés par l'affilié.
                        </p>
                        <div className="mt-5 flex flex-wrap gap-3">
                            <Button variant="secondary" href={route('register')} native>Rejoindre le programme</Button>
                            <Button variant="ghost" href={route('affiliate.program-terms')} native>Lire les conditions →</Button>
                        </div>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-[1180px] px-4 py-12 text-center sm:px-6">
                <Button variant="primary" href={route('catalog.index')}>Explorer la boutique</Button>
            </section>
        </>
    );
}

About.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
