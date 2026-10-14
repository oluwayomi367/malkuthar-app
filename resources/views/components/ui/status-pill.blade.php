@props([
    'status',
])

@php
    $map = [
        'pending'    => ['En attente',    'wait'],
        'available'  => ['Disponible',    'ok'],
        'processing' => ['En traitement', 'neut'],
        'paid'       => ['Payé',          'neut'],
        'cancelled'  => ['Annulé',        'bad'],
        'reversed'   => ['Repris',        'bad'],
        'failed'     => ['Échoué',        'bad'],
        // Marketplace — comptes vendeurs & modération
        'active'     => ['Actif',         'ok'],
        'suspended'  => ['Suspendu',      'bad'],
        'closed'     => ['Fermé',         'neut'],
        'approved'   => ['Approuvé',      'ok'],
        'rejected'   => ['Rejeté',        'bad'],
        'draft'      => ['Brouillon',     'neut'],
        'pending_review'     => ['En revue',        'wait'],
        'changes_requested'  => ['À corriger',      'wait'],
        'published'  => ['En ligne',      'ok'],
        'archived'   => ['Archivé',       'neut'],
        // Marketplace — préparation & règlement des sous-commandes
        'unfulfilled' => ['À traiter',    'wait'],
        'shipped'    => ['Expédié',       'neut'],
        'delivered'  => ['Livré',         'ok'],
        'completed'  => ['Terminé',       'ok'],
        'on_hold'    => ['Bloqué',        'wait'],
        'in_payout'  => ['En versement',  'neut'],
        'refunded'   => ['Remboursé',     'bad'],
        'partially_refunded' => ['Remboursé en partie', 'wait'],
        'hidden'     => ['Masqué',        'neut'],
    ];
    [$label, $tone] = $map[$status] ?? [ucfirst((string) $status), 'neut'];

    $tones = [
        'wait' => 'border-amber/25 bg-amber-tint text-amber',
        'ok'   => 'border-sage/25 bg-sage-soft text-sage',
        'neut' => 'border-line-strong bg-ground-alt text-ink-2',
        'bad'  => 'border-rust/25 bg-rust-tint text-rust',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 '
        .'text-xs font-semibold '.$tones[$tone],
]) }}>
    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true"></span>{{ $label }}
</span>
