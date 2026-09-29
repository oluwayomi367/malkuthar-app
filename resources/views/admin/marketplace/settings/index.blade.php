@extends('layouts.admin')

@section('title', 'Administration — Réglages Vitrine & Marketplace')
@section('eyebrow', 'Administration')
@section('heading', 'Réglages de la vitrine & Place de marché')

@section('content')
<div class="max-w-3xl space-y-10">

    {{-- SECTION 1 : BANDEAU D'ANNONCE DÉFILANT --}}
    <section class="rounded-2xl border border-black/10 bg-surface p-6 shadow-xs">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-accent">Vitrine publique</p>
                <h2 class="mt-1 font-display text-lg font-bold text-ink">Bandeau d'annonce défilant (Sous le menu)</h2>
                <p class="mt-1 text-xs text-ink-2">
                    Ce bandeau s'affiche sous le menu de navigation de toutes les pages de la vitrine. Le texte défile en boucle continue vers la gauche.
                </p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold @class([
                'bg-brand-green/10 text-brand-green border border-brand-green/20' => $announcementTickerEnabled,
                'bg-ink-3/10 text-ink-3 border border-ink-3/20' => ! $announcementTickerEnabled,
            ])">
                <span class="h-1.5 w-1.5 rounded-full {{ $announcementTickerEnabled ? 'bg-brand-green' : 'bg-ink-3' }}"></span>
                {{ $announcementTickerEnabled ? 'Actif' : 'Désactivé' }}
            </span>
        </div>

        <form method="POST" action="{{ route('admin.marketplace.settings.announcement.update') }}" class="mt-6 space-y-5">
            @csrf @method('PUT')

            <div class="rounded-xl border border-line bg-ground/60 p-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="announcement_ticker_enabled" value="1" @checked($announcementTickerEnabled)
                           class="mt-0.5 h-4 w-4 rounded border-line-strong text-accent focus:ring-accent/30">
                    <span>
                        <span class="block text-sm font-semibold text-ink">Activer l'affichage du bandeau sur la vitrine</span>
                        <span class="block text-xs text-ink-2">
                            Si décoché, le bandeau sera entièrement masqué pour tous les visiteurs.
                        </span>
                    </span>
                </label>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="announcement_ticker" class="text-xs font-semibold text-ink-2">
                        Texte de l'annonce défilante
                    </label>
                    <span class="text-[11px] text-ink-3">Séparateur recommandé : · ou |</span>
                </div>
                <textarea
                    id="announcement_ticker"
                    name="announcement_ticker"
                    rows="3"
                    class="w-full rounded-xl border border-line/90 bg-surface p-3 text-sm text-ink placeholder:text-ink-3/60 focus:border-accent focus:outline-none focus:ring-4 focus:ring-accent/15"
                    placeholder="✨ Vos créations d'exception · ⚡ Téléchargement immédiat · 📦 Expédition soignée à domicile..."
                >{{ old('announcement_ticker', $announcementTicker) }}</textarea>
                <p class="mt-1.5 text-[11px] text-ink-3">
                    Astuce : vous pouvez insérer des émojis (✨, ⚡, 📦, 💳, 🤝) et utiliser le symbole « · » ou « | » pour espacer plusieurs messages.
                </p>
            </div>

            {{-- Aperçu en temps réel --}}
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-ink-3 mb-2">Aperçu en direct sur la vitrine</p>
                <div class="overflow-hidden rounded-xl border border-black/20 bg-black text-white py-2.5 px-4 text-xs">
                    <div class="flex w-max animate-marquee space-x-8">
                        <span class="inline-flex items-center gap-3">
                            <span>{{ $announcementTicker ?: 'Aucun texte configuré' }}</span>
                            <span class="text-accent text-[10px]">✦</span>
                            <span>{{ $announcementTicker ?: 'Aucun texte configuré' }}</span>
                            <span class="text-accent text-[10px]">✦</span>
                        </span>
                        <span class="inline-flex items-center gap-3" aria-hidden="true">
                            <span>{{ $announcementTicker ?: 'Aucun texte configuré' }}</span>
                            <span class="text-accent text-[10px]">✦</span>
                            <span>{{ $announcementTicker ?: 'Aucun texte configuré' }}</span>
                            <span class="text-accent text-[10px]">✦</span>
                        </span>
                    </div>
                </div>
            </div>

            <div>
                <x-ui.button type="submit" variant="primary">Enregistrer le bandeau</x-ui.button>
            </div>
        </form>
    </section>

    {{-- SECTION 2 : RÉGLAGES PLACE DE MARCHÉ --}}
    <section class="space-y-6">
        <div class="rounded-2xl border p-5 @class(['border-brand-green/25 bg-brand-green/[0.06]' => $enabled, 'border-black/10 bg-surface' => ! $enabled])">
            <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">État actuel — Marketplace</p>
            <p class="mt-1 font-display text-xl font-bold text-ink">
                @if ($enabled)
                    En ligne — visible du public
                @else
                    Masquée — routes publiques et espace vendeur désactivés
                @endif
            </p>
            <p class="mt-1 text-sm text-ink-2">
                Ce drapeau contrôle `/place-de-marche`, `/vendeur/*`, la page « Tarifs vendeur » et les liens
                de navigation. Le désactiver n'efface rien : vendeurs, offres et commandes restent en base.
            </p>
        </div>

        <form method="POST" action="{{ route('admin.marketplace.settings.update') }}" class="space-y-6">
            @csrf @method('PUT')

            <div class="rounded-2xl border border-black/10 bg-surface p-5">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="enabled" value="1" @checked($enabled)
                           class="mt-0.5 h-4 w-4 rounded border-line-strong text-accent focus:ring-accent/30">
                    <span>
                        <span class="block text-[15px] font-semibold text-ink">Ouvrir la place de marché au public</span>
                        <span class="block text-sm text-ink-2">
                            Cochez pour lancer : la boutique, la candidature vendeur et la page Tarifs deviennent accessibles.
                        </span>
                    </span>
                </label>
            </div>

            <div class="rounded-2xl border border-black/10 bg-surface p-5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Paliers de commission (page « Tarifs »)</p>
                <p class="mt-1 text-xs text-ink-3">
                    Le palier Growth n'est jamais appliqué automatiquement : depuis la fiche d'un vendeur
                    (« Vendeurs » → taux de commission), vous activez le taux réduit une fois le seuil atteint.
                    Ces valeurs ne servent qu'à l'affichage de la promesse commerciale et au calcul du taux
                    plateforme par défaut.
                </p>

                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <x-ui.field name="starter_rate_percent" label="Starter — taux (%)" type="number" step="0.01" min="0" max="90"
                                :value="old('starter_rate_percent', rtrim(rtrim(number_format($starterRate * 100, 2), '0'), '.'))" :required="true" />
                    <x-ui.field name="growth_rate_percent" label="Growth — taux (%)" type="number" step="0.01" min="0" max="90"
                                :value="old('growth_rate_percent', rtrim(rtrim(number_format($growthRate * 100, 2), '0'), '.'))" :required="true" />
                    <x-ui.field name="growth_threshold_amount" label="Growth — seuil de CA cumulé" type="number" step="1" min="0"
                                :value="old('growth_threshold_amount', (int) $growthThreshold)" :required="true" />
                </div>
            </div>

            <x-ui.button type="submit" variant="primary">Enregistrer les réglages marketplace</x-ui.button>
        </form>
    </section>

    {{-- SECTION 3 : VENDEURS AVEC OVERRIDE --}}
    <section>
        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Vendeurs avec un taux négocié (palier Growth / Scale)</p>
        @if ($vendorsWithOverride->isEmpty())
            <p class="mt-2 text-sm text-ink-2">Aucun vendeur n'a de taux personnalisé pour l'instant — tous sont au palier Starter.</p>
        @else
            <div class="mt-3 overflow-x-auto rounded-2xl border border-black/10 bg-surface">
                <table class="w-full min-w-[480px] text-left text-sm">
                    <thead class="bg-surface text-[10.5px] uppercase tracking-[0.09em] text-ink-3">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Boutique</th>
                            <th class="px-4 py-3 font-semibold">Taux négocié</th>
                            <th class="px-4 py-3 font-semibold">Fiche</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/[0.06]">
                        @foreach ($vendorsWithOverride as $v)
                            <tr>
                                <td class="px-4 py-3 font-medium text-ink">{{ $v->display_name }}</td>
                                <td class="px-4 py-3 font-mono text-ink-2">{{ rtrim(rtrim(number_format($v->commission_rate_override * 100, 2), '0'), '.') }} %</td>
                                <td class="px-4 py-3"><a href="{{ route('admin.marketplace.vendors.show', $v) }}" class="text-accent hover:underline">Voir →</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

</div>
@endsection
