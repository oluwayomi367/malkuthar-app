@extends('layouts.storefront')

@section('title', 'Charte — aperçu')
@section('seo_description', 'Aperçu interne de la charte MALKUTHAR.')

@section('content')
<div class="mx-auto max-w-[900px] px-4 py-12 sm:px-6">

    <p class="text-[10.5px] font-semibold uppercase tracking-[0.14em] text-accent">Interne · direction Premium</p>
    <h1 class="mt-2 font-display text-3xl font-bold tracking-[-0.025em] text-ink sm:text-4xl">Tech moderne / premium</h1>
    <p class="mt-3 max-w-xl text-ink-2">Fond sombre profond, surfaces en verre, dégradé d'accent violet → bleu réservé à la conversion. Cette page valide les tokens et la bibliothèque <code class="font-mono text-[13px]">&lt;x-ui.*&gt;</code>.</p>

    {{-- COULEURS --}}
    <section class="mt-14">
        <h2 class="font-display text-lg font-semibold text-ink">Palette</h2>
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([
                ['Ground', 'bg-ground border border-white/10', '#08080C'],
                ['Ground alt', 'bg-ground-alt border border-white/10', '#0F0F17'],
                ['Surface', 'bg-surface border border-white/10', '#14141D'],
                ['Line', 'bg-line', '#23232F'],
                ['Ink', 'bg-deep', '#F5F5F8'],
                ['Ink 2', 'bg-ink-2', '#A6A6B4'],
                ['Ink 3', 'bg-ink-3', '#6C6C7C'],
                ['Accent', 'bg-accent', '#7C5CFF'],
                ['Accent 2', 'bg-accent-2', '#4C7DFF'],
                ['Dégradé', 'bg-accent', 'accent → accent-2'],
                ['Gold', 'bg-gold', '#C9A25E'],
                ['Rust', 'bg-rust', '#FB7185'],
            ] as [$name, $cls, $hex])
                <div class="overflow-hidden rounded-xl border border-white/10">
                    <div class="h-16 {{ $cls }}"></div>
                    <div class="px-3 py-2">
                        <p class="text-[13px] font-semibold text-ink">{{ $name }}</p>
                        <p class="font-mono text-[11px] text-ink-3">{{ $hex }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- TYPO --}}
    <section class="mt-14">
        <h2 class="font-display text-lg font-semibold text-ink">Typographie — Sora (titres) · Inter (corps)</h2>
        <div class="mt-4 space-y-3 border-t border-line pt-4">
            <p class="font-display text-3xl font-bold tracking-[-0.025em] sm:text-4xl">Vendez vos savoirs, vos objets, vos heures.</p>
            <p class="font-display text-xl font-semibold tracking-[-0.01em]">Titre de section — Sora 600</p>
            <p class="max-w-xl text-ink-2">Corps de texte — Inter 400, interligne 1.65. Largeur de lecture limitée. Chiffres tabulaires : 1&nbsp;234&nbsp;567 FCFA.</p>
            <p class="text-[13px] font-medium text-ink-3">Légende / méta — 13&nbsp;px</p>
            <p class="text-[10.5px] font-semibold uppercase tracking-[0.12em] text-accent">Micro-label accent — 11&nbsp;px</p>
            <p class="font-mono text-[13px] text-ink-2">JetBrains Mono — COM-20260808-000001</p>
        </div>
    </section>

    {{-- BOUTONS --}}
    <section class="mt-14">
        <h2 class="font-display text-lg font-semibold text-ink">Boutons</h2>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <x-ui.button variant="primary">Payer 22&nbsp;500 FCFA</x-ui.button>
            <x-ui.button variant="secondary">Ajouter au panier</x-ui.button>
            <x-ui.button variant="ghost">Continuer mes achats</x-ui.button>
            <x-ui.button variant="primary" disabled>Indisponible</x-ui.button>
        </div>
        <div class="mt-3 max-w-xs">
            <x-ui.button variant="primary" :full="true">Bouton pleine largeur</x-ui.button>
        </div>
    </section>

    {{-- CHAMPS --}}
    <section class="mt-14">
        <h2 class="font-display text-lg font-semibold text-ink">Champs de formulaire</h2>
        <div class="mt-4 grid max-w-md gap-4">
            <x-ui.field name="email_demo" label="Email" type="email" placeholder="vous@exemple.com" />
            <x-ui.field name="phone_demo" label="Numéro Mobile Money" hint="Format international, ex. +229…" />
            <x-ui.select name="op_demo" label="Opérateur" :options="['mtn' => 'MTN', 'moov' => 'Moov', 'celtiis' => 'Celtiis']" placeholder="Choisir…" />
            <x-ui.textarea name="msg_demo" label="Message" :rows="3" />
        </div>
    </section>

    {{-- BADGES + PILLS --}}
    <section class="mt-14">
        <h2 class="font-display text-lg font-semibold text-ink">Badges &amp; statuts</h2>
        <div class="mt-4 flex flex-wrap gap-2">
            <x-ui.badge tone="ink">Digital</x-ui.badge>
            <x-ui.badge tone="ink">Physique</x-ui.badge>
            <x-ui.badge tone="ink">Service</x-ui.badge>
            <x-ui.badge tone="accent">Nouveau</x-ui.badge>
            <x-ui.badge tone="gold">✦ Affiliable</x-ui.badge>
            <x-ui.badge tone="rust">Plus que 3</x-ui.badge>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach (['pending','available','processing','paid','cancelled','reversed','failed'] as $s)
                <x-ui.status-pill :status="$s" />
            @endforeach
        </div>
    </section>

    {{-- CARTES + TUILES --}}
    <section class="mt-14">
        <h2 class="font-display text-lg font-semibold text-ink">Cartes &amp; tuiles chiffrées (§44)</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <x-ui.stat-tile label="Solde disponible" value="125 500" currency="FCFA" :emphasis="true">
                <x-slot:action>
                    <x-ui.button variant="primary" :full="true">Retirer mes gains</x-ui.button>
                </x-slot:action>
            </x-ui.stat-tile>
            <div class="grid grid-cols-2 gap-3">
                <x-ui.stat-tile label="En attente" value="35 000" currency="F" />
                <x-ui.stat-tile label="Total gagné" value="450 000" currency="F" />
                <x-ui.stat-tile label="Total retiré" value="324 500" currency="F" />
                <x-ui.stat-tile label="Cette semaine" value="+ 12 000" currency="F" />
            </div>
        </div>
        <div class="mt-3">
            <x-ui.card>
                <p class="font-display font-semibold text-ink">Carte en verre</p>
                <p class="mt-1 text-sm text-ink-2">Fond translucide, flou d'arrière-plan, bordure d'un cheveu, ombre douce projetée.</p>
            </x-ui.card>
        </div>
    </section>

    {{-- ALERTES + EMPTY --}}
    <section class="mt-14">
        <h2 class="font-display text-lg font-semibold text-ink">Alertes &amp; états vides</h2>
        <div class="mt-4 space-y-3">
            <x-ui.alert tone="success">Votre demande de retrait a été enregistrée.</x-ui.alert>
            <x-ui.alert tone="error">Le créneau demandé n'est plus disponible.</x-ui.alert>
            <x-ui.alert tone="info">Un code de vérification vous a été envoyé par e-mail.</x-ui.alert>
        </div>
        <div class="mt-4">
            <x-ui.empty-state title="Aucune vente pour le moment" text="Partagez votre lien d'affiliation pour générer vos premières commissions.">
                <x-slot:cta>
                    <x-ui.button variant="secondary">Copier mon lien</x-ui.button>
                </x-slot:cta>
            </x-ui.empty-state>
        </div>
    </section>

    {{-- TABS + COPY --}}
    <section class="mt-14">
        <h2 class="font-display text-lg font-semibold text-ink">Onglets &amp; « Copier »</h2>
        <div class="mt-4">
            <x-ui.tabs :items="[url('/boutique') => 'Tout', url('/boutique?type=digital') => 'Digitaux', url('/boutique?type=physical') => 'Physiques', url('/boutique?type=service') => 'Services']" :active="url('/boutique')" />
        </div>
        <div class="mt-4 flex items-center gap-3 rounded-2xl border border-white/10 bg-surface/[0.03] p-3">
            <span class="min-w-0 flex-1 truncate font-mono text-sm text-ink">{{ url('/ref/JEAN125') }}</span>
            <x-ui.copy-button :value="url('/ref/JEAN125')" label="Copier" />
        </div>
    </section>

    <section class="mt-14 border-t border-line pt-6">
        <p class="text-sm text-ink-3">Si la direction te va : je re-compose l'accueil, la boutique et les fiches dans cette langue.</p>
    </section>

</div>
@endsection
