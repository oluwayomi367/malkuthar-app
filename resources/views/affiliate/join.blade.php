@extends('layouts.app')

@section('title', 'Devenir affilié')

@section('content')
<div class="mx-auto max-w-lg">
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Programme d'affiliation</p>
    <h1 class="mt-1 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Devenez affilié MALKUTHAR</h1>
    <p class="mt-2 text-sm text-ink-2">Recommandez les produits digitaux MALKUTHAR et gagnez sur trois niveaux. Aucun second compte : votre espace client devient aussi votre espace affilié.</p>

    <div class="mt-6 space-y-2">
        <div class="flex items-center justify-between rounded-xl border border-accent/25 bg-accent/[0.06] px-4 py-3 text-sm">
            <span class="text-ink">Niveau 1 — ventes directes</span><span class="font-display font-semibold text-accent">15 %</span>
        </div>
        <div class="flex items-center justify-between rounded-xl border border-black/10 bg-surface px-4 py-3 text-sm">
            <span class="text-ink">Niveau 2 — ventes de vos filleuls</span><span class="font-display font-semibold text-emerald-300">10 %</span>
        </div>
        <div class="flex items-center justify-between rounded-xl border border-black/10 bg-surface px-4 py-3 text-sm">
            <span class="text-ink">Niveau 3 — niveau suivant</span><span class="font-display font-semibold text-amber">5 %</span>
        </div>
    </div>

    <p class="mt-3 text-xs text-ink-3">Les produits physiques et les services ne génèrent aucune commission (§4).</p>

    <form method="POST" action="{{ route('affiliate.activate') }}" class="mt-6">
        @csrf
        <x-ui.button type="submit" variant="primary" :full="true">Activer mon espace affilié</x-ui.button>
    </form>

    <p class="mt-3 text-center text-xs text-ink-3">
        En activant, vous acceptez les
        <a href="{{ route('affiliate.program-terms') }}" class="text-accent hover:underline">conditions du programme</a>.
    </p>
</div>
@endsection
