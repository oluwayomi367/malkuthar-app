import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import StorefrontLayout from '../../Layouts/StorefrontLayout';
import Button from '../../Components/ui/Button';
import Badge from '../../Components/ui/Badge';
import SectionHeading from '../../Components/ui/SectionHeading';
import Card from '../../Components/ui/Card';
import Field from '../../Components/ui/Field';
import Textarea from '../../Components/ui/Textarea';

const POUR_QUI = [
    ['Praticiens', 'Thérapeutes, coachs, consultants qui proposent des séances et des accompagnements sur rendez-vous.'],
    ['Créateurs & auteurs', 'Ebooks, audios, ressources numériques livrés automatiquement après paiement.'],
    ['Formateurs', 'Parcours pédagogiques structurés, en autonomie ou en cohorte, avec suivi de progression.'],
    ['Artisans & marchands', 'Objets, coffrets et ouvrages de la région, avec gestion des stocks et de la livraison.'],
];

const TYPES_OFFRES = [
    ['Produits physiques', 'Stocks, variantes, expédition suivie.'],
    ['Produits digitaux', 'Livraison immédiate par lien sécurisé.'],
    ['Services', "Réservation d'un créneau réel, en visio ou en présentiel."],
    ['Formations', 'Modules, leçons, accès limité dans le temps, certificat.'],
];

const ETAPES = [
    ['Créez votre compte vendeur', "Depuis votre compte MALKUTHAR, vous demandez l'ouverture d'un espace vendeur et déposez vos justificatifs."],
    ['Publiez une offre', "Vous choisissez une catégorie, remplissez la fiche (les champs s'adaptent au type d'offre) et soumettez."],
    ["L'équipe valide", 'Chaque offre est relue par un administrateur avant sa mise en ligne. Vous recevez un retour clair si un ajustement est nécessaire.'],
    ['Vendez et soyez payé', 'Votre offre est en ligne. À chaque vente, votre part est créditée ; vous demandez vos versements depuis votre tableau de bord.'],
];

const FAQ = [
    ['Comment MALKUTHAR facture-t-il ses services ?', 'Une commission est prélevée uniquement quand vous vendez — un pourcentage de chaque vente, jamais un abonnement.'],
    ['Y a-t-il des frais supplémentaires ?', "Aucun. La commission affichée est la seule retenue de la plateforme. D'éventuels frais de retrait, s'il y en a, sont indiqués avant confirmation."],
    ['Quand vais-je recevoir mes versements ?', 'Après une courte période de rétractation suivant le paiement du client, votre solde devient disponible et vous déclenchez vous-même le versement depuis votre tableau de bord.'],
    ['Dois-je payer pour utiliser les fonctionnalités de MALKUTHAR ?', 'Non — catalogue dynamique, variantes, calendrier de réservation, avis clients : tout est inclus, quel que soit votre palier.'],
    ['Comment sont gérés les paiements ?', 'Selon la configuration retenue pour votre région (paiement à la livraison ou passerelle en ligne), affichée au client au moment du paiement.'],
    ['Si je ne fais pas de ventes, est-ce que je paie quand même ?', 'Non. Sans vente, aucune commission n\'est due.'],
    ['Puis-je fermer ma boutique à tout moment ?', 'Oui, en retirant vos offres depuis votre espace vendeur ou en nous contactant.'],
];

const fmt = (n) => {
    const s = Number(n).toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
    return s.replace('.', ',');
};

function WaitlistForm() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '', email: '', subject: 'vendeur', message: '', website: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('contact.store'), { onSuccess: () => reset('message') });
    };

    return (
        <form onSubmit={submit} className="mt-6 grid gap-4">
            <div className="grid gap-4 sm:grid-cols-2">
                <Field name="name" label="Votre nom" required autoComplete="name" value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} />
                <Field name="email" label="Votre email" type="email" required autoComplete="email" value={data.email} onChange={(e) => setData('email', e.target.value)} error={errors.email} />
            </div>
            <Textarea name="message" label="Ce que vous vendez, votre région" rows={5} required
                      placeholder="Ex. : séances de sophrologie à Cotonou / coffrets artisanaux / formation en ligne sur…"
                      value={data.message} onChange={(e) => setData('message', e.target.value)} error={errors.message} />
            <div className="hidden" aria-hidden="true">
                <label>Ne pas remplir<input type="text" tabIndex={-1} autoComplete="off" value={data.website} onChange={(e) => setData('website', e.target.value)} /></label>
            </div>
            <div><Button variant="primary" type="submit" disabled={processing}>M'inscrire à la liste d'attente</Button></div>
        </form>
    );
}

export default function Landing({ enabled, stats, starterRate, growthRate, growthThreshold }) {
    const [openFaq, setOpenFaq] = useState(null);

    return (
        <>
            <Head title="Vendre sur MALKUTHAR — la place de marché">
                <meta name="description" content={enabled
                    ? `Ouvrez votre boutique sur MALKUTHAR : produits physiques, produits digitaux, services et formations. Commission ${starterRate} % flat, aucun frais mensuel, versements pilotés par vous.`
                    : "La place de marché MALKUTHAR arrive : praticiens, créateurs, formateurs et artisans de la région pourront y vendre produits physiques, produits digitaux, services et formations. Rejoignez la liste d'attente."} />
            </Head>

            {enabled ? (
                <>
                    <section className="mx-auto max-w-[1180px] px-4 pb-12 pt-14 sm:px-6 sm:pt-20">
                        <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Place de marché</p>
                        <h1 className="mt-3 max-w-2xl text-[30px] font-bold leading-[1.12] tracking-[-0.012em] text-ink sm:text-5xl">
                            Créez et vendez vos offres sur MALKUTHAR.
                        </h1>
                        <p className="mt-4 max-w-xl text-ink-2">
                            Un compte vendeur, une boutique à votre nom, une commission simple et transparente. Produits physiques,
                            produits digitaux, services et formations — chaque fiche validée par notre équipe avant sa mise en ligne.
                        </p>
                        <div className="mt-6 flex flex-wrap gap-3">
                            <Button variant="primary" href={route('vendor.application')} native>Créez votre boutique</Button>
                            <Button variant="secondary" href={route('marketplace.shop')}>Parcourir la place de marché</Button>
                        </div>
                    </section>

                    {stats && (
                        <section className="border-y border-line bg-ground-alt">
                            <div className="mx-auto grid max-w-[1180px] grid-cols-2 gap-px bg-line">
                                <div className="bg-ground-alt px-4 py-8 text-center sm:px-6">
                                    <p className="font-display text-2xl font-semibold tracking-[-0.01em] text-ink">{stats.vendors}</p>
                                    <p className="mt-1 text-[13px] text-ink-2">Vendeur{stats.vendors > 1 ? 's' : ''} actif{stats.vendors > 1 ? 's' : ''}</p>
                                </div>
                                <div className="bg-ground-alt px-4 py-8 text-center sm:px-6">
                                    <p className="font-display text-2xl font-semibold tracking-[-0.01em] text-ink">{stats.offers}</p>
                                    <p className="mt-1 text-[13px] text-ink-2">Offre{stats.offers > 1 ? 's' : ''} en ligne</p>
                                </div>
                            </div>
                        </section>
                    )}
                </>
            ) : (
                <section className="mx-auto max-w-[1180px] px-4 pb-12 pt-14 sm:px-6 sm:pt-20">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Place de marché · Bientôt</p>
                    <h1 className="mt-3 max-w-2xl text-[30px] font-bold leading-[1.12] tracking-[-0.012em] text-ink sm:text-5xl">
                        Vendez vos offres sur MALKUTHAR.
                    </h1>
                    <p className="mt-4 max-w-xl text-ink-2">
                        Nous ouvrons prochainement une place de marché aux praticiens, créateurs, formateurs et artisans de la
                        région. Un compte vendeur, un catalogue à votre nom, et le même soin de transparence que pour le reste de
                        la plateforme. Inscrivez-vous pour être prévenu·e à l'ouverture.
                    </p>
                    <div className="mt-6 flex flex-wrap gap-3">
                        <a href="#candidater" className="inline-flex h-11 items-center justify-center rounded-xl bg-accent px-5 text-sm font-semibold text-white shadow-[0_1px_2px_rgba(46,44,40,0.12)] hover:bg-[#77602a] sm:h-10">Rejoindre la liste d'attente</a>
                        <a href="#fonctionnement" className="px-2 text-sm font-semibold text-accent underline decoration-accent/40 underline-offset-4 hover:decoration-accent">Comment ça marchera →</a>
                    </div>
                </section>
            )}

            <section className="border-y border-line bg-ground-alt">
                <div className="mx-auto grid max-w-[1180px] gap-px bg-line sm:grid-cols-2 lg:grid-cols-4">
                    {POUR_QUI.map(([titre, texte]) => (
                        <div key={titre} className="bg-ground-alt px-4 py-8 sm:px-6">
                            <p className="text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-3">◇ {titre}</p>
                            <p className="mt-3 text-[15px] text-ink-2">{texte}</p>
                        </div>
                    ))}
                </div>
            </section>

            <section className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
                <SectionHeading eyebrow="Quatre natures d'offres" title={enabled ? 'Ce que vous pouvez vendre' : 'Ce que vous pourrez vendre'} />
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {TYPES_OFFRES.map(([titre, texte]) => (
                        <div key={titre} className="rounded-2xl border border-line bg-surface p-5 shadow-[0_1px_2px_rgba(46,44,40,0.04)]">
                            <p className="font-display text-[15px] font-semibold text-ink">{titre}</p>
                            <p className="mt-1 text-[13px] leading-snug text-ink-2">{texte}</p>
                        </div>
                    ))}
                </div>
                <p className="mt-5 text-sm text-ink-2">
                    Chaque catégorie a ses propres champs (dimensions, durée, niveau, langue…) : la fiche s'adapte à ce que vous
                    vendez, sans formulaire fourre-tout.
                </p>
            </section>

            <section id="fonctionnement" className="border-t border-line bg-ground-alt">
                <div className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
                    <SectionHeading eyebrow="Le parcours vendeur" title={enabled ? 'Comment ça marche' : 'Comment ça marchera'} />
                    <ol className="grid gap-6 sm:grid-cols-2">
                        {ETAPES.map(([titre, texte], i) => (
                            <li key={titre} className="flex gap-4">
                                <span className="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-accent/30 bg-accent-soft/60 font-display text-sm font-semibold text-accent">
                                    {i + 1}
                                </span>
                                <div>
                                    <p className="font-display text-[15px] font-semibold text-ink">{titre}</p>
                                    <p className="mt-1 text-sm leading-relaxed text-ink-2">{texte}</p>
                                </div>
                            </li>
                        ))}
                    </ol>
                </div>
            </section>

            <section id="tarifs" className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
                <SectionHeading eyebrow="Le modèle économique" title="Simple, transparent, équitable" />
                <p className="max-w-2xl text-sm text-ink-2">
                    Une commission unique, prélevée uniquement sur ce que vous vendez réellement. Pas de frais mensuels, pas de
                    frais cachés.
                </p>

                <div className="mt-8 grid gap-4 sm:grid-cols-2">
                    <div className="rounded-2xl border border-line bg-surface p-6">
                        <Badge tone="muted">Starter</Badge>
                        <p className="mt-3 font-display text-3xl font-bold text-ink">{fmt(starterRate)} % <span className="text-sm font-medium text-ink-3">flat</span></p>
                        <p className="mt-1 text-sm text-ink-2">Pour démarrer votre boutique, sans engagement.</p>
                        <ul className="mt-4 space-y-2 text-sm text-ink-2">
                            {['Aucun frais mensuel', 'Aucun frais caché', 'Toutes les fonctionnalités', 'Versements pilotés par vous'].map((f) => (
                                <li key={f} className="flex items-center gap-2">
                                    <svg className="h-4 w-4 shrink-0 text-brand-green" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.8"><path d="M4 10l4 4 8-8" strokeLinecap="round" strokeLinejoin="round" /></svg>
                                    {f}
                                </li>
                            ))}
                        </ul>
                        <Button variant="primary" className="mt-6 w-full justify-center" href={route('vendor.application')} native>Créez votre boutique</Button>
                    </div>

                    <div className="rounded-2xl border border-accent/30 bg-accent-soft/30 p-6">
                        <Badge tone="gold">Growth</Badge>
                        <p className="mt-3 font-display text-3xl font-bold text-ink">{fmt(growthRate)} % <span className="text-sm font-medium text-ink-3">flat</span></p>
                        <p className="mt-1 text-sm text-ink-2">Pour les boutiques établies, dès {Math.round(growthThreshold).toLocaleString('fr-FR')} FCFA de chiffre d'affaires cumulé.</p>
                        <ul className="mt-4 space-y-2 text-sm text-ink-2">
                            {['Aucun frais mensuel', 'Aucun frais caché', 'Toutes les fonctionnalités', 'Activé par notre équipe au seuil atteint'].map((f) => (
                                <li key={f} className="flex items-center gap-2">
                                    <svg className="h-4 w-4 shrink-0 text-brand-green" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.8"><path d="M4 10l4 4 8-8" strokeLinecap="round" strokeLinejoin="round" /></svg>
                                    {f}
                                </li>
                            ))}
                        </ul>
                        <Button variant="secondary" className="mt-6 w-full justify-center" href={route('contact')}>Nous contacter</Button>
                    </div>
                </div>

                <div className="mt-4 rounded-2xl border border-line bg-ground-alt p-6 text-center">
                    <Badge tone="muted">Scale</Badge>
                    <p className="mt-2 font-display text-xl font-bold text-ink">Taux sur mesure</p>
                    <p className="mx-auto mt-1 max-w-md text-sm text-ink-2">Volume important ? Nous négocions un taux adapté à vos besoins.</p>
                    <Button variant="ghost" className="mt-3" href={route('contact')}>Contactez le support →</Button>
                </div>

                <p className="mt-6 text-sm text-ink-2">
                    La place de marché est <span className="font-semibold text-ink">indépendante du programme d'affiliation</span> :
                    les offres des vendeurs n'entrent pas dans les commissions à trois niveaux. Le détail figure dans les{' '}
                    <a href={route('marketplace.vendor-terms')} className="font-semibold text-accent underline decoration-accent/40 underline-offset-4 hover:decoration-accent">
                        conditions du programme vendeur
                    </a>.
                </p>
            </section>

            <section className="border-t border-line bg-ground-alt">
                <div className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
                    <SectionHeading eyebrow="Vos questions" title="Questions fréquentes" />
                    <div className="divide-y divide-line rounded-2xl border border-line bg-surface">
                        {FAQ.map(([q, a], i) => (
                            <details key={q} className="group px-5 py-4" open={openFaq === i} onToggle={(e) => setOpenFaq(e.currentTarget.open ? i : null)}>
                                <summary className="flex cursor-pointer list-none items-center justify-between gap-4 text-[15px] font-medium text-ink">
                                    {q}
                                    <svg className="h-4 w-4 shrink-0 text-ink-3 transition-transform group-open:rotate-45" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.8">
                                        <path d="M10 4v12M4 10h12" strokeLinecap="round" />
                                    </svg>
                                </summary>
                                <p className="mt-2 text-sm leading-relaxed text-ink-2">{a}</p>
                            </details>
                        ))}
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
                <SectionHeading eyebrow="La confiance" title="Des versements pilotés par vous, tout tracé" />
                <div className="grid gap-6 sm:grid-cols-2">
                    <div>
                        <p className="text-[15px] font-semibold text-ink">Versements pilotés par vous</p>
                        <p className="mt-1 text-sm text-ink-2">Mobile Money ou virement. Vous déclenchez vos retraits depuis votre tableau de bord, une fois la période de validation passée.</p>
                    </div>
                    <div>
                        <p className="text-[15px] font-semibold text-ink">Chaque mouvement tracé</p>
                        <p className="mt-1 text-sm text-ink-2">Ventes, commissions, versements : tout figure dans un registre consultable, comme pour les affiliés.</p>
                    </div>
                </div>
            </section>

            {enabled ? (
                <section className="mx-auto max-w-[1180px] px-4 py-14 text-center sm:px-6">
                    <h2 className="font-display text-2xl font-bold tracking-[-0.01em] text-ink">Prêt à lancer votre boutique ?</h2>
                    <p className="mx-auto mt-2 max-w-md text-sm text-ink-2">Commencez au palier Starter, sans engagement. Changez de palier à tout moment selon votre volume.</p>
                    <div className="mt-5 flex flex-wrap justify-center gap-3">
                        <Button variant="primary" href={route('vendor.application')} native>Créer ma boutique</Button>
                        <Button variant="secondary" href={route('marketplace.shop')}>Parcourir la place de marché</Button>
                    </div>
                </section>
            ) : (
                <>
                    <section className="border-t border-line bg-ground-alt">
                        <div className="mx-auto max-w-[1180px] px-4 py-14 sm:px-6">
                            <SectionHeading eyebrow="Où en sommes-nous" title="Ouverture progressive" />
                            <p className="max-w-2xl text-sm text-ink-2">
                                La place de marché démarrera avec un petit groupe de vendeurs de la région cible, chaque offre étant
                                validée à la main. L'accès s'élargira ensuite par vagues. En rejoignant la liste d'attente, vous serez
                                contacté·e dès que les inscriptions ouvriront.
                            </p>
                        </div>
                    </section>

                    <section id="candidater" className="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
                        <div className="grid gap-10 lg:grid-cols-[1.3fr_1fr] lg:gap-16">
                            <div>
                                <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Liste d'attente</p>
                                <h2 className="mt-2 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Être prévenu·e à l'ouverture</h2>
                                <p className="mt-3 max-w-lg text-sm text-ink-2">
                                    Dites-nous en quelques mots ce que vous souhaitez vendre et où vous êtes installé·e. Nous
                                    revenons vers vous à l'adresse indiquée dès que le programme vendeur ouvre.
                                </p>
                                <WaitlistForm />
                            </div>
                            <aside>
                                <Card>
                                    <p className="text-[11px] font-semibold uppercase tracking-[0.1em] text-ink-3">En attendant</p>
                                    <ul className="mt-4 space-y-3 text-sm text-ink-2">
                                        <li>Vous avez déjà un compte MALKUTHAR ? Il deviendra aussi votre compte vendeur — aucun second compte.</li>
                                        <li>Envie de recommander plutôt que de vendre ? Le <a href={route('affiliation')} className="text-accent hover:underline">programme d'affiliation</a> est ouvert dès maintenant.</li>
                                        <li>Une question précise ? Passez par la <a href={route('contact')} className="text-accent hover:underline">page contact</a>.</li>
                                        <li>À lire : les <a href={route('marketplace.vendor-terms')} className="text-accent hover:underline">conditions du programme vendeur</a>.</li>
                                    </ul>
                                </Card>
                            </aside>
                        </div>
                    </section>
                </>
            )}
        </>
    );
}

Landing.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
