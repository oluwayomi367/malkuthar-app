import { Head, useForm } from '@inertiajs/react';
import StorefrontLayout from '../Layouts/StorefrontLayout';
import Button from '../Components/ui/Button';
import Card from '../Components/ui/Card';
import Field from '../Components/ui/Field';
import Select from '../Components/ui/Select';
import Textarea from '../Components/ui/Textarea';

const SUBJECTS = {
    commande: 'Une commande',
    produit: 'Un produit ou une création',
    affiliation: "Le programme d'affiliation",
    vendeur: 'Devenir vendeur (Place de Marché)',
    autre: 'Autre demande',
};

export default function Contact({ contact }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '', email: '', subject: '', message: '', website: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('contact.store'), { onSuccess: () => reset('message') });
    };

    return (
        <>
            <Head title="Contact & Conciergerie — MALKUTHAR">
                <meta
                    name="description"
                    content="Contactez l'équipe et le service conciergerie MALKUTHAR : commandes, créations d'exception, réservations ou partenariats."
                />
            </Head>

            <div className="mx-auto max-w-[1280px] px-4 py-6 sm:px-6 sm:py-8 space-y-10">
                {/* 1. HERO BANNER — STYLE RÉFÉRENCE AVEC PHOTO CONCIERGERIE */}
                <section className="overflow-hidden rounded-2xl sm:rounded-3xl border border-line-strong/60 bg-[#F9FAFB] p-6 sm:p-8 lg:p-10 shadow-2xs">
                    <div className="grid grid-cols-1 items-center gap-8 lg:grid-cols-[1.1fr_0.9fr]">
                        <div className="flex flex-col justify-center">
                            <span className="text-xs sm:text-sm font-bold text-accent underline decoration-2 underline-offset-4">
                                Service Client & Conciergerie
                            </span>
                            <h1 className="mt-3 sm:mt-4 font-display text-2xl font-extrabold tracking-tight text-ink sm:text-3xl lg:text-4xl leading-[1.15]">
                                Une question ?
                                <br />
                                Échangeons ensemble
                            </h1>
                            <p className="mt-3 max-w-xl text-xs sm:text-sm leading-relaxed text-ink-2">
                                Que ce soit pour le suivi d'une commande, une demande sur nos créations, une réservation
                                de résidence ou un partenariat, notre équipe est à votre entière écoute.
                            </p>
                            <div className="mt-5 flex flex-wrap items-center gap-4 text-xs font-semibold text-ink-2">
                                <span className="inline-flex items-center gap-1.5">
                                    <span className="h-2 w-2 rounded-full bg-emerald-500" />
                                    Réponse rapide sous 24h
                                </span>
                                <span className="inline-flex items-center gap-1.5">
                                    <span className="h-2 w-2 rounded-full bg-accent" />
                                    Écoute attentive & personnalisée
                                </span>
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-xl sm:rounded-2xl border border-black/5 bg-ground shadow-sm">
                            <img
                                src="/images/contact-concierge.jpg"
                                alt="Service conciergerie et accueil MALKUTHAR"
                                className="h-52 sm:h-64 md:h-72 lg:h-80 w-full object-cover object-center"
                            />
                        </div>
                    </div>
                </section>

                {/* 2. FORMULAIRE & COORDONNÉES */}
                <div className="grid gap-8 lg:grid-cols-[1.3fr_0.7fr]">
                    <div className="rounded-2xl border border-line bg-surface p-6 sm:p-8 shadow-2xs">
                        <span className="text-xs font-bold uppercase tracking-wider text-accent underline decoration-2 underline-offset-4">
                            Message direct
                        </span>
                        <h2 className="mt-1 font-display text-xl sm:text-2xl font-bold text-ink">
                            Envoyez-nous un message
                        </h2>

                        <form onSubmit={submit} className="mt-6 grid gap-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field
                                    name="name"
                                    label="Votre nom"
                                    required
                                    autoComplete="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    error={errors.name}
                                />
                                <Field
                                    name="email"
                                    label="Votre adresse email"
                                    type="email"
                                    required
                                    autoComplete="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    error={errors.email}
                                />
                            </div>

                            <Select
                                name="subject"
                                label="Objet de votre demande"
                                required
                                placeholder="Sélectionnez un sujet…"
                                options={SUBJECTS}
                                value={data.subject}
                                onChange={(e) => setData('subject', e.target.value)}
                                error={errors.subject}
                            />

                            <Textarea
                                name="message"
                                label="Votre message détaillé"
                                rows={5}
                                required
                                value={data.message}
                                onChange={(e) => setData('message', e.target.value)}
                                error={errors.message}
                            />

                            {/* honeypot anti-spam */}
                            <div className="hidden" aria-hidden="true">
                                <label>
                                    Ne pas remplir
                                    <input
                                        type="text"
                                        tabIndex={-1}
                                        autoComplete="off"
                                        value={data.website}
                                        onChange={(e) => setData('website', e.target.value)}
                                    />
                                </label>
                            </div>

                            <div className="pt-2">
                                <Button variant="primary" type="submit" disabled={processing} className="h-10 px-6 font-semibold shadow-xs">
                                    {processing ? 'Envoi en cours…' : 'Envoyer mon message'}
                                </Button>
                            </div>
                        </form>
                    </div>

                    {/* COORDONNÉES ET CONTACTS RAPIDES */}
                    <aside className="space-y-6">
                        <Card className="border border-line shadow-2xs">
                            <span className="text-[11px] font-bold uppercase tracking-wider text-accent">
                                Coordonnées Officielles
                            </span>
                            <h3 className="mt-1 font-display text-base font-bold text-ink">
                                Maison MALKUTHAR
                            </h3>

                            <dl className="mt-4 space-y-4 text-xs sm:text-sm">
                                <div className="border-b border-line/60 pb-3">
                                    <dt className="text-ink-3 text-[11px] font-semibold uppercase">Email direct</dt>
                                    <dd className="mt-0.5">
                                        <a href={`mailto:${contact.email}`} className="font-semibold text-ink hover:text-accent underline decoration-accent/40 underline-offset-4">
                                            {contact.email}
                                        </a>
                                    </dd>
                                </div>

                                {contact.whatsapp && (
                                    <div className="border-b border-line/60 pb-3">
                                        <dt className="text-ink-3 text-[11px] font-semibold uppercase">Assistance WhatsApp</dt>
                                        <dd className="mt-0.5 flex items-center justify-between">
                                            <span className="font-semibold text-ink">{contact.whatsapp}</span>
                                            <a
                                                href={`https://wa.me/${contact.whatsapp.replace(/\D/g, '')}`}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="inline-flex items-center gap-1 rounded-lg bg-emerald-600/10 px-2.5 py-1 text-xs font-bold text-emerald-700 hover:bg-emerald-600/20"
                                            >
                                                <span>Discuter</span>
                                                <span>→</span>
                                            </a>
                                        </dd>
                                    </div>
                                )}

                                {contact.phone && (
                                    <div className="border-b border-line/60 pb-3">
                                        <dt className="text-ink-3 text-[11px] font-semibold uppercase">Téléphone</dt>
                                        <dd className="mt-0.5 font-semibold text-ink">{contact.phone}</dd>
                                    </div>
                                )}

                                <div className="border-b border-line/60 pb-3">
                                    <dt className="text-ink-3 text-[11px] font-semibold uppercase">Horaires d'ouverture</dt>
                                    <dd className="mt-0.5 text-ink-2">{contact.hours}</dd>
                                </div>

                                <div>
                                    <dt className="text-ink-3 text-[11px] font-semibold uppercase">Siège & Adresse</dt>
                                    <dd className="mt-0.5 text-ink-2">{contact.company} — {contact.address}</dd>
                                </div>
                            </dl>
                        </Card>

                        <div className="rounded-2xl border border-line-strong/50 bg-[#F9FAFB] p-5 text-xs text-ink-2 shadow-2xs">
                            <span className="font-bold text-ink">Espace Client :</span> Pour le suivi en direct de vos commandes et le téléchargement de vos produits numériques, accédez directement à{' '}
                            <a href="/mon-compte" className="font-semibold text-accent underline decoration-accent/40 underline-offset-2 hover:decoration-accent">
                                votre espace personnel
                            </a>.
                        </div>
                    </aside>
                </div>
            </div>
        </>
    );
}

Contact.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
