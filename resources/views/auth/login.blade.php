@extends('layouts.auth')

@section('title', 'Connexion')
@section('seo_description', 'Connectez-vous à votre espace MALKUTHAR.')

@section('auth_heading', 'Bon retour parmi nous')
@section('auth_intro', 'Connectez-vous pour accéder à votre espace client, affilié ou vendeur.')
@section('auth_image', 'auth.login')
@section('auth_tagline', 'Un seul compte MALKUTHAR.')
@section('auth_subtitle', 'Vos achats, vos téléchargements, vos réservations et, le cas échéant, vos espaces affilié et vendeur.')

@section('content')
@if ($errors->any())
    <div class="mb-5">
        <x-ui.alert tone="error">
            {{ $errors->first() }}
        </x-ui.alert>
    </div>
@endif

<form method="POST" action="{{ route('login') }}" class="grid gap-4">
    @csrf

    <x-ui.field name="email" label="Adresse email" type="email" :required="true" autocomplete="email" placeholder="votre@email.com" autofocus />
    <x-ui.field name="password" label="Mot de passe" type="password" :required="true" autocomplete="current-password" />

    <label class="inline-flex min-h-11 cursor-pointer items-center gap-3 self-start text-sm text-ink-2">
        <input type="checkbox" name="remember"
               class="h-5 w-5 rounded border-line-strong text-accent focus:ring-accent/30">
        Se souvenir de moi
    </label>

    <x-ui.button variant="primary" type="submit" :full="true">Se connecter</x-ui.button>
</form>

<p class="mt-5 text-center text-[11px] leading-normal text-ink-3">
    Vous souhaitez
    <a href="{{ route('register.affiliate') }}" class="underline underline-offset-2 hover:text-ink">devenir affilié</a>
    ou
    <a href="{{ route('register.vendor') }}" class="underline underline-offset-2 hover:text-ink">vendre sur la place de marché</a> ?
</p>
@endsection

@section('auth_footer')
    <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-line bg-surface/80 px-4 text-xs font-semibold text-ink shadow-2xs transition-colors hover:border-line-strong hover:bg-surface">
        <span class="text-accent" aria-hidden="true">✦</span> Pas encore de compte ? Créer un compte
    </a>
@endsection
