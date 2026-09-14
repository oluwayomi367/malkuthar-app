@extends('layouts.app')

@section('title', 'Ma lignée')
@section('sidebar')<x-layout.sidebar-app space="affiliate" />@endsection

@section('content')
@php
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $levelAccent = [1 => 'text-accent', 2 => 'text-emerald-300', 3 => 'text-amber'];
    $levelRing = [1 => 'border-accent/30', 2 => 'border-emerald-300/25', 3 => 'border-amber/25'];
@endphp

<p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Espace affilié</p>
<h1 class="mt-1 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Ma lignée</h1>
<p class="mt-1 text-sm text-ink-2">Vos trois niveaux de filleuls : effectifs, ventes réalisées et chiffre d'affaires généré.</p>

<div class="mt-8 space-y-3">
    @foreach ($levels as $level)
        <div class="rounded-2xl border bg-surface p-5 {{ $levelRing[$level['level']] ?? 'border-black/10' }}">
            <div class="flex items-center justify-between">
                <p class="font-display text-base font-semibold text-ink">Niveau {{ $level['level'] }}</p>
                <span class="rounded-full border border-black/10 bg-black/[0.04] px-2.5 py-0.5 text-xs font-semibold {{ $levelAccent[$level['level']] ?? 'text-ink-2' }}">{{ $level['rate'] }} %</span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                <div><p class="text-ink-3">Filleuls</p><p class="mt-0.5 font-display text-lg font-semibold text-ink">{{ $level['members'] }}</p></div>
                <div><p class="text-ink-3">Actifs</p><p class="mt-0.5 font-display text-lg font-semibold text-ink">{{ $level['active'] }}</p></div>
                <div><p class="text-ink-3">Ventes réalisées</p><p class="mt-0.5 font-display text-lg font-semibold text-ink">{{ $level['sales'] }}</p></div>
                <div><p class="text-ink-3">CA généré</p><p class="mt-0.5 font-display text-lg font-semibold text-ink">{{ $money($level['revenue']) }}</p></div>
            </div>
        </div>
    @endforeach
</div>

<p class="mt-4 text-xs text-ink-3">Les données personnelles des membres de votre réseau ne sont pas affichées, uniquement des agrégats (§46).</p>
@endsection
