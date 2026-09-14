@extends('layouts.app')

@section('title', 'Mon profil')
@section('sidebar')<x-layout.sidebar-app space="account" />@endsection

@section('content')
<h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Mon profil</h1>
<p class="mt-1 text-sm text-ink-2">Vos coordonnées personnelles.</p>

<form method="POST" action="{{ route('account.profile.update') }}" class="mt-8 max-w-xl space-y-5 rounded-2xl border border-black/10 bg-surface p-6">
    @csrf
    @method('PUT')

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field name="first_name" label="Prénom" :value="$user->first_name" required />
        <x-ui.field name="last_name" label="Nom" :value="$user->last_name" required />
    </div>

    <div class="flex flex-col gap-1.5">
        <label class="text-[13px] font-medium text-ink-2">Adresse e-mail</label>
        <input value="{{ $user->email }}" disabled class="h-11 cursor-not-allowed rounded-xl border border-black/10 bg-surface px-3.5 text-[15px] text-ink-3">
        <p class="text-xs text-ink-3">Contactez le support pour modifier votre e-mail.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field name="phone" label="Téléphone" :value="$user->phone" />
        <x-ui.field name="country" label="Pays" :value="$user->country" />
    </div>

    <div class="pt-1">
        <x-ui.button type="submit" variant="primary">Enregistrer</x-ui.button>
    </div>
</form>

<div class="mt-6 max-w-xl rounded-2xl border border-black/10 bg-surface p-6 text-sm">
    <h2 class="font-display font-semibold text-ink">Programme d'affiliation</h2>
    @if ($user->isAffiliate())
        <p class="mt-1 text-ink-2">Vous êtes affilié depuis le {{ $user->affiliate_activated_at?->translatedFormat('d F Y') }}.</p>
        <a href="{{ route('affiliate.dashboard') }}" class="mt-3 inline-flex text-accent hover:underline">Ouvrir mon tableau de bord affilié →</a>
    @else
        <p class="mt-1 text-ink-2">Gagnez 15 · 10 · 5 % sur les produits digitaux éligibles que vous recommandez.</p>
        <a href="{{ route('affiliate.join') }}" class="mt-3 inline-flex text-accent hover:underline">Devenir affilié →</a>
    @endif
</div>
@endsection
