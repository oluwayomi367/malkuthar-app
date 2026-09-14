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
    ];
    [$label, $tone] = $map[$status] ?? [ucfirst((string) $status), 'neut'];

    $tones = [
        'wait' => 'border-amber/30 bg-amber/10 text-amber',
        'ok'   => 'border-brand-green/30 bg-brand-green/10 text-brand-green',
        'neut' => 'border-black/12 bg-black/[0.04] text-ink-2',
        'bad'  => 'border-rust/30 bg-rust/10 text-rust',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 '
        .'text-[11.5px] font-semibold '.$tones[$tone],
]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $label }}
</span>
