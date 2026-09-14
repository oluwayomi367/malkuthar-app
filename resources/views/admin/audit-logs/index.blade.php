@extends('layouts.admin')

@section('title', "Administration — Journal d'audit")
@section('eyebrow', 'Traçabilité')
@section('heading', "Journal d'audit")

@section('content')
<form method="GET" class="mb-6 flex flex-wrap gap-2">
    <select name="action" onchange="this.form.submit()"
            class="h-11 rounded-xl border border-black/10 bg-surface px-3 text-sm text-ink focus:border-accent focus:outline-none">
        <option value="">Toutes les actions</option>
        @foreach ($actions as $a)
            <option value="{{ $a }}" @selected($action === $a)>{{ $a }}</option>
        @endforeach
    </select>
    <noscript><button class="h-11 rounded-xl border border-black/10 px-4 text-sm font-semibold text-ink">Filtrer</button></noscript>
</form>

@if ($logs->isEmpty())
    <x-ui.empty-state title="Journal vide" text="Aucune action enregistrée pour ce filtre." />
@else
    <div class="overflow-hidden rounded-2xl border border-black/10">
        @foreach ($logs as $log)
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-black/[0.06] px-4 py-3.5 text-sm last:border-0">
                <div class="min-w-0">
                    <p class="text-ink">{{ $log->description }}</p>
                    <p class="mt-0.5 flex flex-wrap gap-x-3 font-mono text-[11px] text-ink-3">
                        <span>{{ $log->action }}</span>
                        @if ($log->ip_address)<span>IP {{ $log->ip_address }}</span>@endif
                        @if ($log->subject_type)<span>{{ class_basename($log->subject_type) }} #{{ \Illuminate\Support\Str::limit($log->subject_id, 8, '') }}</span>@endif
                    </p>
                </div>
                <p class="shrink-0 text-right text-xs text-ink-3">
                    {{ optional($log->admin)->first_name }} {{ optional($log->admin)->last_name }}<br>
                    {{ $log->created_at?->format('d/m/Y H:i') }}
                </p>
            </div>
        @endforeach
    </div>

    <x-ui.pagination :paginator="$logs" />
@endif
@endsection
