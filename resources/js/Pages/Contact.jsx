import { Head, useForm } from '@inertiajs/react';
import StorefrontLayout from '../Layouts/StorefrontLayout';
import Button from '../Components/ui/Button';
import Card from '../Components/ui/Card';
import Field from '../Components/ui/Field';
import Select from '../Components/ui/Select';
import Textarea from '../Components/ui/Textarea';

const SUBJECTS = {
    commande: 'Une commande',
    produit: 'Un produit',
    affiliation: "Le programme d'affiliation",
    vendeur: 'Devenir vendeur (Marketplace)',
    autre: 'Autre',
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
            <Head title="Contact">
                <meta name="description" content="Contactez l'équipe MALKUTHAR : questions sur une commande, un produit ou le programme d'affiliation." />
            </Head>

            <div className="mx-auto max-w-[1180px] px-4 py-12 sm:px-6 sm:py-16">
                <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-gold">Contact</p>
                <h1 className="mt-3 text-[28px] font-bold tracking-[-0.025em] text-ink sm:text-4xl">Une question&nbsp;?</h1>
                <p className="mt-3 max-w-lg text-ink-2">
                    Écrivez-nous — une commande, un produit, le programme d'affiliation, ou autre chose.
                    Nous répondons à l'adresse que vous indiquez.
                </p>

                <div className="mt-10 grid gap-10 lg:grid-cols-[1.4fr_1fr] lg:gap-16">
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field name="name" label="Votre nom" required autoComplete="name"
                                   value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} />
                            <Field name="email" label="Votre email" type="email" required autoComplete="email"
                                   value={data.email} onChange={(e) => setData('email', e.target.value)} error={errors.email} />
                        </div>

                        <Select name="subject" label="Sujet" required placeholder="Choisir un sujet…" options={SUBJECTS}
                                value={data.subject} onChange={(e) => setData('subject', e.target.value)} error={errors.subject} />

                        <Textarea name="message" label="Votre message" rows={6} required
                                  value={data.message} onChange={(e) => setData('message', e.target.value)} error={errors.message} />

                        {/* honeypot : caché aux humains, doit rester vide */}
                        <div className="hidden" aria-hidden="true">
                            <label>Ne pas remplir
                                <input type="text" tabIndex={-1} autoComplete="off"
                                       value={data.website} onChange={(e) => setData('website', e.target.value)} />
                            </label>
                        </div>

                        <div>
                            <Button variant="primary" type="submit" disabled={processing}>Envoyer le message</Button>
                        </div>
                    </form>

                    <aside>
                        <Card>
                            <p className="text-[11px] font-semibold uppercase tracking-[0.1em] text-ink-3">Coordonnées</p>
                            <dl className="mt-4 space-y-4 text-sm">
                                <div>
                                    <dt className="text-ink-3">Email</dt>
                                    <dd className="mt-0.5"><a href={`mailto:${contact.email}`} className="font-medium text-ink underline decoration-gold underline-offset-4">{contact.email}</a></dd>
                                </div>
                                {contact.whatsapp && (
                                    <div>
                                        <dt className="text-ink-3">WhatsApp</dt>
                                        <dd className="mt-0.5"><a href={`https://wa.me/${contact.whatsapp.replace(/\D/g, '')}`} className="font-medium text-ink underline decoration-gold underline-offset-4">{contact.whatsapp}</a></dd>
                                    </div>
                                )}
                                {contact.phone && (
                                    <div>
                                        <dt className="text-ink-3">Téléphone</dt>
                                        <dd className="mt-0.5 font-medium text-ink">{contact.phone}</dd>
                                    </div>
                                )}
                                <div>
                                    <dt className="text-ink-3">Horaires</dt>
                                    <dd className="mt-0.5 text-ink-2">{contact.hours}</dd>
                                </div>
                                <div>
                                    <dt className="text-ink-3">Adresse</dt>
                                    <dd className="mt-0.5 text-ink-2">{contact.company} — {contact.address}</dd>
                                </div>
                            </dl>
                        </Card>
                        <p className="mt-4 text-xs text-ink-3">
                            Pour le suivi d'une commande, connectez-vous à{' '}
                            <a href="/mon-compte" className="underline decoration-gold underline-offset-2">votre espace client</a>.
                        </p>
                    </aside>
                </div>
            </div>
        </>
    );
}

Contact.layout = (page) => <StorefrontLayout>{page}</StorefrontLayout>;
