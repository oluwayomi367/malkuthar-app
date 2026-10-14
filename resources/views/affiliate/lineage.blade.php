@extends('layouts.app')

@section('title', 'Ma lignée')
@section('sidebar')<x-layout.sidebar-app space="affiliate" />@endsection

@section('content')
@php
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $levelLabel = [1 => 'Vos ventes directes', 2 => 'Ventes de vos filleuls', 3 => 'Niveau suivant'];
@endphp

<header>
    <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Mon réseau</h1>
    <p class="mt-2 max-w-2xl text-[15px] leading-relaxed text-ink-2">Vos trois niveaux de filleuls : effectifs, ventes réalisées et chiffre d'affaires généré.</p>
</header>

<div class="mt-8 space-y-4">
    @foreach ($levels as $level)
        <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="level-{{ $level['level'] }}">
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <div>
                    <h2 id="level-{{ $level['level'] }}" class="font-sans text-lg font-semibold text-ink">Niveau {{ $level['level'] }}</h2>
                    <p class="text-[13px] text-ink-3">{{ $levelLabel[$level['level']] ?? '' }}</p>
                </div>
                <p class="font-display text-[32px] font-semibold leading-none text-accent-2">{{ $level['rate'] }}&nbsp;%</p>
            </div>
            <dl class="mt-5 grid grid-cols-2 gap-x-4 gap-y-4 border-t border-line pt-5 sm:grid-cols-4">
                <div><dt class="text-sm text-ink-2">Filleuls</dt><dd class="mt-1 font-display text-[28px] font-semibold leading-none text-ink">{{ $level['members'] }}</dd></div>
                <div><dt class="text-sm text-ink-2">Actifs</dt><dd class="mt-1 font-display text-[28px] font-semibold leading-none text-ink">{{ $level['active'] }}</dd></div>
                <div><dt class="text-sm text-ink-2">Ventes réalisées</dt><dd class="mt-1 font-display text-[28px] font-semibold leading-none text-ink">{{ $level['sales'] }}</dd></div>
                <div><dt class="text-sm text-ink-2">CA généré</dt><dd class="mt-1 font-display text-[28px] font-semibold leading-none text-ink">{{ $money($level['revenue']) }}</dd></div>
            </dl>
        </section>
    @endforeach
</div>

<p class="mt-4 text-[13px] text-ink-3">Les données personnelles des membres de votre réseau ne sont pas affichées, uniquement des totaux.</p>
@endsection
