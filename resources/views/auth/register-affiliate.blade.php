@extends('layouts.auth')

@section('title', 'Devenir Affilié — Inscription')
@section('seo_description', 'Inscrivez-vous au programme d\'affiliation MALKUTHAR et touchez des commissions sur 3 niveaux : 15 %, 10 % et 5 % sur chaque vente.')
@section('form_max', 'max-w-[560px]')

@section('auth_heading', 'Devenir Partenaire Affilié')
@section('auth_intro', 'Vous obtenez un lien de parrainage personnel et suivez vos gains depuis votre espace affilié.')
@section('auth_tagline', 'Recommandez, percevez des commissions.')
@section('auth_subtitle', 'Sur les produits digitaux affiliables, chaque vente est rémunérée sur trois niveaux de parrainage.')
@section('auth_image', 'auth.register_affiliate')

@section('content')
{{-- Rappel des taux --}}
<div class="rounded-2xl bg-ground-alt p-5">
    <h2 class="font-sans text-base font-semibold tracking-normal text-ink">Commissions sur 3 niveaux</h2>
    <dl class="mt-3 grid grid-cols-3 gap-3 text-center">
        <div class="flex flex-col-reverse rounded-xl bg-accent-soft px-2 py-3">
            <dt class="mt-1.5 text-xs text-ink-2">Niveau 1 (direct)</dt>
            <dd class="font-display text-[26px] font-semibold leading-none text-accent-2">15 %</dd>
        </div>
        <div class="flex flex-col-reverse rounded-xl bg-ground-alt px-2 py-3">
            <dt class="mt-1.5 text-xs text-ink-2">Niveau 2</dt>
            <dd class="font-display text-[26px] font-semibold leading-none text-ink">10 %</dd>
        </div>
        <div class="flex flex-col-reverse rounded-xl bg-ground-alt px-2 py-3">
            <dt class="mt-1.5 text-xs text-ink-2">Niveau 3</dt>
            <dd class="font-display text-[26px] font-semibold leading-none text-ink">5 %</dd>
        </div>
    </dl>
    <p class="mt-4 text-sm leading-relaxed text-ink-2">
        <span class="font-semibold text-ink">Sans parrain ?</span> Le Système MALKUTHAR devient votre parrain d'office et vous êtes intégré au réseau immédiatement.
    </p>
</div>

@if ($errors->any())
    <div class="mt-6">
        <x-ui.alert tone="error">Veuillez vérifier les informations renseignées ci-dessous.</x-ui.alert>
    </div>
@endif

<form method="POST" action="{{ route('register.affiliate.store') }}" class="mt-8 grid gap-5">
    @csrf

    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.field name="first_name" label="Prénom" :required="true" autocomplete="given-name" autofocus :value="old('first_name')" />
        <x-ui.field name="last_name" label="Nom" :required="true" autocomplete="family-name" :value="old('last_name')" />
    </div>

    <x-ui.field name="email" label="Adresse email" type="email" :required="true" autocomplete="email" :value="old('email')" />

    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.field name="phone" label="Téléphone (WhatsApp / Retraits)" :required="true" autocomplete="tel" :value="old('phone')"
                    hint="Pour les versements Mobile Money" />
        <x-ui.field name="country" label="Pays de résidence" :required="true" autocomplete="country-name" :value="old('country', 'Bénin')" />
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.field name="password" label="Mot de passe" type="password" :required="true" autocomplete="new-password" />
        <x-ui.field name="password_confirmation" label="Confirmer le mot de passe" type="password" :required="true" autocomplete="new-password" />
    </div>

    <x-auth.referral-field />

    <div class="mt-1">
        <x-ui.button variant="primary" type="submit" :full="true">Valider mon inscription et obtenir mon lien</x-ui.button>
    </div>

    <p class="text-xs leading-relaxed text-ink-3">
        Avant de vous inscrire, vous pouvez consulter les
        <a href="{{ route('affiliate.program-terms') }}" class="font-medium text-ink-2 underline underline-offset-2 hover:text-ink">conditions du programme d'affiliation</a>.
    </p>
</form>

@endsection

@section('auth_footer')
    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-line bg-surface/80 px-4 text-xs font-semibold text-ink shadow-2xs transition-colors hover:border-line-strong hover:bg-surface">
        <span class="text-accent" aria-hidden="true">✦</span> Déjà inscrit ? Se connecter
    </a>
@endsection
