@extends('layouts.auth')

@section('title', 'Connexion')
@section('seo_description', 'Connectez-vous à votre espace MALKUTHAR.')

@section('content')
<h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink">Connexion</h1>
<p class="mt-1.5 text-sm text-ink-2">Accédez à votre espace client et affilié.</p>

@if ($errors->any())
    <div class="mt-5">
        <x-ui.alert tone="error">
            {{ $errors->first() }}
        </x-ui.alert>
    </div>
@endif

<form method="POST" action="{{ route('login') }}" class="mt-6 grid gap-4">
    @csrf

    <x-ui.field name="email" label="Email" type="email" :required="true" autocomplete="email" autofocus />
    <x-ui.field name="password" label="Mot de passe" type="password" :required="true" autocomplete="current-password" />

    <label class="flex items-center gap-2.5 text-sm text-ink-2">
        <input type="checkbox" name="remember"
               class="h-4 w-4 rounded border-black/20 bg-black/[0.04] text-accent focus:ring-accent/30">
        Se souvenir de moi
    </label>

    <x-ui.button variant="primary" type="submit" :full="true">Se connecter</x-ui.button>
</form>

<p class="mt-6 text-center text-sm text-ink-2">
    Pas encore de compte ?
    <a href="{{ route('register') }}" class="font-semibold text-ink underline decoration-accent underline-offset-4 transition-colors hover:text-accent">Créer un compte</a>
</p>
@endsection
