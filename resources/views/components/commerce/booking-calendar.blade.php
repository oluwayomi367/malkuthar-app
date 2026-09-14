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
    {{ $attributes->merge(['class' => 'rounded-lg border border-line bg-ground p-4']) }}
>
    <p class="text-[13px] font-semibold uppercase tracking-[0.08em] text-ink-3">Choisir un créneau</p>

    <label class="mt-3 block text-[13px] font-semibold text-ink-2">
        Date
        <input type="date" x-model="date" x-on:change="loadSlots()"
               min="{{ now()->toDateString() }}"
               class="mt-1 h-11 w-full rounded-md border border-line-strong/25 bg-ground px-3 text-[15px] text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink/10">
    </label>

    <div class="mt-4" x-show="date">
        <p class="text-[13px] text-ink-3" x-show="loading">Chargement…</p>
        <p class="text-[13px] text-rust" x-show="error" x-text="error"></p>
        <p class="text-[13px] text-rust" x-show="!loading && !error && slots.length === 0" x-cloak>
            Aucun créneau disponible ce jour-là.
        </p>
        <div class="flex flex-wrap gap-2" x-show="slots.length > 0" x-cloak>
            <template x-for="s in slots" :key="s">
                <button type="button" x-on:click="pick(s)"
                        class="h-11 rounded-md border px-3 text-sm transition-colors"
                        :class="time === s ? 'border-ink bg-ink text-white' : 'border-line-strong/25 text-ink hover:border-ink'"
                        x-text="s"></button>
            </template>
        </div>
    </div>

    <p class="mt-3 text-[13px] text-ink-2" x-show="date && time" x-cloak>
        Créneau choisi : <span class="font-semibold text-ink" x-text="new Date(date).toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long' }) + ' à ' + time"></span>
    </p>
</div>
