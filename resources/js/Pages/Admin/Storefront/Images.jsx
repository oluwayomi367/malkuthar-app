import { useRef, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

/** Un emplacement d'image : aperçu, remplacement par envoi de fichier, retour à l'image d'origine. */
function SlotCard({ slot }) {
    const input = useRef(null);
    const [preview, setPreview] = useState(null);
    const form = useForm({ image: null });

    const pick = (e) => {
        const file = e.target.files?.[0] ?? null;
        form.setData('image', file);
        setPreview(file ? URL.createObjectURL(file) : null);
    };

    const cancel = () => {
        form.reset();
        form.clearErrors();
        setPreview(null);
        if (input.current) input.current.value = '';
    };

    const submit = (e) => {
        e.preventDefault();
        form.post(route('admin.storefront-images.update', slot.key), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: cancel,
        });
    };

    const reset = () => {
        router.delete(route('admin.storefront-images.destroy', slot.key), { preserveScroll: true });
    };

    const inputId = `image-${slot.key.replace('.', '-')}`;

    return (
        <li className="flex flex-col overflow-hidden rounded-2xl border border-night-line bg-night-2">
            <div className="relative aspect-[16/10] bg-night">
                <img src={preview ?? slot.url} alt="" loading="lazy" className="h-full w-full object-cover" />
                <span className={['absolute left-3 top-3 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                    preview ? 'bg-accent text-white' : slot.custom ? 'bg-sage text-white' : 'bg-night-2 text-night-ink-2'].join(' ')}>
                    {preview ? 'Aperçu, non enregistré' : slot.custom ? 'Image personnalisée' : "Image d'origine"}
                </span>
            </div>

            <form onSubmit={submit} className="flex flex-1 flex-col gap-3 p-4">
                <div>
                    <h3 className="text-sm font-semibold text-night-ink">{slot.label}</h3>
                    <p className="mt-0.5 text-xs text-night-ink-2">{slot.where}</p>
                </div>

                <label htmlFor={inputId} className="sr-only">Nouvelle image pour {slot.label}</label>
                <input ref={input} id={inputId} type="file" accept="image/jpeg,image/png,image/webp,image/avif,image/gif" onChange={pick}
                       className="block w-full text-xs text-night-ink-2 file:mr-3 file:min-h-9 file:cursor-pointer file:rounded-full file:border file:border-night-line file:bg-night file:px-3 file:text-xs file:font-semibold file:text-night-ink hover:file:bg-night-line" />
                {form.errors.image && <p className="text-xs font-medium text-rust">{form.errors.image}</p>}

                <div className="mt-auto flex flex-wrap items-center gap-2 pt-1">
                    {form.data.image ? (
                        <>
                            <button type="submit" disabled={form.processing}
                                    className="inline-flex min-h-10 items-center rounded-full bg-accent px-4 text-xs font-semibold text-white hover:bg-accent-2 disabled:opacity-50">
                                {form.processing ? 'Envoi…' : 'Enregistrer'}
                            </button>
                            <button type="button" onClick={cancel}
                                    className="inline-flex min-h-10 items-center rounded-full px-3 text-xs font-medium text-night-ink-2 hover:text-night-ink">
                                Annuler
                            </button>
                        </>
                    ) : (
                        <button type="button" onClick={() => input.current?.click()}
                                className="inline-flex min-h-10 items-center rounded-full border border-night-line px-4 text-xs font-semibold text-night-ink hover:bg-night">
                            Remplacer l'image
                        </button>
                    )}
                    {slot.custom && !form.data.image && (
                        <button type="button" onClick={reset}
                                className="ml-auto inline-flex min-h-10 items-center px-2 text-xs font-medium text-night-ink-2 underline underline-offset-2 hover:text-rust">
                            Revenir à l'image d'origine
                        </button>
                    )}
                </div>
            </form>
        </li>
    );
}

export default function Images({ groups = [] }) {
    return (
        <AdminLayout title="Images de la vitrine" eyebrow="Vitrine" heading="Images de la vitrine">
            <p className="max-w-3xl text-sm text-night-ink-2">
                Remplacez les photos et bannières des pages publiques. Formats acceptés : JPG, PNG, WebP, AVIF ou GIF, 5 Mo maximum.
                Une image envoyée remplace celle d'origine partout où cet emplacement est utilisé ; vous pouvez toujours revenir à l'image d'origine.
            </p>

            <nav aria-label="Pages" className="mt-6 flex flex-wrap gap-2">
                {groups.map((g, i) => (
                    <a key={g.page} href={`#page-${i}`}
                       className="inline-flex min-h-10 items-center rounded-full border border-night-line bg-night-2 px-4 text-sm font-medium text-night-ink hover:bg-night-line/50">
                        {g.page}
                    </a>
                ))}
            </nav>

            <div className="mt-8 space-y-12">
                {groups.map((g, i) => (
                    <section key={g.page} id={`page-${i}`} aria-labelledby={`titre-${i}`} className="scroll-mt-24">
                        <h2 id={`titre-${i}`} className="text-lg font-semibold text-night-ink">{g.page}</h2>
                        <ul className="mt-4 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                            {g.slots.map((slot) => <SlotCard key={slot.key} slot={slot} />)}
                        </ul>
                    </section>
                ))}
            </div>
        </AdminLayout>
    );
}
