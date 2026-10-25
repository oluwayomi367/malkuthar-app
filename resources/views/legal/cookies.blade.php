@extends('legal.layout')

@php
    $refDays = config('malkuthar.affiliation.referral_cookie_days');
    $sessionMinutes = config('session.lifetime');
    $t = config('malkuthar.tracking');
    // Outils de mesure et de publicité réellement activés sur ce site.
    $trackers = collect([
        ['Google Tag Manager', 'Chargement des outils de mesure', $t['gtm_container_id'] ?? null],
        ['Google Analytics', "Mesure d'audience", $t['ga_measurement_id'] ?? null],
        ['Meta (Facebook) Pixel', 'Mesure des campagnes publicitaires Meta', $t['meta_pixel_id'] ?? null],
        ['TikTok Pixel', 'Mesure des campagnes publicitaires TikTok', $t['tiktok_pixel_id'] ?? null],
    ])->filter(fn ($row) => filled($row[2]));
    $th = 'border-b border-line-strong px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-ink';
    $td = 'border-b border-line px-3 py-2.5 align-top text-sm';
@endphp

@section('title', 'Gestion des cookies')
@section('seo_description', 'Les cookies et stockages utilisés par le site MALKUTHAR, leur durée et comment les gérer.')
@section('legal_heading', 'Gestion des cookies')
@section('legal_intro', 'Un cookie est un petit fichier déposé sur votre appareil lors de votre visite. Voici ceux que le site utilise et comment les gérer.')

@section('legal_body')
    <section>
        <h2>1. Cookies nécessaires au fonctionnement</h2>
        <p>Ils sont indispensables au site et ne peuvent pas être désactivés sans empêcher la connexion ou la commande.</p>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[520px] border-collapse">
                <thead><tr><th class="{{ $th }}">Nom</th><th class="{{ $th }}">Rôle</th><th class="{{ $th }}">Durée</th></tr></thead>
                <tbody>
                    <tr><td class="{{ $td }} font-mono">{{ config('session.cookie') }}</td><td class="{{ $td }}">Garde votre session ouverte (connexion, formulaire en cours)</td><td class="{{ $td }}">{{ $sessionMinutes }} minutes d'inactivité</td></tr>
                    <tr><td class="{{ $td }} font-mono">XSRF-TOKEN</td><td class="{{ $td }}">Protège les formulaires contre les envois frauduleux</td><td class="{{ $td }}">Durée de la session</td></tr>
                    <tr><td class="{{ $td }} font-mono">mal_locale</td><td class="{{ $td }}">Mémorise la langue choisie</td><td class="{{ $td }}">1 an</td></tr>
                    <tr><td class="{{ $td }} font-mono">mal_consent</td><td class="{{ $td }}">Mémorise vos choix en matière de cookies</td><td class="{{ $td }}">6 mois</td></tr>
                    <tr><td class="{{ $td }} font-mono">mal_ref</td><td class="{{ $td }}">Retient l'affilié qui vous a recommandé le site, pour lui attribuer sa commission</td><td class="{{ $td }}">{{ $refDays }} jours</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h2>2. Stockage dans votre navigateur</h2>
        <p>
            Votre panier et vos favoris sont enregistrés directement dans votre navigateur
            (stockage local <span class="font-mono text-sm">mal_cart</span> et <span class="font-mono text-sm">mal_wishlist</span>).
            Ils ne nous sont pas transmis tant que vous ne passez pas commande, et restent jusqu'à ce que
            vous vidiez votre panier ou les données de votre navigateur.
        </p>
    </section>

    <section>
        <h2>3. Mesure d'audience et publicité</h2>
        @if ($trackers->isEmpty())
            <p>Aucun outil de mesure d'audience ni de publicité n'est actuellement activé sur le site.</p>
        @else
            <p>Les outils suivants sont activés. Ils déposent leurs propres cookies et peuvent transmettre des données de navigation à leur éditeur.</p>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[520px] border-collapse">
                    <thead><tr><th class="{{ $th }}">Outil</th><th class="{{ $th }}">Rôle</th></tr></thead>
                    <tbody>
                        @foreach ($trackers as [$name, $role])
                            <tr><td class="{{ $td }} font-semibold text-ink">{{ $name }}</td><td class="{{ $td }}">{{ $role }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section>
        <h2>4. Comment gérer les cookies</h2>
        <p>
            Les cookies de mesure d'audience et de publicité ne sont déposés qu'avec votre accord,
            donné dans la fenêtre qui s'affiche lors de votre première visite. Vous pouvez modifier
            ce choix à tout moment :
        </p>
        <p>
            <button type="button" data-cookie-settings
                    class="inline-flex min-h-11 items-center rounded-full bg-deep px-5 text-sm font-semibold text-white hover:bg-deep-2">
                Gérer mes choix de cookies
            </button>
        </p>
        <p>
            Vous pouvez aussi supprimer ou bloquer les cookies depuis les réglages de votre
            navigateur (rubrique « Confidentialité » ou « Cookies »). Bloquer les cookies nécessaires
            vous empêchera de vous connecter et de commander.
        </p>
    </section>

    <section>
        <h2>5. En savoir plus</h2>
        <p>
            Pour le traitement de vos données personnelles en général, consultez la
            <a href="{{ route('legal.privacy') }}">politique de confidentialité</a>.
        </p>
    </section>
@endsection
