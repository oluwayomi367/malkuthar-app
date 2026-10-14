@extends('layouts.auth')

@section('title', 'Connexion')
@section('seo_description', 'Connectez-vous à votre espace MALKUTHAR.')

@section('auth_tagline', 'Bon retour parmi nous.')
@section('auth_subtitle', 'Un seul compte pour vos achats, vos téléchargements, vos réservations et, le cas échéant, vos espaces affilié et vendeur.')

@section('content')
<h1 class="font-display text-[32px] font-semibold leading-tight text-ink">Connexion</h1>
<p class="mt-2 text-[15px] text-ink-2">Accédez à votre espace client, affilié ou vendeur.</p>

@if ($errors->any())
    <div class="mt-6">
        <x-ui.alert tone="error">
            {{ $errors->first() }}
        </x-ui.alert>
    </div>
@endif

<form method="POST" action="{{ route('login') }}" class="mt-8 grid gap-5">
    @csrf

    <x-ui.field name="email" label="Adresse email" type="email" :required="true" autocomplete="email" autofocus />
    <x-ui.field name="password" label="Mot de passe" type="password" :required="true" autocomplete="current-password" />

    <label class="inline-flex min-h-11 cursor-pointer items-center gap-3 self-start text-sm text-ink-2">
        <input type="checkbox" name="remember"
               class="h-5 w-5 rounded border-line-strong text-accent focus:ring-accent/30">
        Se souvenir de moi
    </label>

    <x-ui.button variant="primary" type="submit" :full="true">Se connecter</x-ui.button>
</form>

<div class="mt-8 border-t border-line pt-6 text-sm text-ink-2">
    <p>
        Pas encore de compte ?
        <a href="{{ route('register') }}" class="font-semibold text-ink underline decoration-accent underline-offset-4 transition-colors hover:text-accent-2">Créer un compte</a>
    </p>
    <p class="mt-2">
        Vous souhaitez
        <a href="{{ route('register.affiliate') }}" class="font-medium text-ink underline decoration-line-strong underline-offset-4 hover:decoration-accent">devenir affilié</a>
        ou
        <a href="{{ route('register.vendor') }}" class="font-medium text-ink underline decoration-line-strong underline-offset-4 hover:decoration-accent">vendre sur la place de marché</a> ?
    </p>
</div>
@endsection
