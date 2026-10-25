import { Head, useForm } from '@inertiajs/react';
import StorefrontLayout from '../Layouts/StorefrontLayout';
import { useImages } from '../lib/images';
import Button from '../Components/ui/Button';
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

function Coordonnee({ label, children }) {
    return (
        <div className="border-t border-line py-4 first:border-t-0 first:pt-0">
            <dt className="text-sm text-ink-3">{label}</dt>
            <dd className="mt-1 text-[15px] text-ink">{children}</dd>
        </div>
    );
}

export default function Contact({ contact }) {
    const img = useImages();
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '', email: '', subject: '', message: '', website: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('contact.store'), { onSuccess: () => reset('message') });
    };

    return (
        <>
            <Head title="Contact — MALKUTHAR">
                <meta
                    name="description"
                    content="Contactez l'équipe MALKUTHAR : commandes, produits, réservations ou partenariats."
                />
            </Head>

            <section className="border-b border-line">
                <div className="mx-auto grid max-w-[1280px] items-center gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.1fr_1fr] lg:gap-16 lg:py-20">
                    <div>
                        <p className="text-sm font-medium text-accent-2">Contact</p>
                        <h1 className="mt-3 text-[clamp(2.25rem,5vw,3.5rem)] font-semibold leading-[1.06] text-ink">
                            Une question ? Écrivez-nous.
                        </h1>
                        <p className="mt-6 max-w-xl text-base leading-relaxed text-ink-2 sm:text-lg">
                            Suivi de commande, question sur un produit, réservation ou partenariat :
                            notre équipe vous répond en général sous 24 heures ouvrées.
                        </p>
                    </div>
                    <div className="overflow-hidden rounded-2xl bg-ground-alt">
                        <img
                            src={img('contact.hero')}
                            alt="Accueil de la maison MALKUTHAR"
                            className="aspect-[4/3] h-full w-full object-cover"
                        />
                    </div>
                </div>
            </section>

            <div className="mx-auto grid max-w-[1280px] gap-10 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[1.4fr_1fr] lg:gap-16">
                <section aria-labelledby="form-titre" className="rounded-2xl border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(28,25,23,0.04)] sm:p-8">
                    <h2 id="form-titre" className="text-3xl text-ink">Envoyer un message</h2>
                    <p className="mt-2 text-sm text-ink-2">Les champs marqués d'un astérisque sont obligatoires.</p>

                    <form onSubmit={submit} className="mt-8 grid gap-5" noValidate>
                        <div className="grid gap-5 sm:grid-cols-2">
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
                                label="Votre adresse e-mail"
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
                            placeholder="Choisissez un sujet"
                            options={SUBJECTS}
                            value={data.subject}
                            onChange={(e) => setData('subject', e.target.value)}
                            error={errors.subject}
                        />

                        <Textarea
                            name="message"
                            label="Votre message"
                            rows={6}
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

                        <div>
                            <Button variant="primary" type="submit" disabled={processing} className="w-full sm:w-auto">
                                {processing ? 'Envoi en cours…' : 'Envoyer mon message'}
                            </Button>
                        </div>
                    </form>
                </section>

                <aside aria-labelledby="coord-titre" className="space-y-8">
                    <div>
                        <h2 id="coord-titre" className="text-3xl text-ink">Coordonnées</h2>
                        <dl className="mt-6">
                            <Coordonnee label="E-mail">
                                <a href={`mailto:${contact.email}`} className="font-medium underline decoration-line-strong underline-offset-4 hover:decoration-accent">
                                    {contact.email}
                                </a>
                            </Coordonnee>

                            {contact.whatsapp && (
                                <Coordonnee label="WhatsApp">
                                    <a
                                        href={`https://wa.me/${contact.whatsapp.replace(/\D/g, '')}`}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="font-medium underline decoration-line-strong underline-offset-4 hover:decoration-accent"
                                    >
                                        {contact.whatsapp}
                                    </a>
                                </Coordonnee>
                            )}

                            {contact.phone && (
                                <Coordonnee label="Téléphone">
                                    <a href={`tel:${contact.phone.replace(/\s/g, '')}`} className="font-medium">{contact.phone}</a>
                                </Coordonnee>
                            )}

                            <Coordonnee label="Horaires">{contact.hours}</Coordonnee>
                            <Coordonnee label="Adresse">{contact.company} — {contact.address}</Coordonnee>
                        </dl>
                    </div>

                    <p className="rounded-2xl bg-ground-alt p-5 text-sm leading-relaxed text-ink-2">
                        Pour suivre une commande ou retrouver vos téléchargements, rendez-vous dans{' '}
                        <a href="/mon-compte" className="font-semibold text-ink underline decoration-line-strong underline-offset-4 hover:decoration-accent">
                            votre espace client
                        </a>.
                    </p>
                </aside>
            </div>
        </>
    );
}

Contact.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
