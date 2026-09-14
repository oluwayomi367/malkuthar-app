@extends('layouts.admin')

@section('title', 'Administration — Réglages Marketplace')
@section('eyebrow', 'Marketplace')
@section('heading', 'Réglages de la place de marché')

@section('content')
<div class="mb-6 rounded-2xl border p-5 @class(['border-brand-green/25 bg-brand-green/[0.06]' => $enabled, 'border-black/10 bg-surface' => ! $enabled])">
    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">État actuel</p>
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

<form method="POST" action="{{ route('admin.marketplace.settings.update') }}" class="max-w-2xl space-y-6">
    @csrf @method('PUT')

    <div class="rounded-2xl border border-black/10 bg-surface p-5">
        <label class="flex items-start gap-3">
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

    <x-ui.button type="submit" variant="primary">Enregistrer</x-ui.button>
</form>

<div class="mt-10 max-w-2xl">
    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Vendeurs avec un taux négocié (palier Growth / Scale)</p>
    @if ($vendorsWithOverride->isEmpty())
        <p class="mt-2 text-sm text-ink-2">Aucun vendeur n'a de taux personnalisé pour l'instant — tous sont au palier Starter.</p>
    @else
        <div class="mt-3 overflow-x-auto rounded-2xl border border-black/10">
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
</div>
@endsection
