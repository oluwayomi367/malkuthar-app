@extends('layouts.app')

@section('title', 'Mon profil')
@section('sidebar')<x-layout.sidebar-app space="account" />@endsection

@section('content')
<header>
    <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Mon profil</h1>
    <p class="mt-2 text-[15px] text-ink-2">Vos coordonnées personnelles.</p>
</header>

<div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start">
    <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-5 rounded-2xl border border-line bg-surface p-5 sm:p-6">
        @csrf
        @method('PUT')

        <h2 class="font-sans text-lg font-semibold text-ink">Coordonnées</h2>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-ui.field name="first_name" label="Prénom" :value="$user->first_name" required autocomplete="given-name" />
            <x-ui.field name="last_name" label="Nom" :value="$user->last_name" required autocomplete="family-name" />
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="profile-email" class="text-sm font-semibold text-ink">Adresse e-mail</label>
            <input id="profile-email" value="{{ $user->email }}" disabled aria-describedby="profile-email-hint" class="min-h-11 cursor-not-allowed rounded-xl border border-line bg-ground-alt px-3.5 text-[15px] text-ink-2">
            <p id="profile-email-hint" class="text-[13px] text-ink-3">Contactez le support pour modifier votre e-mail.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-ui.field name="phone" label="Téléphone" type="tel" :value="$user->phone" autocomplete="tel" />
            <x-ui.field name="country" label="Pays" :value="$user->country" autocomplete="country-name" />
        </div>

        <div class="border-t border-line pt-5">
            <x-ui.button type="submit" variant="primary">Enregistrer</x-ui.button>
        </div>
    </form>

    <aside class="rounded-2xl border border-line bg-ground-alt p-5 text-sm sm:p-6">
        <h2 class="font-sans text-base font-semibold text-ink">Programme d'affiliation</h2>
        @if ($user->isAffiliate())
            <p class="mt-2 leading-relaxed text-ink-2">Vous êtes affilié depuis le {{ $user->affiliate_activated_at?->translatedFormat('d F Y') }}.</p>
            <a href="{{ route('affiliate.dashboard') }}" class="mt-3 inline-flex min-h-11 items-center font-semibold text-accent-2 underline decoration-accent/40 underline-offset-4 hover:decoration-accent">Ouvrir l'espace affilié</a>
        @else
            <p class="mt-2 leading-relaxed text-ink-2">Gagnez 15 %, 10 % ou 5 % sur les produits digitaux éligibles que vous recommandez.</p>
            <a href="{{ route('affiliate.join') }}" class="mt-3 inline-flex min-h-11 items-center font-semibold text-accent-2 underline decoration-accent/40 underline-offset-4 hover:decoration-accent">Devenir affilié</a>
        @endif
    </aside>
</div>
@endsection
