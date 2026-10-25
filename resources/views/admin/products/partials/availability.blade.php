@php
    $weekdays = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 0 => 'Dimanche'];
    $initialWindows = $product->schedules->map(fn ($s) => [
        'weekday' => (int) $s->weekday,
        'start_time' => substr((string) $s->start_time, 0, 5),
        'end_time' => substr((string) $s->end_time, 0, 5),
        'capacity' => (int) ($s->capacity ?: 1),
    ])->values();
    $defaultCapacity = (int) ($product->schedules->max('capacity') ?: 1);
@endphp

<section id="availability" class="scroll-mt-24 rounded-2xl border border-night-line bg-night-2 p-5">
    <h2 class="font-sans text-base font-semibold text-night-ink">Calendrier (§18)</h2>
    <p class="mt-1 text-xs text-night-ink-2">
        Tout ce qui définit quand ce service est réservable : durée d'un rendez-vous, jours &amp; horaires
        disponibles, nombre de rendez-vous en parallèle, pauses et jours indisponibles.
    </p>

    {{-- ============ 1. Durée + créneaux hebdomadaires ============ --}}
    <form method="POST" action="{{ route('admin.services.schedule.update', $product) }}"
          class="mt-5" x-data="{ windows: {{ \Illuminate\Support\Js::from($initialWindows) }}, def: {{ $defaultCapacity }} }">
        @csrf @method('PUT')

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="flex flex-col gap-1 text-sm font-medium text-night-ink-2">
                Durée d'un rendez-vous (minutes)
                <input type="number" min="5" step="5" name="duration_minutes" value="{{ $product->duration_minutes ?: 60 }}"
                       class="h-10 rounded-lg border border-night-line bg-night-2 px-3 text-sm text-night-ink">
                <span class="text-xs text-night-ink-2">Découpe chaque plage horaire en créneaux de cette durée.</span>
            </label>
            <label class="flex flex-col gap-1 text-sm font-medium text-night-ink-2">
                Rendez-vous en parallèle (par défaut)
                <input type="number" min="1" x-model.number="def"
                       class="h-10 rounded-lg border border-night-line bg-night-2 px-3 text-sm text-night-ink">
                <span class="text-xs text-night-ink-2">Appliqué aux nouveaux créneaux ; ajustable ligne par ligne.</span>
            </label>
        </div>

        <p class="mb-2 mt-5 text-sm font-medium text-night-ink-2">Jours &amp; horaires disponibles</p>

        <template x-if="windows.length === 0">
            <p class="rounded-lg border border-dashed border-night-line px-3 py-4 text-center text-xs text-night-ink-2">Aucun jour disponible — le service n'est pas réservable.</p>
        </template>

        <div class="space-y-2">
            <template x-for="(w, i) in windows" :key="i">
                <div class="grid grid-cols-1 items-end gap-2 rounded-lg border border-night-line p-3 sm:grid-cols-[1.2fr_0.9fr_0.9fr_1fr_auto]">
                    <label class="flex flex-col gap-1 text-xs text-night-ink-2">Jour disponible
                        <select x-model.number="w.weekday" :name="`windows[${i}][weekday]`" class="h-9 rounded-lg border border-night-line bg-night-2 px-2 text-sm text-night-ink">
                            @foreach ($weekdays as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="flex flex-col gap-1 text-xs text-night-ink-2">Ouverture
                        <input type="time" x-model="w.start_time" :name="`windows[${i}][start_time]`" required class="h-9 rounded-lg border border-night-line bg-night-2 px-2 text-sm text-night-ink">
                    </label>
                    <label class="flex flex-col gap-1 text-xs text-night-ink-2">Fermeture
                        <input type="time" x-model="w.end_time" :name="`windows[${i}][end_time]`" required class="h-9 rounded-lg border border-night-line bg-night-2 px-2 text-sm text-night-ink">
                    </label>
                    <label class="flex flex-col gap-1 text-xs text-night-ink-2">RDV en parallèle
                        <input type="number" min="1" x-model.number="w.capacity" :name="`windows[${i}][capacity]`" class="h-9 rounded-lg border border-night-line bg-night-2 px-2 text-sm text-night-ink">
                    </label>
                    <button type="button" x-on:click="windows.splice(i, 1)" class="mb-1 text-xs text-rust hover:underline">Retirer</button>
                </div>
            </template>
        </div>

        <div class="mt-3 flex items-center justify-between">
            <button type="button" x-on:click="windows.push({ weekday: 1, start_time: '09:00', end_time: '17:00', capacity: def })"
                    class="text-xs font-semibold text-accent-2 hover:underline">+ Ajouter un jour</button>
            <x-ui.button type="submit" variant="secondary">Enregistrer le calendrier</x-ui.button>
        </div>
    </form>

    {{-- ============ 2. Pauses & jours indisponibles ============ --}}
    <div class="mt-6 border-t border-night-line pt-5">
        <p class="text-sm font-medium text-night-ink-2">Pauses &amp; jours indisponibles</p>

        @if ($product->timeOff->isNotEmpty())
            <ul class="mt-3 space-y-1.5 text-sm">
                @foreach ($product->timeOff as $off)
                    <li class="flex items-center justify-between gap-3 border-b border-night-line pb-1.5 last:border-0 last:pb-0">
                        <span class="text-night-ink">
                            {{ $off->date->translatedFormat('d/m/Y') }}
                            <span class="text-night-ink-2">
                                @if ($off->isFullDay())
                                    · jour indisponible
                                @else
                                    · pause {{ substr((string) $off->start_time, 0, 5) }}–{{ substr((string) $off->end_time, 0, 5) }}
                                @endif
                                @if ($off->reason) · {{ $off->reason }} @endif
                            </span>
                        </span>
                        <form method="POST" action="{{ route('admin.services.time-off.destroy', $off) }}">
                            @csrf @method('DELETE')
                            <button class="text-xs text-rust hover:underline">Retirer</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('admin.services.time-off.store') }}"
              class="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_1fr_1.4fr_auto] sm:items-end">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <label class="flex flex-col gap-1 text-xs text-night-ink-2">Date
                <input type="date" name="date" required class="h-9 rounded-lg border border-night-line bg-night-2 px-2 text-sm text-night-ink">
            </label>
            <label class="flex flex-col gap-1 text-xs text-night-ink-2">Début de pause
                <input type="time" name="start_time" class="h-9 rounded-lg border border-night-line bg-night-2 px-2 text-sm text-night-ink">
            </label>
            <label class="flex flex-col gap-1 text-xs text-night-ink-2">Fin de pause
                <input type="time" name="end_time" class="h-9 rounded-lg border border-night-line bg-night-2 px-2 text-sm text-night-ink">
            </label>
            <label class="flex flex-col gap-1 text-xs text-night-ink-2">Motif
                <input type="text" name="reason" placeholder="Congé, jour férié…" class="h-9 rounded-lg border border-night-line bg-night-2 px-2 text-sm text-night-ink placeholder:text-night-ink-2/70">
            </label>
            <x-ui.button type="submit" variant="secondary">Ajouter</x-ui.button>
        </form>
        <p class="mt-2 text-xs text-night-ink-2">Sans horaires : la <strong>journée entière</strong> devient indisponible. Avec horaires : c'est une <strong>pause</strong> ce jour-là.</p>
    </div>
</section>
