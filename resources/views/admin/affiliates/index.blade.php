@extends('layouts.admin')

@section('title', 'Administration — Affiliés')
@section('eyebrow', 'Gestion')
@section('heading', 'Affiliés')

@section('content')
@php $m = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<form method="GET" class="mb-6 flex gap-2">
    <input type="search" name="q" value="{{ $q }}" placeholder="Nom, email, identifiant, code de parrainage…"
           class="h-11 flex-1 rounded-xl border border-black/10 bg-surface px-3.5 text-[15px] text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25">
    <x-ui.button variant="primary" type="submit">Rechercher</x-ui.button>
</form>

@if ($affiliates->isEmpty())
    <x-ui.empty-state title="Aucun affilié" text="{{ $q ? 'Aucun résultat pour cette recherche.' : 'Aucun compte n\'a encore activé son espace affilié.' }}" />
@else
    <div class="overflow-x-auto rounded-2xl border border-black/10">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="bg-surface text-[10.5px] uppercase tracking-[0.09em] text-ink-3">
                <tr>
                    <th class="px-4 py-3 font-semibold">Affilié</th>
                    <th class="px-4 py-3 font-semibold">Identifiant</th>
                    <th class="px-4 py-3 font-semibold">Filleuls N1</th>
                    <th class="px-4 py-3 font-semibold">Statut</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black/[0.06]">
                @foreach ($affiliates as $a)
                    <tr class="transition-colors hover:bg-surface">
                        <td class="px-4 py-3">
                            <p class="text-ink">{{ trim("{$a->first_name} {$a->last_name}") }}</p>
                            <p class="text-xs text-ink-3">{{ $a->email }}</p>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-ink-2">{{ $a->affiliate_id }}<br>{{ $a->code_parrainage }}</td>
                        <td class="px-4 py-3 text-ink-2">{{ $m($a->children_count) }}</td>
                        <td class="px-4 py-3"><x-ui.status-pill :status="$a->status === 'active' ? 'available' : 'failed'" /></td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.affiliates.show', $a) }}" class="text-accent hover:underline">Ouvrir →</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <x-ui.pagination :paginator="$affiliates" />
@endif
@endsection
