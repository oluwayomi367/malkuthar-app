@extends('layouts.storefront')

@section('title', 'Vendre sur MALKUTHAR — la place de marché')
@section('seo_description', "La place de marché MALKUTHAR arrive : praticiens, créateurs, formateurs et artisans de la région pourront y vendre produits physiques, produits digitaux, services et formations. Rejoignez la liste d'attente.")

@section('content')
@php
    $pourQui = [
        ['Praticiens', 'Thérapeutes, coachs, consultants qui proposent des séances et des accompagnements sur rendez-vous.'],
        ['Créateurs & auteurs', 'Ebooks, audios, ressources numériques livrés automatiquement après paiement.'],
        ['Formateurs', 'Parcours pédagogiques structurés, en autonomie ou en cohorte, avec suivi de progression.'],
        ['Artisans & marchands', 'Objets, coffrets et ouvrages de la région, avec gestion des stocks et de la livraison.'],
    ];
    $typesOffres = [
        ['Produits physiques', 'Stocks, variantes, expédition suivie.'],
        ['Produits digitaux', 'Livraison immédiate par lien sécurisé.'],
        ['Services', 'Réservation d\'un créneau réel, en visio ou en présentiel.'],
        ['Formations', 'Modules, leçons, accès limité dans le temps, certificat.'],
    ];
    $etapes = [
        ['Créez votre compte vendeur', "Depuis votre compte MALKUTHAR, vous demandez l'ouverture d'un espace vendeur et déposez vos justificatifs."],
        ['Publiez une offre', "Vous choisissez une catégorie, remplissez la fiche (les champs s'adaptent au type d'offre) et soumettez."],
        ["L'équipe valide", "Chaque offre est relue par un administrateur avant sa mise en ligne. Vous recevez un retour clair si un ajustement est nécessaire."],
        ['Vendez et soyez payé', "Votre offre est en ligne. À chaque vente, votre part est créditée ; vous demandez vos versements depuis votre tableau de bord."],
    ];
@endphp

{{-- ================= HERO ================= --}}
<section class="mx-auto max-w-[1180px] px-4 pb-12 pt-14 sm:px-6 sm:pt-20">
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Place de marché · Bientôt</p>
    <h1 class="mt-3 max-w-2xl text-[30px] font-bold leading-[1.12] tracking-[-0.012em] text-ink sm:text-5xl">
        Vendez vos offres sur MALKUTHAR.
    </h1>
    <p class="mt-4 max-w-xl text-ink-2">
        Nous ouvrons prochainement une place de marché aux praticiens, créateurs, formateurs et
        artisans de la région. Un compte vendeur, un catalogue à votre nom, et le même soin de
        transparence que pour le reste de la plateforme. Inscrivez-vous pour être prévenu·e à
        l'ouverture.
    </p>
    <div class="mt-6 flex flex-wrap gap-3">
        @if (\App\Support\MarketplaceSettings::enabled())
            <x-ui.button variant="primary" :href="route('marketplace.shop')">Parcourir les offres</x-ui.button>
            <x-ui.button variant="ghost" href="#candidater">Devenir vendeur →</x-ui.button>
        @else
            <x-ui.button variant="primary" href="#candidater">Rejoindre la liste d'attente</x-ui.button>
            <x-ui.button variant="ghost" href="#fonctionnement">Comment ça marchera →</x-ui.button>
        @endif
    </div>
</section>

{{-- ================= POUR QUI ================= --}}
<section class="border-y border-line bg-ground-alt">
    <div class="mx-auto grid max-w-[1180px] gap-px bg-line sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($pourQui as [$titre, $texte])
            <div class="bg-ground-alt px-4 py-8 sm:px-6">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-3">◇ {{ $titre }}</p>
                <p class="mt-3 text-[15px] text-ink-2">{{ $texte }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ================= CE QUE VOUS POURREZ VENDRE ================= --}}
<section class="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
    <x-ui.section-heading eyebrow="Quatre natures d'offres" title="Ce que vous pourrez vendre" />
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($typesOffres as [$titre, $texte])
            <div class="rounded-2xl border border-line bg-surface p-5 shadow-[0_1px_2px_rgba(46,44,40,0.04)]">
                <p class="font-display text-[15px] font-semibold text-ink">{{ $titre }}</p>
                <p class="mt-1 text-[13px] leading-snug text-ink-2">{{ $texte }}</p>
            </div>
        @endforeach
    </div>
    <p class="mt-5 text-sm text-ink-2">
        Chaque catégorie a ses propres champs (dimensions, durée, niveau, langue…) : la fiche
        s'adapte à ce que vous vendez, sans formulaire fourre-tout.
    </p>
</section>

{{-- ================= COMMENT ÇA MARCHERA ================= --}}
<section id="fonctionnement" class="border-t border-line bg-ground-alt scroll-mt-20">
    <div class="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
        <x-ui.section-heading eyebrow="Le parcours vendeur" title="Comment ça marchera" />
        <ol class="grid gap-6 sm:grid-cols-2">
            @foreach ($etapes as $i => [$titre, $texte])
                <li class="flex gap-4">
                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-accent/30 bg-accent-soft/60 font-display text-sm font-semibold text-accent">
                        {{ $i + 1 }}
                    </span>
                    <div>
                        <p class="font-display text-[15px] font-semibold text-ink">{{ $titre }}</p>
                        <p class="mt-1 text-sm leading-relaxed text-ink-2">{{ $texte }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ================= LE MODÈLE ================= --}}
<section class="mx-auto max-w-[1180px] px-4 py-16 sm:px-6">
    <x-ui.section-heading eyebrow="Le modèle économique" title="Une commission claire, des versements à vous" />
    <div class="grid gap-6 sm:grid-cols-3">
        <div>
            <p class="text-[15px] font-semibold text-ink">Commission de plateforme</p>
            <p class="mt-1 text-sm text-ink-2">Un pourcentage connu à l'avance, prélevé sur chaque vente. Il peut varier selon la catégorie. Le reste vous revient.</p>
        </div>
        <div>
            <p class="text-[15px] font-semibold text-ink">Versements pilotés par vous</p>
            <p class="mt-1 text-sm text-ink-2">Mobile Money ou virement. Vous déclenchez vos retraits depuis votre tableau de bord, une fois la période de validation passée.</p>
        </div>
        <div>
            <p class="text-[15px] font-semibold text-ink">Chaque mouvement tracé</p>
            <p class="mt-1 text-sm text-ink-2">Ventes, commissions, versements : tout figure dans un registre consultable, comme pour les affiliés.</p>
        </div>
    </div>
    <p class="mt-6 rounded-xl border border-line bg-surface px-4 py-3 text-[13px] text-ink-2">
        La place de marché est <span class="font-semibold text-ink">indépendante du programme d'affiliation</span> :
        les offres des vendeurs n'entrent pas dans les commissions à trois niveaux.
    </p>
</section>

{{-- ================= CALENDRIER ================= --}}
<section class="border-t border-line bg-ground-alt">
    <div class="mx-auto max-w-[1180px] px-4 py-14 sm:px-6">
        <x-ui.section-heading eyebrow="Où en sommes-nous" title="Ouverture progressive" />
        <p class="max-w-2xl text-sm text-ink-2">
            La place de marché démarrera avec un petit groupe de vendeurs de la région cible, chaque
            offre étant validée à la main. L'accès s'élargira ensuite par vagues. En rejoignant la
            liste d'attente, vous serez contacté·e dès que les inscriptions ouvriront.
        </p>
    </div>
</section>

{{-- ================= CANDIDATER / LISTE D'ATTENTE ================= --}}
<section id="candidater" class="mx-auto max-w-[1180px] px-4 py-16 sm:px-6 scroll-mt-20">
    <div class="grid gap-10 lg:grid-cols-[1.3fr_1fr] lg:gap-16">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Liste d'attente</p>
            <h2 class="mt-2 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Être prévenu·e à l'ouverture</h2>
            <p class="mt-3 max-w-lg text-sm text-ink-2">
                Dites-nous en quelques mots ce que vous souhaitez vendre et où vous êtes installé·e.
                Nous revenons vers vous à l'adresse indiquée dès que le programme vendeur ouvre.
            </p>

            <form method="POST" action="{{ route('contact.store') }}" class="mt-6 grid gap-4">
                @csrf
                <input type="hidden" name="subject" value="vendeur">

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field name="name" label="Votre nom" :required="true" autocomplete="name" />
                    <x-ui.field name="email" label="Votre email" type="email" :required="true" autocomplete="email" />
                </div>

                <x-ui.textarea
                    name="message"
                    label="Ce que vous vendez, votre région"
                    :rows="5"
                    :required="true"
                    placeholder="Ex. : séances de sophrologie à Cotonou / coffrets artisanaux / formation en ligne sur…"
                />

                {{-- honeypot : caché aux humains, doit rester vide --}}
                <div class="hidden" aria-hidden="true">
                    <label>Ne pas remplir <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <div>
                    <x-ui.button variant="primary" type="submit">M'inscrire à la liste d'attente</x-ui.button>
                </div>
            </form>
        </div>

        <aside>
            <x-ui.card>
                <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-ink-3">En attendant</p>
                <ul class="mt-4 space-y-3 text-sm text-ink-2">
                    <li>Vous avez déjà un compte MALKUTHAR ? Il deviendra aussi votre compte vendeur — aucun second compte.</li>
                    <li>Envie de recommander plutôt que de vendre ? Le <a href="{{ route('affiliation') }}" class="text-accent hover:underline">programme d'affiliation</a> est ouvert dès maintenant.</li>
                    <li>Une question précise ? Passez par la <a href="{{ route('contact') }}" class="text-accent hover:underline">page contact</a>.</li>
                    <li>À lire : les <a href="{{ route('marketplace.vendor-terms') }}" class="text-accent hover:underline">conditions du programme vendeur</a>.</li>
                </ul>
            </x-ui.card>
        </aside>
    </div>
</section>
@endsection
