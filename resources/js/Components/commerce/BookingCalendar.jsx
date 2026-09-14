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
        <div className={['rounded-lg border border-line bg-ground p-4', className].filter(Boolean).join(' ')}>
            <p className="text-[13px] font-semibold uppercase tracking-[0.08em] text-ink-3">Choisir un créneau</p>

            <label className="mt-3 block text-[13px] font-semibold text-ink-2">
                Date
                <input type="date" value={date} min={new Date().toISOString().slice(0, 10)}
                       onChange={(e) => { setDate(e.target.value); loadSlots(e.target.value); }}
                       className="mt-1 h-11 w-full rounded-md border border-line-strong/25 bg-ground px-3 text-[15px] text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink/10" />
            </label>

            {date && (
                <div className="mt-4">
                    {loading && <p className="text-[13px] text-ink-3">Chargement…</p>}
                    {!loading && error && <p className="text-[13px] text-rust">{error}</p>}
                    {!loading && !error && slots.length === 0 && (
                        <p className="text-[13px] text-rust">Aucun créneau disponible ce jour-là.</p>
                    )}
                    {!loading && slots.length > 0 && (
                        <div className="flex flex-wrap gap-2">
                            {slots.map((s) => (
                                <button key={s} type="button" onClick={() => pick(s)}
                                        className={['h-11 rounded-md border px-3 text-sm transition-colors', time === s ? 'border-ink bg-ink text-white' : 'border-line-strong/25 text-ink hover:border-ink'].join(' ')}>
                                    {s}
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            )}

            {date && time && (
                <p className="mt-3 text-[13px] text-ink-2">
                    Créneau choisi : <span className="font-semibold text-ink">
                        {new Date(date).toLocaleDateString('fr-FR', WEEKDAY_MONTH)} à {time}
                    </span>
                </p>
            )}
        </div>
    );
}
