@extends('layouts.admin')

@section('title', 'Fiche — ' . $product->name)
@section('eyebrow', 'Catalogue')
@section('heading', $product->name)

@section('actions')
    <x-ui.button :href="route('admin.products.index')" variant="secondary">← Retour</x-ui.button>
    <x-ui.button :href="route('admin.products.edit', $product)" variant="primary">Modifier</x-ui.button>
    <form method="POST" action="{{ route('admin.products.delete', $product) }}"
          onsubmit="return confirm('Supprimer définitivement « {{ $product->name }} » ?')">
        @csrf @method('DELETE')
        <button class="inline-flex h-11 items-center rounded-xl border border-rust/40 px-4 text-sm font-semibold text-rust transition-colors hover:bg-rust/10 sm:h-10">Supprimer</button>
    </form>
@endsection

@section('content')
@php
    $m = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $typeLabels = ['digital' => 'Digital', 'physical' => 'Physique', 'service' => 'Service'];
    $statusMeta = [
        'published' => ['Publié', 'accent'],
        'draft' => ['Brouillon', 'muted'],
        'archived' => ['Archivé', 'rust'],
    ];
    [$sLabel, $sTone] = $statusMeta[$product->status] ?? [$product->status, 'muted'];
    $images = collect([$product->main_image])->merge((array) ($product->gallery ?? []))->filter()->unique()->values();
    $moveLabels = [
        'reserved' => 'Réservé', 'released' => 'Libéré', 'sold' => 'Vendu',
        'returned' => 'Retour', 'restocked' => 'Réappro.', 'adjustment' => 'Ajustement',
    ];
    $moveSign = ['reserved' => '−', 'sold' => '', 'released' => '+', 'returned' => '+', 'restocked' => '+', 'adjustment' => '±'];
@endphp

<div class="mb-6 flex flex-wrap items-center gap-2">
    <x-ui.badge tone="muted">{{ $typeLabels[$product->type] ?? $product->type }}</x-ui.badge>
    <x-ui.badge :tone="$sTone">{{ $sLabel }}</x-ui.badge>
    @if ($product->is_affiliable)<x-ui.badge tone="accent">Affiliable · commissions 15/10/5</x-ui.badge>@endif
    @unless ($product->is_available)<x-ui.badge tone="rust">Indisponible à la vente</x-ui.badge>@endunless
    @if ($product->status === 'published')
        <a href="{{ route('catalog.show', $product->slug) }}" target="_blank" rel="noopener" class="ml-auto text-sm text-accent hover:underline">Voir en boutique ↗</a>
    @endif
</div>

<div class="grid gap-8 lg:grid-cols-[300px_1fr] lg:gap-10">
    {{-- Visuels --}}
    <div>
        <div class="overflow-hidden rounded-2xl border border-black/10 bg-ground/60">
            @if ($images->isNotEmpty())
                <img src="{{ $images->first() }}" alt="{{ $product->name }}" class="aspect-square w-full object-cover">
            @else
                <div class="flex aspect-square w-full items-center justify-center text-ink-3">
                    <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 15l5-5 4 4 3-3 6 6"/><circle cx="9" cy="9" r="1.5"/></svg>
                </div>
            @endif
        </div>
        @if ($images->count() > 1)
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($images->slice(1) as $img)
                    <img src="{{ $img }}" alt="" class="h-16 w-16 rounded-lg border border-black/10 object-cover">
                @endforeach
            </div>
        @endif
        @if ($product->presentation_video)
            <a href="{{ $product->presentation_video }}" target="_blank" rel="noopener" class="mt-3 inline-flex text-sm text-accent hover:underline">▶ Vidéo de présentation</a>
        @endif
    </div>

    {{-- Détails --}}
    <div class="space-y-6">
        @if ($product->description)
            <p class="whitespace-pre-line text-sm leading-relaxed text-ink-2">{{ $product->description }}</p>
        @endif

        <dl class="grid grid-cols-2 gap-x-6 gap-y-4 rounded-2xl border border-black/10 bg-surface p-5 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-[11px] uppercase tracking-wide text-ink-3">Prix</dt>
                <dd class="mt-0.5 font-display text-lg font-semibold text-ink">{{ $m($product->price) }} {{ $product->currency }}</dd>
            </div>
            @if ($product->promotional_price)
                <div>
                    <dt class="text-[11px] uppercase tracking-wide text-ink-3">Prix promo</dt>
                    <dd class="mt-0.5 font-display text-lg font-semibold text-accent">{{ $m($product->promotional_price) }} {{ $product->currency }}</dd>
                </div>
            @endif
            <div>
                <dt class="text-[11px] uppercase tracking-wide text-ink-3">Slug</dt>
                <dd class="mt-0.5 font-mono text-ink-2">{{ $product->slug }}</dd>
            </div>
            @if ($product->category)
                <div><dt class="text-[11px] uppercase tracking-wide text-ink-3">Catégorie</dt><dd class="mt-0.5 text-ink">{{ $product->category }}@if ($product->sub_category) / {{ $product->sub_category }}@endif</dd></div>
            @endif

            @if ($product->type === 'digital')
                <div class="col-span-2 sm:col-span-3"><dt class="text-[11px] uppercase tracking-wide text-ink-3">Fichier livré</dt><dd class="mt-0.5 break-all font-mono text-ink-2">{{ $product->file_path ?: '—' }}</dd></div>
            @elseif ($product->type === 'physical')
                <div><dt class="text-[11px] uppercase tracking-wide text-ink-3">SKU</dt><dd class="mt-0.5 font-mono text-ink">{{ $product->sku ?: '—' }}</dd></div>
                <div><dt class="text-[11px] uppercase tracking-wide text-ink-3">Poids</dt><dd class="mt-0.5 text-ink">{{ $product->weight ? $product->weight.' kg' : '—' }}</dd></div>
            @elseif ($product->type === 'service')
                <div><dt class="text-[11px] uppercase tracking-wide text-ink-3">Durée</dt><dd class="mt-0.5 text-ink">{{ $product->duration_minutes ? $product->duration_minutes.' min' : '—' }}</dd></div>
                <div><dt class="text-[11px] uppercase tracking-wide text-ink-3">Mode</dt><dd class="mt-0.5 text-ink">{{ $product->delivery_mode ?: '—' }}</dd></div>
                <div><dt class="text-[11px] uppercase tracking-wide text-ink-3">Lieu</dt><dd class="mt-0.5 text-ink">{{ $product->location ?: '—' }}</dd></div>
            @endif

            <div><dt class="text-[11px] uppercase tracking-wide text-ink-3">Créé le</dt><dd class="mt-0.5 text-ink-2">{{ $product->created_at?->format('d/m/Y') }}</dd></div>
            <div><dt class="text-[11px] uppercase tracking-wide text-ink-3">Modifié le</dt><dd class="mt-0.5 text-ink-2">{{ $product->updated_at?->format('d/m/Y') }}</dd></div>
        </dl>

        {{-- Ventes & commissions --}}
        <div class="rounded-2xl border border-black/10 bg-surface p-5">
            <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Ventes &amp; commissions</p>

            <div class="mt-3 grid grid-cols-2 gap-3 text-center sm:grid-cols-4">
                <div class="rounded-xl border border-black/10 py-3">
                    <p class="font-display text-2xl font-bold text-ink">{{ $sales['units_sold'] }}</p>
                    <p class="text-[11px] uppercase tracking-wide text-ink-3">Unités vendues</p>
                </div>
                <div class="rounded-xl border border-black/10 py-3">
                    <p class="font-display text-2xl font-bold text-ink">{{ $sales['orders_count'] }}</p>
                    <p class="text-[11px] uppercase tracking-wide text-ink-3">Commandes</p>
                </div>
                <div class="rounded-xl border border-black/10 py-3">
                    <p class="font-display text-2xl font-bold text-ink">{{ $m($sales['revenue']) }}</p>
                    <p class="text-[11px] uppercase tracking-wide text-ink-3">CA ({{ $sales['currency'] }})</p>
                </div>
                <div class="rounded-xl border border-black/10 py-3">
                    <p class="font-display text-2xl font-bold {{ $sales['pending_revenue'] > 0 ? 'text-amber' : 'text-ink-3' }}">{{ $m($sales['pending_revenue']) }}</p>
                    <p class="text-[11px] uppercase tracking-wide text-ink-3">En attente COD</p>
                </div>
            </div>

            @if (! $sales['applicable'])
                <p class="mt-4 rounded-xl border border-black/10 bg-ground/50 p-3 text-sm text-ink-3">
                    Produit non affiliable : aucune commission d'affiliation n'est calculée sur ses ventes (§4/§5).
                </p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[380px] text-left text-sm">
                        <thead class="text-[10.5px] uppercase tracking-[0.08em] text-ink-3">
                            <tr>
                                <th class="py-2 pr-3 font-semibold">Commissions générées</th>
                                <th class="px-2 py-2 text-right font-semibold">Montant ({{ $sales['currency'] }})</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/[0.05]">
                            @foreach ($sales['commission_by_level'] as $level => $amount)
                                <tr>
                                    <td class="py-2 pr-3 text-ink-2">Niveau {{ $level }}</td>
                                    <td class="px-2 py-2 text-right text-ink">{{ $m($amount) }}</td>
                                </tr>
                            @endforeach
                            @if ($sales['commission_reversed'] > 0)
                                <tr>
                                    <td class="py-2 pr-3 text-rust">Reprises (remboursements)</td>
                                    <td class="px-2 py-2 text-right text-rust">−{{ $m($sales['commission_reversed']) }}</td>
                                </tr>
                            @endif
                            <tr class="font-semibold">
                                <td class="py-2 pr-3 text-ink">Net distribué</td>
                                <td class="px-2 py-2 text-right text-ink">{{ $m($sales['commission_net']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                @if (! empty($sales['commission_by_status']))
                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-ink-2">
                        @foreach ($sales['commission_by_status'] as $st => $amount)
                            <span class="inline-flex items-center gap-1.5">
                                <x-ui.status-pill :status="$st" />
                                <span>{{ $m($amount) }} {{ $sales['currency'] }}</span>
                            </span>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        {{-- Stock (physique) --}}
        @if ($product->type === 'physical')
            <div class="rounded-2xl border border-black/10 bg-surface p-5">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Stock</p>
                    <a href="{{ route('admin.products.edit', $product) }}#stock" class="text-xs text-accent hover:underline">Ajuster</a>
                </div>
                @php
                    $hasVariants = $product->relationLoaded('variants') && $product->variants->isNotEmpty();
                    $sumAvail = $hasVariants ? $product->variants->sum('stock_available') : (int) $product->stock_available;
                    $sumRes = $hasVariants ? $product->variants->sum('stock_reserved') : (int) $product->stock_reserved;
                    $sumSold = $hasVariants ? $product->variants->sum('stock_sold') : (int) $product->stock_sold;
                @endphp
                <div class="mt-3 grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-xl border border-black/10 bg-surface py-3">
                        <p class="font-display text-2xl font-bold {{ $sumAvail <= 5 ? 'text-rust' : 'text-ink' }}">{{ $sumAvail }}</p>
                        <p class="text-[11px] uppercase tracking-wide text-ink-3">Disponible</p>
                    </div>
                    <div class="rounded-xl border border-black/10 bg-surface py-3">
                        <p class="font-display text-2xl font-bold text-ink-2">{{ $sumRes }}</p>
                        <p class="text-[11px] uppercase tracking-wide text-ink-3">Réservé</p>
                    </div>
                    <div class="rounded-xl border border-black/10 bg-surface py-3">
                        <p class="font-display text-2xl font-bold text-ink-2">{{ $sumSold }}</p>
                        <p class="text-[11px] uppercase tracking-wide text-ink-3">Vendu</p>
                    </div>
                </div>

                @if ($hasVariants)
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[440px] text-left text-sm">
                            <thead class="text-[10.5px] uppercase tracking-[0.08em] text-ink-3">
                                <tr><th class="py-2 pr-3 font-semibold">Variante</th><th class="px-2 py-2 font-semibold">SKU</th><th class="px-2 py-2 text-center font-semibold">Dispo</th><th class="px-2 py-2 text-center font-semibold">Rés.</th><th class="pl-2 py-2 text-center font-semibold">Vendu</th><th class="pl-2 py-2 text-right font-semibold">Prix</th></tr>
                            </thead>
                            <tbody class="divide-y divide-black/[0.05]">
                                @foreach ($product->variants as $v)
                                    <tr @class(['text-ink-3' => ! $v->is_active])>
                                        <td class="py-2 pr-3 text-ink">{{ $v->name }}@unless ($v->is_active) <span class="text-xs text-ink-3">(inactive)</span>@endunless</td>
                                        <td class="px-2 py-2 font-mono text-xs text-ink-3">{{ $v->sku ?: '—' }}</td>
                                        <td class="px-2 py-2 text-center {{ (int) $v->stock_available <= 3 ? 'text-rust' : '' }}">{{ (int) $v->stock_available }}</td>
                                        <td class="px-2 py-2 text-center text-ink-2">{{ (int) $v->stock_reserved }}</td>
                                        <td class="pl-2 py-2 text-center text-ink-2">{{ (int) $v->stock_sold }}</td>
                                        <td class="pl-2 py-2 text-right text-ink-2">{{ $v->price !== null ? number_format((float) $v->price, 0, ',', ' ').' '.$product->currency : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($movements->isNotEmpty())
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ($movements as $mv)
                            <li class="flex items-baseline justify-between gap-3 border-b border-black/[0.05] pb-2 last:border-0 last:pb-0">
                                <span>
                                    <span class="text-ink">{{ $moveLabels[$mv->type] ?? $mv->type }}</span>
                                    <span class="text-ink-3"> {{ $moveSign[$mv->type] ?? '' }}{{ $mv->quantity }}</span>
                                    @if ($mv->reason)<span class="text-ink-3"> · {{ $mv->reason }}</span>@endif
                                    @if ($mv->admin)<span class="text-ink-3"> · {{ $mv->admin->first_name }} {{ $mv->admin->last_name }}</span>@endif
                                </span>
                                <span class="shrink-0 text-xs text-ink-3">{{ $mv->created_at->format('d/m/y H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
