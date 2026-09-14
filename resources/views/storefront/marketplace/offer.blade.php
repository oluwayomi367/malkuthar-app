@extends('layouts.storefront')

@section('title', $offer->title)
@section('seo_description', $offer->seo_description ?: \Illuminate\Support\Str::limit(strip_tags($offer->summary ?: $offer->description), 155))

@section('content')
@php
    use Illuminate\Support\Facades\Storage;

    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $hasSale = $offer->sale_price !== null && (float) $offer->sale_price < (float) $offer->price;
    $off = $hasSale ? (int) round(100 - $offer->sale_price / $offer->price * 100) : 0;
    $detail = $offer->detail();
    $typeLabel = ['physical' => 'Produit physique', 'digital' => 'Produit digital', 'service' => 'Service', 'training' => 'Formation'][$offer->type] ?? $offer->type;
    $images = collect([$offer->main_image_path])->merge($offer->gallery ?? [])->filter()->unique()->values();
    $rating = (int) round($offer->rating_avg);

    $hasVariants = $offer->hasVariants();
    $soldOutSimple = $offer->type === 'physical' && ! $hasVariants && $detail && ($detail->stock - $detail->stock_reserved) <= 0;

    $axes = [];
    foreach ($offer->variants ?? [] as $v) {
        foreach (($v->option_values ?? []) as $axis => $val) { $axes[$axis][$val] = true; }
    }
    $axes = collect($axes)->map(fn ($vals) => array_keys($vals))->all();
    $variantsJson = ($offer->variants ?? collect())->map(fn ($v) => [
        'id' => $v->id, 'options' => $v->option_values ?? [],
        'price' => $v->effectivePrice($offer), 'stock' => $v->availableStock(),
        'image' => $v->image_path ? Storage::url($v->image_path) : null,
    ])->values();

    $specs = $schema->map(function ($f) use ($offer) {
        $val = $offer->custom_fields[$f->key] ?? null;
        return ($val === null || $val === '') ? null : ['label' => $f->label, 'value' => is_array($val) ? implode(', ', $val) : $val];
    })->filter()->values();
@endphp

<div class="mx-auto max-w-[1180px] px-4 py-8 sm:px-6 sm:py-12">
    <nav class="text-xs text-ink-3">
        <a href="{{ route('marketplace.shop') }}" class="hover:text-ink">Place de marché</a>
        @if ($offer->category)
            <span> / </span><a href="{{ route('marketplace.shop', ['categorie' => $offer->category->slug]) }}" class="hover:text-ink">{{ $offer->category->name }}</a>
        @endif
    </nav>

    <p class="mt-4 text-[11px] font-semibold uppercase tracking-[0.14em] text-accent">{{ $typeLabel }}</p>
    <h1 class="mt-1 max-w-3xl font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-4xl">{{ $offer->title }}</h1>
    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-ink-2">
        <a href="{{ route('marketplace.shop.vendor', $offer->vendor->slug) }}" class="font-medium text-ink hover:text-accent">{{ $offer->vendor->display_name }}</a>
        @if ($offer->vendor->region)<span class="text-ink-3">{{ $offer->vendor->region }}</span>@endif
        @if ($offer->rating_count > 0)
            <span class="flex items-center gap-1"><span class="text-accent">{{ str_repeat('★', $rating) }}<span class="text-line-strong">{{ str_repeat('★', 5 - $rating) }}</span></span>
                {{ number_format($offer->rating_avg, 1, ',', ' ') }} ({{ $offer->rating_count }})</span>
        @endif
        @if ($offer->sales_count > 0)<span class="text-ink-3">{{ $offer->sales_count }} vendu·s</span>@endif
    </div>

    <div class="mt-6 grid gap-8 lg:grid-cols-[7fr_5fr] lg:gap-10">
        {{-- ===== Galerie ===== --}}
        <div x-data="{ active: {{ $images->isNotEmpty() ? "'".Storage::url($images->first())."'" : 'null' }} }">
            <div class="overflow-hidden rounded-2xl border border-line bg-ground-alt">
                <div class="aspect-square sm:aspect-[4/3]">
                    @if ($images->isNotEmpty())
                        <img :src="active" alt="{{ $offer->title }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full items-center justify-center text-line-strong">
                            <svg class="h-16 w-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7"/></svg>
                        </div>
                    @endif
                </div>
            </div>
            @if ($images->count() > 1)
                <div class="mt-2 flex gap-2 overflow-x-auto pb-1">
                    @foreach ($images as $img)
                        @php $url = Storage::url($img); @endphp
                        <button type="button" x-on:click="active = '{{ $url }}'"
                                :class="active === '{{ $url }}' ? 'border-accent' : 'border-line'"
                                class="h-16 w-16 shrink-0 overflow-hidden rounded-lg border-2 transition-colors">
                            <img src="{{ $url }}" alt="" class="h-full w-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ===== Colonne d'achat (collante) ===== --}}
        <div class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-line bg-surface p-5 shadow-[0_10px_40px_-28px_rgba(46,44,40,0.25)]">
                @if ($offer->summary)<p class="text-sm text-ink-2">{{ $offer->summary }}</p>@endif

                <div class="mt-4 flex items-end gap-3">
                    <span class="font-display text-3xl font-bold tracking-[-0.02em] text-ink">{{ $money($offer->effectivePrice()) }}</span>
                    <span class="pb-1 text-sm text-ink-3">{{ $offer->currency }}</span>
                    @if ($hasSale)
                        <span class="pb-1 text-sm text-ink-3 line-through">{{ $money($offer->price) }}</span>
                        <span class="mb-1 rounded-full bg-rust px-2 py-0.5 text-[11px] font-bold text-white">−{{ $off }}%</span>
                    @endif
                </div>

                {{-- Achat --}}
                @if ($hasVariants)
                    <form method="POST" action="{{ route('marketplace.cart.add') }}" class="mt-4"
                          x-data="{
                              variants: {{ \Illuminate\Support\Js::from($variantsJson) }},
                              axes: {{ \Illuminate\Support\Js::from($axes) }},
                              sel: {},
                              get current() { return this.variants.find(v => Object.keys(this.axes).every(a => v.options[a] === this.sel[a])); },
                              get chosen() { return Object.keys(this.axes).every(a => this.sel[a]); },
                          }">
                        @csrf
                        <input type="hidden" name="offer_id" value="{{ $offer->id }}">
                        <input type="hidden" name="variant_id" :value="current?.id || ''">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <template x-for="(values, axis) in axes" :key="axis">
                                <label class="text-sm">
                                    <span class="text-ink-2 capitalize" x-text="axis"></span>
                                    <select x-model="sel[axis]" class="mt-1 h-10 w-full rounded-xl border border-line bg-surface px-3 text-sm">
                                        <option value="">Choisir…</option>
                                        <template x-for="val in values" :key="val"><option :value="val" x-text="val"></option></template>
                                    </select>
                                </label>
                            </template>
                        </div>
                        <p class="mt-2 text-sm" x-show="current" x-cloak>
                            <span class="font-semibold text-ink" x-text="new Intl.NumberFormat('fr-FR').format(current?.price || 0) + ' {{ $offer->currency }}'"></span>
                            <span class="ml-2 text-ink-3" x-text="current?.stock > 0 ? current.stock + ' en stock' : 'épuisé'"></span>
                        </p>
                        <p class="mt-2 text-sm text-rust" x-show="chosen && !current" x-cloak>Cette combinaison n'est pas disponible.</p>
                        <div class="mt-4 flex items-center gap-3">
                            <input type="number" name="quantity" value="1" min="1" max="20" class="h-11 w-20 rounded-xl border border-line bg-surface px-3 text-sm">
                            <x-ui.button type="submit" variant="primary" :full="true" ::disabled="!current || current.stock <= 0">Ajouter au panier</x-ui.button>
                        </div>
                    </form>
                @else
                    <form method="POST" action="{{ route('marketplace.cart.add') }}" class="mt-4 flex items-center gap-3">
                        @csrf
                        <input type="hidden" name="offer_id" value="{{ $offer->id }}">
                        <input type="number" name="quantity" value="1" min="1" max="20" class="h-11 w-20 rounded-xl border border-line bg-surface px-3 text-sm">
                        <x-ui.button type="submit" variant="primary" :full="true" :disabled="$soldOutSimple">
                            {{ $soldOutSimple ? 'Épuisé' : 'Ajouter au panier' }}
                        </x-ui.button>
                    </form>
                @endif

                {{-- Réassurance --}}
                <ul class="mt-4 space-y-1.5 border-t border-line pt-4 text-[13px] text-ink-2">
                    <li class="flex items-center gap-2"><svg class="h-4 w-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M9 12l2 2 4-4M12 3l7 4v5c0 5-3 7-7 9-4-2-7-4-7-9V7z"/></svg> Vendeur vérifié par MALKUTHAR</li>
                    <li class="flex items-center gap-2"><svg class="h-4 w-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 2l8 4v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6z"/></svg> Paiement protégé, litiges arbitrés</li>
                    <li class="flex items-center gap-2"><svg class="h-4 w-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        @if ($offer->type === 'physical') Livraison suivie
                        @elseif ($offer->type === 'digital') Livraison immédiate après paiement
                        @elseif ($offer->type === 'service') Créneau réel confirmé
                        @else Accès en ligne, à votre rythme @endif
                    </li>
                </ul>
            </div>

            {{-- Carte vendeur --}}
            <a href="{{ route('marketplace.shop.vendor', $offer->vendor->slug) }}" class="mt-3 flex items-center gap-3 rounded-2xl border border-line bg-surface p-4 transition-colors hover:border-line-strong">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-ground-alt">
                    @if ($offer->vendor->logo_path)
                        <img src="{{ Storage::url($offer->vendor->logo_path) }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="font-display font-bold text-ink-3">{{ \Illuminate\Support\Str::substr($offer->vendor->display_name, 0, 1) }}</span>
                    @endif
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-ink">{{ $offer->vendor->display_name }}</span>
                    <span class="block text-xs text-ink-3">Voir la boutique →</span>
                </span>
            </a>
        </div>
    </div>

    {{-- ===== Onglets ===== --}}
    <div class="mt-12" x-data="{ t: 'desc' }">
        <div class="flex gap-1 border-b border-line text-sm">
            <button type="button" x-on:click="t='desc'" :class="t==='desc' ? 'border-accent font-semibold text-ink' : 'border-transparent text-ink-3'" class="border-b-2 px-3 py-2">Description</button>
            @if ($specs->isNotEmpty())
                <button type="button" x-on:click="t='specs'" :class="t==='specs' ? 'border-accent font-semibold text-ink' : 'border-transparent text-ink-3'" class="border-b-2 px-3 py-2">Caractéristiques</button>
            @endif
            <button type="button" x-on:click="t='avis'" :class="t==='avis' ? 'border-accent font-semibold text-ink' : 'border-transparent text-ink-3'" class="border-b-2 px-3 py-2">Avis ({{ $offer->rating_count }})</button>
        </div>

        <div x-show="t==='desc'" class="prose-sm mt-5 max-w-2xl whitespace-pre-line text-ink-2">{{ $offer->description }}</div>

        @if ($specs->isNotEmpty())
            <dl x-show="t==='specs'" x-cloak class="mt-5 grid max-w-xl grid-cols-1 gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                @foreach ($specs as $s)
                    <div class="flex justify-between border-b border-line py-1.5"><dt class="text-ink-3">{{ $s['label'] }}</dt><dd class="text-ink">{{ $s['value'] }}</dd></div>
                @endforeach
            </dl>
        @endif

        <div x-show="t==='avis'" x-cloak class="mt-5">
            @if (! empty($reviewableItem))
                <form method="POST" action="{{ route('marketplace.reviews.store') }}" class="mb-6 max-w-lg rounded-xl border border-line bg-surface p-4">
                    @csrf
                    <input type="hidden" name="order_item_id" value="{{ $reviewableItem->id }}">
                    <label class="text-sm text-ink-2">Votre note
                        <select name="rating" class="ml-2 h-8 rounded border border-line bg-surface px-2 text-sm">
                            @for ($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ $i }} ★</option>@endfor
                        </select>
                    </label>
                    <input name="title" placeholder="Titre (facultatif)" class="mt-2 h-9 w-full rounded-lg border border-line bg-surface px-2 text-sm">
                    <textarea name="body" rows="3" placeholder="Votre expérience…" class="mt-2 w-full rounded-lg border border-line bg-surface px-2 py-1.5 text-sm"></textarea>
                    <div class="mt-2"><x-ui.button type="submit" variant="secondary">Publier mon avis</x-ui.button></div>
                </form>
            @endif
            <div class="space-y-4">
                @forelse ($reviews as $r)
                    <div class="border-b border-line pb-4">
                        <p class="text-sm text-ink">{{ str_repeat('★', $r->rating) }}<span class="text-line-strong">{{ str_repeat('★', 5 - $r->rating) }}</span>
                            @if ($r->title)<span class="ml-2 font-medium">{{ $r->title }}</span>@endif
                        </p>
                        @if ($r->body)<p class="mt-1 text-sm text-ink-2">{{ $r->body }}</p>@endif
                        <p class="mt-1 text-xs text-ink-3">{{ $r->created_at->translatedFormat('d F Y') }}</p>
                        @if ($r->vendor_reply)
                            <p class="mt-2 rounded-lg bg-ground-alt/60 px-3 py-2 text-sm text-ink-2"><span class="font-medium text-ink">{{ $offer->vendor->display_name }} :</span> {{ $r->vendor_reply }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-ink-3">Pas encore d'avis.</p>
                @endforelse
            </div>
        </div>
    </div>

    @if ($related->isNotEmpty())
        <div class="mt-14">
            <h2 class="font-display text-lg font-semibold text-ink">Dans la même catégorie</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($related as $r)
                    <x-marketplace.offer-card :offer="$r" />
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
