import React, { useState } from 'react';
import { Link, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({
    bookings = { data: [] },
    services = [],
    productId = '',
    scope = 'upcoming',
    heading = 'Calendrier',
    eyebrow = 'Services',
}) {
    const [editingBooking, setEditingBooking] = useState(null);
    const [cancelingBooking, setCancelingBooking] = useState(null);

    const rescheduleForm = useForm({
        datetime: '',
        notify_client: true,
    });

    const cancelForm = useForm({
        reason: '',
        notify_client: true,
    });

    const submitReschedule = (e) => {
        e.preventDefault();
        rescheduleForm.post(route('admin.calendar.reschedule', editingBooking.id), {
            onSuccess: () => {
                setEditingBooking(null);
                rescheduleForm.reset();
            },
        });
    };

    const submitCancel = (e) => {
        e.preventDefault();
        cancelForm.post(route('admin.calendar.cancel', cancelingBooking.id), {
            onSuccess: () => {
                setCancelingBooking(null);
                cancelForm.reset();
            },
        });
    };

    const handleFilter = (newScope, newProduct) => {
        router.get(
            route('admin.calendar.index'),
            {
                scope: newScope !== undefined ? newScope : scope,
                product: newProduct !== undefined ? newProduct : productId,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    // Group bookings by date string (YYYY-MM-DD)
    const groupedBookings = (bookings.data || []).reduce((acc, b) => {
        if (!b.reservation_datetime) return acc;
        const dateKey = b.reservation_datetime.split('T')[0] || b.reservation_datetime.slice(0, 10);
        if (!acc[dateKey]) acc[dateKey] = [];
        acc[dateKey].push(b);
        return acc;
    }, {});

    return (
        <AdminLayout title="Calendrier" eyebrow={eyebrow} heading={heading}>
            {/* 1. DISPONIBILITÉ DES SERVICES */}
            <div className="mb-6 rounded-2xl border border-night-line bg-night-2 p-5">
                <p className="text-xs font-bold uppercase tracking-wider text-night-ink-2">
                    Disponibilité des services (§18)
                </p>
                <p className="mt-1 text-xs text-night-ink-2">
                    Jours &amp; horaires, durée, pauses et jours indisponibles — se règlent sur la fiche de chaque service.
                </p>

                <div className="mt-3.5 flex flex-wrap gap-2">
                    {services && services.length > 0 ? (
                        services.map((s) => (
                            <Link
                                key={s.id}
                                href={`${route('admin.products.edit', s.id)}#availability`}
                                className="inline-flex items-center gap-2 rounded-full border border-night-line bg-night px-3 py-2 text-sm font-medium text-night-ink transition-colors hover:border-accent hover:bg-night-line/50 min-h-11"
                            >
                                <span className="font-semibold text-night-ink">{s.name}</span>
                                <span className="text-night-ink-2">
                                    {s.duration_minutes || 60} min ·{' '}
                                    {s.schedules_count > 0 ? (
                                        <span className="text-sage">{s.schedules_count} jour(s)</span>
                                    ) : (
                                        <span className="text-rust">calendrier vide</span>
                                    )}
                                </span>
                            </Link>
                        ))
                    ) : (
                        <p className="text-xs text-night-ink-2">Aucun service créé.</p>
                    )}
                </div>
            </div>

            {/* 2. FILTRES AGENDA */}
            <div className="mb-6 flex flex-wrap items-center gap-3">
                <div className="flex items-center rounded-xl border border-night-line bg-night-2 p-1 text-sm">
                    <button
                        type="button"
                        onClick={() => handleFilter('upcoming')}
                        className={[
                            'rounded-lg px-3 py-1 font-semibold transition-colors',
                            scope === 'upcoming' ? 'bg-accent text-white' : 'text-night-ink-2 hover:text-night-ink',
                        ].join(' ')}
                    >
                        À venir
                    </button>
                    <button
                        type="button"
                        onClick={() => handleFilter('past')}
                        className={[
                            'rounded-lg px-3 py-1 font-semibold transition-colors',
                            scope === 'past' ? 'bg-accent text-white' : 'text-night-ink-2 hover:text-night-ink',
                        ].join(' ')}
                    >
                        Passés
                    </button>
                    <button
                        type="button"
                        onClick={() => handleFilter('all')}
                        className={[
                            'rounded-lg px-3 py-1 font-semibold transition-colors',
                            scope === 'all' ? 'bg-accent text-white' : 'text-night-ink-2 hover:text-night-ink',
                        ].join(' ')}
                    >
                        Tous
                    </button>
                </div>

                <select
                    value={productId || ''}
                    onChange={(e) => handleFilter(undefined, e.target.value)}
                    className="rounded-xl border border-night-line bg-night px-3 py-2.5 text-sm text-night-ink focus:border-accent focus:outline-none min-h-11"
                >
                    <option value="">Tous les services</option>
                    {services.map((s) => (
                        <option key={s.id} value={s.id}>
                            {s.name}
                        </option>
                    ))}
                </select>

                {(scope !== 'upcoming' || productId) && (
                    <button
                        type="button"
                        onClick={() => handleFilter('upcoming', '')}
                        className="text-xs text-night-ink-2 hover:text-night-ink underline"
                    >
                        Réinitialiser
                    </button>
                )}
            </div>

            {/* 3. AGENDA GROUPÉ PAR JOUR */}
            {Object.keys(groupedBookings).length > 0 ? (
                <div className="space-y-6">
                    {Object.entries(groupedBookings).map(([dateKey, items]) => {
                        const dateFormatted = new Date(dateKey + 'T00:00:00').toLocaleDateString('fr-FR', {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric',
                        });

                        return (
                            <div key={dateKey}>
                                <h3 className="mb-2 font-sans text-sm font-semibold capitalize text-night-ink">
                                    {dateFormatted}
                                </h3>
                                <div className="space-y-2.5">
                                    {items.map((b) => {
                                        const dt = new Date(b.reservation_datetime);
                                        const time = dt.toLocaleTimeString('fr-FR', {
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        });

                                        return (
                                            <div
                                                key={b.id}
                                                className="rounded-2xl border border-night-line bg-night-2 p-4 flex flex-wrap items-center justify-between gap-4"
                                            >
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-mono text-sm font-bold text-accent-2">
                                                            {time}
                                                        </span>
                                                        <span className="font-semibold text-night-ink">
                                                            {b.product_name}
                                                        </span>
                                                    </div>
                                                    <div className="mt-1 text-sm text-night-ink-2">
                                                        Client :{' '}
                                                        <span className="font-medium text-night-ink-2">
                                                            {b.order?.user
                                                                ? `${b.order.user.first_name} ${b.order.user.last_name}`
                                                                : 'Invité'}
                                                        </span>{' '}
                                                        · Commande {b.order?.order_number}
                                                        {b.product?.location && ` · Lieu : ${b.product.location}`}
                                                    </div>
                                                </div>

                                                <div className="flex items-center gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            setEditingBooking(b);
                                                            rescheduleForm.setData(
                                                                'datetime',
                                                                b.reservation_datetime.slice(0, 16)
                                                            );
                                                        }}
                                                        className="rounded-full border border-night-line bg-night px-2.5 py-1 text-sm font-semibold text-night-ink-2 hover:bg-night-line/50 min-h-11"
                                                    >
                                                        Déplacer
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setCancelingBooking(b)}
                                                        className="rounded-full border border-rust bg-night-2 px-2.5 py-1 text-sm font-semibold text-rust hover:bg-rust-tint min-h-11"
                                                    >
                                                        Annuler
                                                    </button>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    })}
                </div>
            ) : (
                <div className="rounded-2xl border border-dashed border-night-ink-2/60 bg-night-2 p-12 text-center text-sm text-night-ink-2">
                    Aucun rendez-vous pour cette période ou ce filtre.
                </div>
            )}

            {/* MODAL DÉPLACER */}
            {editingBooking && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-night/80 p-4">
                    <div className="w-full max-w-md rounded-2xl bg-night-2 p-6 border border-night-line">
                        <h3 className="font-sans text-base font-semibold text-night-ink">
                            Déplacer le rendez-vous
                        </h3>
                        <p className="mt-1 text-xs text-night-ink-2">
                            {editingBooking.product_name} pour {editingBooking.order?.user?.first_name}{' '}
                            {editingBooking.order?.user?.last_name}.
                        </p>
                        <form onSubmit={submitReschedule} className="mt-4 space-y-3.5">
                            <div>
                                <label className="text-sm font-medium text-night-ink-2">
                                    Nouvelle date et heure
                                </label>
                                <input
                                    type="datetime-local"
                                    required
                                    value={rescheduleForm.data.datetime}
                                    onChange={(e) => rescheduleForm.setData('datetime', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-3 py-2.5 text-sm text-night-ink focus:border-accent focus:outline-none min-h-11"
                                />
                            </div>
                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="notify_reschedule"
                                    checked={rescheduleForm.data.notify_client}
                                    onChange={(e) =>
                                        rescheduleForm.setData('notify_client', e.target.checked)
                                    }
                                    className="rounded border-night-ink-2/60 text-accent-2 focus:ring-accent"
                                />
                                <label htmlFor="notify_reschedule" className="text-sm text-night-ink-2">
                                    Notifier le client par email du changement
                                </label>
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setEditingBooking(null)}
                                    className="rounded-full border border-night-ink-2/60 bg-night-2 px-3 py-1.5 text-sm font-medium text-night-ink-2 hover:bg-night-line/50 min-h-11"
                                >
                                    Fermer
                                </button>
                                <button
                                    type="submit"
                                    disabled={rescheduleForm.processing}
                                    className="rounded-full bg-accent px-4 py-1.5 text-sm font-semibold text-white hover:bg-accent-2 min-h-11"
                                >
                                    Confirmer le déplacement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* MODAL ANNULER */}
            {cancelingBooking && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-night/80 p-4">
                    <div className="w-full max-w-md rounded-2xl bg-night-2 p-6 border border-night-line">
                        <h3 className="font-sans text-base font-semibold text-rust">
                            Annuler la réservation
                        </h3>
                        <p className="mt-1 text-xs text-night-ink-2">
                            Le créneau sera libéré. Vous pouvez préciser un motif pour le client.
                        </p>
                        <form onSubmit={submitCancel} className="mt-4 space-y-3.5">
                            <div>
                                <label className="text-sm font-medium text-night-ink-2">Motif</label>
                                <input
                                    type="text"
                                    placeholder="Ex. Indisponibilité du praticien / Report demandé"
                                    value={cancelForm.data.reason}
                                    onChange={(e) => cancelForm.setData('reason', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-night-ink-2/60 px-3 py-2.5 text-sm text-night-ink focus:border-rust focus:outline-none min-h-11"
                                />
                            </div>
                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="notify_cancel"
                                    checked={cancelForm.data.notify_client}
                                    onChange={(e) => cancelForm.setData('notify_client', e.target.checked)}
                                    className="rounded border-night-ink-2/60 text-rust focus:ring-rust"
                                />
                                <label htmlFor="notify_cancel" className="text-sm text-night-ink-2">
                                    Notifier le client de l'annulation
                                </label>
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setCancelingBooking(null)}
                                    className="rounded-full border border-night-ink-2/60 bg-night-2 px-3 py-1.5 text-sm font-medium text-night-ink-2 hover:bg-night-line/50 min-h-11"
                                >
                                    Fermer
                                </button>
                                <button
                                    type="submit"
                                    disabled={cancelForm.processing}
                                    className="rounded-full bg-rust px-4 py-1.5 text-sm font-semibold text-white hover:bg-rust/85 min-h-11"
                                >
                                    Confirmer l'annulation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
