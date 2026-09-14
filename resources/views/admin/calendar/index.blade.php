@extends('layouts.admin')

@section('title', 'Administration — Calendrier')
@section('eyebrow', 'Services')
@section('heading', 'Calendrier')

@section('content')
@php
    $scopes = ['upcoming' => 'À venir', 'past' => 'Passés', 'all' => 'Tous'];
    // Agenda groupé par jour pour la vue « À venir » / « Tous ».
    $byDay = $bookings->getCollection()->groupBy(fn ($b) => $b->reservation_datetime->format('Y-m-d'));
@endphp

{{-- ============ Disponibilité des services (§18) ============ --}}
<section class="mb-6 rounded-2xl border border-black/10 bg-surface p-5">
    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Disponibilité des services (§18)</p>
    <p class="mt-1 text-xs text-ink-3">Jours &amp; horaires, durée, capacité, pauses et jours indisponibles — se règlent sur chaque service.</p>
    @if ($services->isEmpty())
        <p class="mt-3 text-sm text-ink-3">Aucun service pour l'instant.</p>
    @else
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($services as $s)
                <a href="{{ route('admin.products.edit', $s) }}#availability"
                   class="inline-flex items-center gap-2 rounded-xl border border-black/10 px-3 py-2 text-sm text-ink transition-colors hover:border-black/20 hover:bg-black/[0.03]">
                    <span class="font-medium">{{ $s->name }}</span>
                    <span class="text-xs text-ink-3">
                        {{ $s->duration_minutes ?: 60 }} min ·
                        @if ($s->schedules_count > 0) {{ $s->schedules_count }} jour(s) @else <span class="text-rust">calendrier vide</span> @endif
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</section>

{{-- ============ Agenda des rendez-vous (§19) ============ --}}
<form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
    <x-ui.select name="scope" label="Période" :selected="$scope" :options="$scopes" onchange="this.form.submit()" />
    <x-ui.select name="product" label="Service" :selected="$productId" placeholder="Tous les services"
        :options="$services->mapWithKeys(fn ($s) => [$s->id => $s->name])->all()" onchange="this.form.submit()" />
    @if ($scope !== 'upcoming' || $productId)
        <a href="{{ route('admin.calendar.index') }}" class="pb-2.5 text-sm text-ink-3 hover:text-ink">Réinitialiser</a>
    @endif
</form>

@if ($bookings->isEmpty())
    <x-ui.empty-state title="Aucun rendez-vous" text="Les réservations de services payées apparaîtront ici." />
@else
    <div class="space-y-6">
        @foreach ($byDay as $day => $items)
            <div>
                <p class="mb-2 font-display text-sm font-semibold text-ink">{{ \Illuminate\Support\Carbon::parse($day)->translatedFormat('l d F Y') }}</p>
                <div class="space-y-3">
                    @foreach ($items as $b)
                        @php $when = $b->reservation_datetime; @endphp
                        <div class="rounded-2xl border border-black/10 bg-surface p-4" x-data="{ edit: false }">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-display text-[15px] font-semibold text-ink">
                                        <span class="text-accent">{{ $when->format('H:i') }}</span> · {{ $b->product_name }}
                                    </p>
                                    <p class="mt-1 text-xs text-ink-3">
                                        {{ $b->product?->duration_minutes ? $b->product->duration_minutes.' min' : '' }}
                                        @if ($b->product?->meeting_link) · visio @elseif ($b->product?->location) · {{ $b->product->location }} @endif
                                        · client :
                                        @if ($b->order?->user){{ $b->order->user->first_name }} {{ $b->order->user->last_name }}@else invité @endif
                                        · <span class="font-mono">{{ $b->order?->order_number }}</span>
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" x-on:click="edit = !edit"
                                            class="h-9 rounded-lg border border-black/12 px-3 text-xs font-semibold text-ink hover:bg-black/[0.03]">Déplacer</button>
                                    <form method="POST" action="{{ route('admin.calendar.cancel', $b) }}"
                                          onsubmit="return confirm('Annuler ce rendez-vous ? Le client sera notifié.')">
                                        @csrf
                                        <button class="h-9 rounded-lg border border-rust/40 px-3 text-xs font-semibold text-rust hover:bg-rust/10">Annuler</button>
                                    </form>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.calendar.reschedule', $b) }}" x-show="edit" x-cloak
                                  class="mt-3 flex flex-wrap items-end gap-2 border-t border-black/[0.06] pt-3">
                                @csrf
                                <label class="flex flex-col gap-1 text-[11px] text-ink-3">Nouvelle date
                                    <input type="date" name="reservation_date" value="{{ $when->format('Y-m-d') }}" required
                                           class="h-9 rounded-lg border border-black/10 bg-surface px-2 text-sm text-ink">
                                </label>
                                <label class="flex flex-col gap-1 text-[11px] text-ink-3">Heure
                                    <input type="time" name="reservation_time" value="{{ $when->format('H:i') }}" required
                                           class="h-9 rounded-lg border border-black/10 bg-surface px-2 text-sm text-ink">
                                </label>
                                <x-ui.button type="submit" variant="secondary">Déplacer &amp; notifier</x-ui.button>
                                @error('reservation_time')<p class="w-full text-xs text-rust">{{ $message }}</p>@enderror
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <x-ui.pagination :paginator="$bookings" />
@endif
@endsection
