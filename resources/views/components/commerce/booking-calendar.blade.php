@props(['product'])

{{-- §17-§18 — choix d'une date puis d'un créneau réel avant l'ajout au
     panier. Émet l'événement `slot-picked` { date, time } consommé par la fiche. --}}
<div
    x-data="{
        date: '',
        time: '',
        slots: [],
        loading: false,
        error: '',
        async loadSlots() {
            this.time = ''; this.slots = []; this.error = '';
            if (! this.date) return;
            this.loading = true;
            try {
                const r = await fetch(`{{ route('services.availability', $product) }}?date=${this.date}`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (r.ok) { this.slots = (await r.json()).slots ?? []; }
                else { this.error = 'Impossible de charger les créneaux.'; }
            } catch (e) { this.error = 'Erreur réseau.'; }
            this.loading = false;
        },
        pick(t) { this.time = t; $dispatch('slot-picked', { date: this.date, time: t }); },
    }"
    {{ $attributes->merge(['class' => 'rounded-2xl border border-line bg-surface p-4']) }}
>
    <h3 class="text-base font-semibold text-ink">Choisir un créneau</h3>

    <label class="mt-3 block text-sm font-semibold text-ink-2">
        Date
        <input type="date" x-model="date" x-on:change="loadSlots()"
               min="{{ now()->toDateString() }}"
               class="mt-1.5 h-11 w-full rounded-xl border border-line-strong bg-surface px-3 text-[15px] text-ink focus:border-accent focus:outline-none focus:ring-4 focus:ring-accent/15">
    </label>

    <div class="mt-4" x-show="date">
        <p class="text-sm text-ink-3" x-show="loading">Chargement…</p>
        <p class="text-sm text-rust" x-show="error" x-text="error"></p>
        <p class="text-sm text-rust" x-show="!loading && !error && slots.length === 0" x-cloak>
            Aucun créneau disponible ce jour-là.
        </p>
        <div class="flex flex-wrap gap-2" x-show="slots.length > 0" x-cloak>
            <template x-for="s in slots" :key="s">
                <button type="button" x-on:click="pick(s)"
                        class="h-11 min-w-[4.5rem] rounded-full border px-4 text-sm font-medium transition-colors"
                        :class="time === s ? 'border-deep bg-deep text-white' : 'border-line-strong text-ink hover:border-ink'" :aria-pressed="time === s"
                        x-text="s"></button>
            </template>
        </div>
    </div>

    <p class="mt-3 text-sm text-ink-2" x-show="date && time" x-cloak>
        Créneau choisi : <span class="font-semibold text-ink" x-text="new Date(date).toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long' }) + ' à ' + time"></span>
    </p>
</div>
