import { useState } from 'react';

const WEEKDAY_MONTH = { weekday: 'long', day: 'numeric', month: 'long' };

/** §17-§18 — choix d'une date puis d'un créneau réel. Appelle `onPick(date, time)`. */
export default function BookingCalendar({ product, onPick, className = '' }) {
    const [date, setDate] = useState('');
    const [time, setTime] = useState('');
    const [slots, setSlots] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const loadSlots = async (d) => {
        setTime('');
        setSlots([]);
        setError('');
        if (!d) return;
        setLoading(true);
        try {
            const r = await fetch(`${route('services.availability', product.id)}?date=${d}`, {
                headers: { Accept: 'application/json' },
            });
            if (r.ok) {
                setSlots((await r.json()).slots ?? []);
            } else {
                setError('Impossible de charger les créneaux.');
            }
        } catch {
            setError('Erreur réseau.');
        }
        setLoading(false);
    };

    const pick = (t) => {
        setTime(t);
        onPick?.(date, t);
    };

    return (
        <div className={['rounded-xl border border-line bg-ground p-4', className].filter(Boolean).join(' ')}>
            <h3 className="text-base font-semibold text-ink">Choisir un créneau</h3>

            <label className="mt-3 block text-sm font-medium text-ink-2" htmlFor={`rdv-date-${product.id}`}>Date</label>
            <input id={`rdv-date-${product.id}`} type="date" value={date} min={new Date().toISOString().slice(0, 10)}
                   onChange={(e) => { setDate(e.target.value); loadSlots(e.target.value); }}
                   className="mt-1.5 h-11 w-full rounded-xl border border-line-strong bg-surface px-3 text-base text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" />

            {date && (
                <div className="mt-4" aria-live="polite">
                    {loading && <p className="text-sm text-ink-3">Chargement des créneaux…</p>}
                    {!loading && error && <p className="text-sm text-rust">{error}</p>}
                    {!loading && !error && slots.length === 0 && (
                        <p className="text-sm text-ink-2">Aucun créneau disponible ce jour-là. Essayez une autre date.</p>
                    )}
                    {!loading && slots.length > 0 && (
                        <>
                            <p className="text-sm font-medium text-ink-2">Horaires disponibles</p>
                            <div className="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-4" role="group" aria-label="Horaires disponibles">
                                {slots.map((s) => {
                                    const on = time === s;
                                    return (
                                        <button key={s} type="button" onClick={() => pick(s)} aria-pressed={on}
                                                className={['inline-flex min-h-11 items-center justify-center gap-1.5 rounded-xl border px-2 text-sm font-medium transition-colors',
                                                    on ? 'border-deep bg-deep text-white' : 'border-line-strong bg-surface text-ink hover:border-ink'].join(' ')}>
                                            {on && (
                                                <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                                    <path d="M5 12.5l4.5 4.5L19 7.5" />
                                                </svg>
                                            )}
                                            {s}
                                        </button>
                                    );
                                })}
                            </div>
                        </>
                    )}
                </div>
            )}

            {date && time && (
                <p className="mt-4 rounded-lg bg-surface px-3 py-2.5 text-sm text-ink-2" aria-live="polite">
                    Créneau choisi : <span className="font-semibold text-ink">
                        {new Date(date).toLocaleDateString('fr-FR', WEEKDAY_MONTH)} à {time}
                    </span>
                </p>
            )}
        </div>
    );
}
