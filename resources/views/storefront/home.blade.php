@extends('layouts.storefront')

@section('title', 'MALKUTHAR — Produits digitaux, physiques et services')
@section('seo_description', "MALKUTHAR réunit trois métiers : ebooks et formations en livraison immédiate, produits physiques expédiés, consultations sur rendez-vous. Programme d'affiliation à 3 niveaux.")

@section('content')
@php $cats = \App\Support\Catalog::categories(); @endphp

{{-- ============ HERO ============ --}}
<section class="relative overflow-hidden">
    <div class="relative mx-auto max-w-[1180px] px-4 pb-20 pt-20 text-center sm:px-6 sm:pt-28">
        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-accent">MALKUTHAR SARL</p>
        <h1 class="mx-auto mt-5 max-w-3xl font-display text-[clamp(2.1rem,6vw,4rem)] font-semibold leading-[1.12] tracking-[-0.018em] text-ink">
            Transmettez vos savoirs, vos œuvres,
            <span class="quote font-normal text-accent">votre présence.</span>
        </h1>
        <p class="mx-auto mt-6 max-w-xl text-lg text-ink-2">
            Une plateforme, trois voies : vos écrits et formations livrés à l'instant,
            vos objets expédiés, vos accompagnements réservés. Bientôt, une place de marché
            ouverte aux praticiens de la région — et un programme d'affiliation à trois niveaux.
        </p>

        <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <x-ui.button variant="primary" :href="route('catalog.index')">Explorer la boutique</x-ui.button>
            <x-ui.button variant="ghost" :href="route('register')">Devenir affilié →</x-ui.button>
        </div>

        <p class="mt-7 text-[13px] text-ink-3">
            Livraison numérique immédiate · Paiement FedaPay &amp; CinetPay
        </p>
    </div>
</section>

{{-- ============ TROIS VOIES — accordéon ============ --}}
<section class="mx-auto max-w-[1180px] px-4 pb-16 sm:px-6">
    <x-ui.section-heading eyebrow="Trois voies, une maison" title="Par où voulez-vous entrer ?" />

    <div class="mt-6" x-data="{ tab: 'digital' }">
        {{-- Boutons en haut (défilement horizontal, centré) --}}
        <div class="-mx-4 overflow-x-auto px-4 [scrollbar-width:none] sm:mx-0 sm:px-0 [&::-webkit-scrollbar]:hidden">
            <div class="flex min-w-max items-center justify-center gap-3 py-1 sm:min-w-0">
                @foreach ($cats as $c)
                    @php $t = $c['type']; @endphp
                    <button type="button"
                            x-on:click="tab = '{{ $t }}'"
                            :aria-pressed="tab === '{{ $t }}'"
                            :class="tab === '{{ $t }}'
                                ? 'border-line-strong bg-surface text-ink shadow-sm'
                                : 'border-transparent bg-ground-alt text-ink-2 hover:border-line hover:bg-surface'"
                            class="inline-flex flex-shrink-0 items-center gap-2.5 rounded-xl border px-4 py-2.5 text-[15px] font-medium transition-all">
                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-md text-white" style="background-color: {{ $c['color'] }}">
                            <svg class="h-3 w-3" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">{!! $c['icon'] !!}</svg>
                        </span>
                        {{ $c['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Détail en bas --}}
        <div class="mt-5 rounded-2xl border border-line bg-surface p-5 sm:p-6">
            @foreach ($cats as $c)
                @php $t = $c['type']; @endphp
                <div x-show="tab === '{{ $t }}'" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="grid gap-6 sm:grid-cols-2 sm:items-center">
                        <div class="order-2 sm:order-1">
                            <div class="flex items-center gap-2.5">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-md text-white" style="background-color: {{ $c['color'] }}">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">{!! $c['icon'] !!}</svg>
                                </span>
                                <h3 class="font-display text-xl font-semibold text-ink">{{ $c['label'] }}</h3>
                            </div>
                            <p class="mt-3 text-ink-2">{{ $c['text'] }}</p>
                            <a href="{{ route('catalog.index', ['type' => $t]) }}"
                               class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-accent hover:underline">
                                Voir les {{ \Illuminate\Support\Str::lower($c['label']) }} <span aria-hidden="true">→</span>
                            </a>
                        </div>
                        <div class="relative order-1 aspect-[4/3] overflow-hidden rounded-xl border border-line bg-ground-alt sm:order-2">
                            <div class="absolute inset-0 flex items-center justify-center text-line-strong">
                                <svg class="h-16 w-16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">{!! $c['icon'] !!}</svg>
                            </div>
                            <img src="{{ asset($c['image']) }}" alt="Aperçu — {{ $c['label'] }}" loading="lazy"
                                 onerror="this.remove()"
                                 class="relative h-full w-full object-cover">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ TÉMOIGNAGES — slider (3 par slide) ============ --}}
@php
    // Témoignages de démonstration — à remplacer par de vrais retours clients.
    $testimonials = [
        ['quote' => "J'ai reçu la formation quelques minutes après le paiement. Tout était clair, du panier au téléchargement.", 'name' => 'Aïcha D.', 'role' => 'Cliente · Cotonou'],
        ['quote' => "J'ai partagé mon lien dans mon cercle et touché mes premiers retraits en Mobile Money le mois suivant.", 'name' => 'Komla A.', 'role' => 'Affilié · Lomé'],
        ['quote' => "J'ai réservé une séance en visio : le créneau proposé était réel, la confirmation immédiate.", 'name' => 'Sarah M.', 'role' => 'Cliente · Abidjan'],
        ['quote' => "Le coffret est arrivé suivi jusqu'à ma porte. Enfin une maison qui réunit les livres, les cours et les séances.", 'name' => 'Ibrahim T.', 'role' => 'Client · Ouagadougou'],
        ['quote' => "Interface claire, paiement local sans friction. J'ai recommandé la plateforme à tout mon groupe de lecture.", 'name' => 'Ngozi E.', 'role' => 'Cliente · Douala'],
        ['quote' => "En tant qu'auteur, je publie mes ebooks et je suis payé dès la vente. La gestion est transparente.", 'name' => 'Yao K.', 'role' => 'Vendeur · Kara'],
    ];
@endphp
<section class="mx-auto max-w-[1180px] px-4 pb-16 sm:px-6">
    <x-ui.section-heading eyebrow="Ils nous font confiance" title="Ce qu'en disent nos membres" />

    <div class="mt-6"
         x-data="{
             items: {{ \Illuminate\Support\Js::from($testimonials) }},
             page: 0,
             perPage: 3,
             timer: null,
             get reduced() { return window.matchMedia('(prefers-reduced-motion: reduce)').matches },
             get pages() { return Math.max(1, Math.ceil(this.items.length / this.perPage)) },
             sync() {
                 const w = window.innerWidth;
                 this.perPage = w < 640 ? 1 : (w < 1024 ? 2 : 3);
                 if (this.page > this.pages - 1) this.page = this.pages - 1;
             },
             slice(p) { return this.items.slice(p * this.perPage, p * this.perPage + this.perPage) },
             next() { this.page = (this.page + 1) % this.pages },
             prev() { this.page = (this.page - 1 + this.pages) % this.pages },
             start() { if (!this.reduced) this.timer = setInterval(() => this.next(), 7000) },
             stop() { clearInterval(this.timer) },
         }"
         x-init="sync(); start(); window.addEventListener('resize', () => sync())"
         x-on:mouseenter="stop()" x-on:mouseleave="start()"
         x-on:focusin="stop()" x-on:focusout="start()">

        <div class="overflow-hidden">
            <div class="flex transition-transform duration-500 ease-out" :style="`transform: translateX(-${page * 100}%)`">
                <template x-for="p in pages" :key="'p'+p">
                    <div class="w-full shrink-0">
                        <div class="grid gap-4 pb-1 sm:grid-cols-2 lg:grid-cols-3">
                            <template x-for="(t, idx) in slice(p - 1)" :key="'t'+p+'-'+idx">
                                <figure class="flex h-full flex-col rounded-2xl border border-line bg-surface p-5 sm:p-6">
                                    <svg class="h-6 w-6 text-accent/40" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M10 7H6a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2v3H5v2h5v-9a2 2 0 0 0-2-2H6V9h4V7zm10 0h-4a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2v3h-3v2h5v-9a2 2 0 0 0-2-2h-2V9h4V7z"/>
                                    </svg>
                                    <blockquote class="quote mt-3 flex-1 text-[17px] leading-relaxed text-ink" x-text="'« ' + t.quote + ' »'"></blockquote>
                                    <figcaption class="mt-5 text-sm">
                                        <span class="font-semibold text-ink" x-text="t.name"></span>
                                        <span class="text-ink-3"> — </span>
                                        <span class="text-ink-2" x-text="t.role"></span>
                                    </figcaption>
                                </figure>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="mt-5 flex items-center justify-between">
            <div class="flex gap-1.5">
                <template x-for="p in pages" :key="'d'+p">
                    <button type="button" x-on:click="page = p - 1"
                            :aria-label="'Page ' + p"
                            :class="page === p - 1 ? 'w-5 bg-accent' : 'w-1.5 bg-line-strong hover:bg-ink-3'"
                            class="h-1.5 rounded-full transition-all"></button>
                </template>
            </div>
            <div class="flex gap-1">
                <button type="button" x-on:click="prev()" aria-label="Précédent"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-line text-ink-2 transition-colors hover:border-line-strong hover:text-ink">
                    <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M10 3L5 8l5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button type="button" x-on:click="next()" aria-label="Suivant"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-line text-ink-2 transition-colors hover:border-line-strong hover:text-ink">
                    <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 3l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        </div>
    </div>
</section>

{{-- ============ AFFILIATION — comment ça marche ============ --}}
@php
    $affiliationSteps = [
        ['Activez votre espace', "En un clic depuis votre compte. Aucun second compte à créer."],
        ['Partagez votre lien', "Votre lien de parrainage personnel, sur les réseaux ou en message."],
        ['La vente est attribuée', "Un achat via votre lien sur un produit digital éligible vous est rattaché."],
        ['La commission tombe', "Créditée dès le paiement, retirable après la période de validation."],
    ];
@endphp
<section class="mx-auto max-w-[1180px] px-4 pb-16 sm:px-6">
    <div class="relative overflow-hidden rounded-3xl border border-black/10 bg-surface p-6 sm:p-10">
        <div class="relative max-w-2xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Programme d'affiliation</p>
            <h2 class="mt-3 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Gagnez en partageant</h2>
            <p class="mt-3 text-ink-2">
                Réservé aux produits digitaux éligibles.
                <span class="font-semibold text-ink">15&nbsp;% · 10&nbsp;% · 5&nbsp;%</span>
                répartis sur trois niveaux de parrainage.
            </p>
        </div>

        <ol class="relative mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($affiliationSteps as $i => [$titre, $texte])
                <li class="flex gap-3">
                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-accent/30 bg-accent-soft/60 font-display text-[13px] font-semibold text-accent">
                        {{ $i + 1 }}
                    </span>
                    <div>
                        <p class="font-display text-[14px] font-semibold text-ink">{{ $titre }}</p>
                        <p class="mt-1 text-[13px] leading-snug text-ink-2">{{ $texte }}</p>
                    </div>
                </li>
            @endforeach
        </ol>

        <div class="mt-8 flex flex-wrap gap-3">
            <x-ui.button variant="primary" :href="route('register')">Rejoindre le programme</x-ui.button>
            <x-ui.button variant="ghost" :href="route('affiliation')">Comment ça marche →</x-ui.button>
        </div>
    </div>
</section>

{{-- ============ MARKETPLACE — bientôt ============ --}}
<section class="mx-auto max-w-[1180px] px-4 pb-16 sm:px-6">
    <div class="rounded-3xl border border-line bg-ground-alt p-6 sm:p-10">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Place de marché</p>
            <span class="rounded-full border border-accent/30 bg-accent-soft/60 px-2 py-0.5 text-[10.5px] font-semibold uppercase tracking-[0.1em] text-accent">Bientôt</span>
        </div>
        <h2 class="mt-3 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">
            Une place de marché ouverte aux praticiens
        </h2>
        <p class="mt-3 max-w-xl text-ink-2">
            Praticiens, créateurs, formateurs et artisans de la région pourront bientôt vendre leurs
            offres sur MALKUTHAR, sous leur propre nom : produits physiques, produits digitaux,
            services et formations, avec validation de chaque fiche par notre équipe.
        </p>

        <div class="mt-6 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (['Produits physiques', 'Produits digitaux', 'Services', 'Formations'] as $t)
                <div class="rounded-xl border border-line bg-surface px-3 py-2.5 text-[13px] font-medium text-ink">{{ $t }}</div>
            @endforeach
        </div>

        <div class="mt-7">
            <x-ui.button variant="secondary" :href="route('marketplace')">Vendre sur MALKUTHAR →</x-ui.button>
        </div>
    </div>
</section>

{{-- ============ RÉASSURANCE ============ --}}
<section class="mx-auto max-w-[1180px] px-4 pb-16 sm:px-6">
    <div class="rounded-2xl border border-black/10 bg-surface p-6 sm:p-8">
        <x-commerce.reassurance />
    </div>
</section>

{{-- ============ CTA FINALE ============ --}}
<section class="mx-auto max-w-[1180px] px-4 pb-24 text-center sm:px-6">
    <h2 class="font-display text-xl font-semibold text-ink">Prêt à ouvrir la boutique ?</h2>
    <div class="mt-5">
        <x-ui.button variant="primary" :href="route('catalog.index')">Voir tous les produits</x-ui.button>
    </div>
</section>
@endsection
