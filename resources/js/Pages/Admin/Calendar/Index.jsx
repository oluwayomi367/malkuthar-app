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
            <div className="mb-6 rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-xs">
                <p className="text-[11px] font-bold uppercase tracking-wider text-zinc-500">
                    Disponibilité des services (§18)
                </p>
                <p className="mt-1 text-xs text-zinc-500">
                    Jours &amp; horaires, durée, pauses et jours indisponibles — se règlent sur la fiche de chaque service.
                </p>

                <div className="mt-3.5 flex flex-wrap gap-2">
                    {services && services.length > 0 ? (
                        services.map((s) => (
                            <Link
                                key={s.id}
                                href={`${route('admin.products.edit', s.id)}#availability`}
                                className="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-xs font-medium text-zinc-800 transition-colors hover:border-accent hover:bg-white shadow-2xs"
                            >
                                <span className="font-semibold text-zinc-900">{s.name}</span>
                                <span className="text-zinc-400">
                                    {s.duration_minutes || 60} min ·{' '}
                                    {s.schedules_count > 0 ? (
                                        <span className="text-emerald-600">{s.schedules_count} jour(s)</span>
                                    ) : (
                                        <span className="text-rose-600">calendrier vide</span>
                                    )}
                                </span>
                            </Link>
                        ))
                    ) : (
                        <p className="text-xs text-zinc-400">Aucun service créé.</p>
                    )}
                </div>
            </div>

            {/* 2. FILTRES AGENDA */}
            <div className="mb-6 flex flex-wrap items-center gap-3">
                <div className="flex items-center rounded-xl border border-zinc-200 bg-white p-1 shadow-2xs text-xs">
                    <button
                        type="button"
                        onClick={() => handleFilter('upcoming')}
                        className={[
                            'rounded-lg px-3 py-1 font-semibold transition-colors',
                            scope === 'upcoming' ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:text-zinc-900',
                        ].join(' ')}
                    >
                        À venir
                    </button>
                    <button
                        type="button"
                        onClick={() => handleFilter('past')}
                        className={[
                            'rounded-lg px-3 py-1 font-semibold transition-colors',
                            scope === 'past' ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:text-zinc-900',
                        ].join(' ')}
                    >
                        Passés
                    </button>
                    <button
                        type="button"
                        onClick={() => handleFilter('all')}
                        className={[
                            'rounded-lg px-3 py-1 font-semibold transition-colors',
                            scope === 'all' ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:text-zinc-900',
                        ].join(' ')}
                    >
                        Tous
                    </button>
                </div>

                <select
                    value={productId || ''}
                    onChange={(e) => handleFilter(undefined, e.target.value)}
                    className="rounded-xl border border-zinc-200 bg-white px-3 py-1.5 text-xs text-zinc-800 shadow-2xs focus:border-accent focus:outline-none"
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
                        className="text-xs text-zinc-500 hover:text-zinc-800 underline"
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
                                <h3 className="mb-2 font-display text-sm font-bold capitalize text-zinc-800">
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
                                                className="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-xs flex flex-wrap items-center justify-between gap-4"
                                            >
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-mono text-sm font-bold text-accent">
                                                            {time}
                                                        </span>
                                                        <span className="font-semibold text-zinc-900">
                                                            {b.product_name}
                                                        </span>
                                                    </div>
                                                    <div className="mt-1 text-xs text-zinc-500">
                                                        Client :{' '}
                                                        <span className="font-medium text-zinc-700">
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
                                                        className="rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-semibold text-zinc-700 hover:bg-white shadow-2xs"
                                                    >
                                                        Déplacer
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setCancelingBooking(b)}
                                                        className="rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 shadow-2xs"
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
                <div className="rounded-2xl border border-dashed border-zinc-300 bg-white/70 p-12 text-center text-xs text-zinc-500">
                    Aucun rendez-vous pour cette période ou ce filtre.
                </div>
            )}

            {/* MODAL DÉPLACER */}
            {editingBooking && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-xs">
                    <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-zinc-200">
                        <h3 className="font-display text-base font-bold text-zinc-900">
                            Déplacer le rendez-vous
                        </h3>
                        <p className="mt-1 text-xs text-zinc-600">
                            {editingBooking.product_name} pour {editingBooking.order?.user?.first_name}{' '}
                            {editingBooking.order?.user?.last_name}.
                        </p>
                        <form onSubmit={submitReschedule} className="mt-4 space-y-3.5">
                            <div>
                                <label className="text-xs font-medium text-zinc-700">
                                    Nouvelle date et heure
                                </label>
                                <input
                                    type="datetime-local"
                                    required
                                    value={rescheduleForm.data.datetime}
                                    onChange={(e) => rescheduleForm.setData('datetime', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-xs text-zinc-900 shadow-2xs focus:border-accent focus:outline-none"
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
                                    className="rounded border-zinc-300 text-accent focus:ring-accent"
                                />
                                <label htmlFor="notify_reschedule" className="text-xs text-zinc-700">
                                    Notifier le client par email du changement
                                </label>
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setEditingBooking(null)}
                                    className="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50"
                                >
                                    Fermer
                                </button>
                                <button
                                    type="submit"
                                    disabled={rescheduleForm.processing}
                                    className="rounded-lg bg-accent px-4 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-accent-2"
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
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-xs">
                    <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-zinc-200">
                        <h3 className="font-display text-base font-bold text-rose-900">
                            Annuler la réservation
                        </h3>
                        <p className="mt-1 text-xs text-zinc-600">
                            Le créneau sera libéré. Vous pouvez préciser un motif pour le client.
                        </p>
                        <form onSubmit={submitCancel} className="mt-4 space-y-3.5">
                            <div>
                                <label className="text-xs font-medium text-zinc-700">Motif</label>
                                <input
                                    type="text"
                                    placeholder="Ex. Indisponibilité du praticien / Report demandé"
                                    value={cancelForm.data.reason}
                                    onChange={(e) => cancelForm.setData('reason', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-xs text-zinc-900 shadow-2xs focus:border-rose-500 focus:outline-none"
                                />
                            </div>
                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="notify_cancel"
                                    checked={cancelForm.data.notify_client}
                                    onChange={(e) => cancelForm.setData('notify_client', e.target.checked)}
                                    className="rounded border-zinc-300 text-rose-600 focus:ring-rose-500"
                                />
                                <label htmlFor="notify_cancel" className="text-xs text-zinc-700">
                                    Notifier le client de l'annulation
                                </label>
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setCancelingBooking(null)}
                                    className="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50"
                                >
                                    Fermer
                                </button>
                                <button
                                    type="submit"
                                    disabled={cancelForm.processing}
                                    className="rounded-lg bg-rose-600 px-4 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-rose-700"
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
