@extends('layouts.app')

@section('title', 'Mes versements')
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
@php $money = fn ($n) => number_format((float) $n, 0, ',', ' '); @endphp

<p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Espace vendeur</p>
<h1 class="mt-1 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Mes versements</h1>

<div class="mt-5 grid gap-6 lg:grid-cols-[1fr_1fr]">
    {{-- Demande --}}
    <section class="rounded-2xl border border-line bg-surface p-5">
        <p class="text-sm text-ink-2">Solde disponible</p>
        <p class="font-display text-2xl font-bold text-ink">{{ $money($balance->available_amount) }} {{ $balance->currency }}</p>

        @if ($accounts->isEmpty())
            <p class="mt-3 text-sm text-rust">Ajoutez un moyen de versement pour pouvoir demander un retrait.</p>
        @else
            <form method="POST" action="{{ route('vendor.payouts.store') }}" class="mt-4 grid gap-3">
                @csrf
                <label class="text-sm">
                    <span class="text-ink-2">Montant (vide = tout le solde)</span>
                    <input name="amount" type="number" step="0.01" min="0" placeholder="{{ $money($balance->available_amount) }}"
                           class="mt-1 h-10 w-full rounded-xl border border-line bg-surface px-3 text-sm">
                    @error('amount')<span class="text-xs text-rust">{{ $message }}</span>@enderror
                </label>
                <label class="text-sm">
                    <span class="text-ink-2">Vers</span>
                    <select name="payout_account_id" class="mt-1 h-10 w-full rounded-xl border border-line bg-surface px-3 text-sm">
                        @foreach ($accounts as $acc)
                            <option value="{{ $acc->id }}" @selected($acc->is_default)>{{ $acc->label }} ({{ $acc->method }})</option>
                        @endforeach
                    </select>
                    @error('account')<span class="text-xs text-rust">{{ $message }}</span>@enderror
                </label>
                <div><x-ui.button type="submit" variant="primary">Demander le versement</x-ui.button></div>
            </form>
        @endif
    </section>

    {{-- Moyens de versement --}}
    <section class="rounded-2xl border border-line bg-surface p-5">
        <h2 class="font-display text-base font-semibold text-ink">Moyens de versement</h2>
        <ul class="mt-3 space-y-2 text-sm">
            @foreach ($accounts as $acc)
                <li class="flex items-center justify-between rounded-lg border border-line px-3 py-2">
                    <span>{{ $acc->label }} <span class="text-ink-3">· {{ $acc->method }}</span>@if ($acc->is_default) <span class="text-xs text-accent">(défaut)</span>@endif</span>
                    <span class="flex gap-2">
                        @unless ($acc->is_default)
                            <form method="POST" action="{{ route('vendor.payouts.accounts.default', $acc) }}">@csrf
                                <button class="text-xs text-accent hover:underline">Par défaut</button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('vendor.payouts.accounts.destroy', $acc) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')
                            <button class="text-xs text-rust hover:underline">Supprimer</button>
                        </form>
                    </span>
                </li>
            @endforeach
        </ul>
        <form method="POST" action="{{ route('vendor.payouts.accounts.store') }}" class="mt-4 grid gap-2 border-t border-line pt-4">
            @csrf
            <div class="grid grid-cols-2 gap-2">
                <select name="method" class="h-9 rounded-lg border border-line bg-surface px-2 text-sm">
                    <option value="mobile_money">Mobile Money</option>
                    <option value="bank_transfer">Virement bancaire</option>
                </select>
                <input name="label" required placeholder="Libellé (ex. MTN)" class="h-9 rounded-lg border border-line bg-surface px-2 text-sm">
            </div>
            <input name="number" required placeholder="Numéro / IBAN" class="h-9 rounded-lg border border-line bg-surface px-2 text-sm">
            <div><x-ui.button type="submit" variant="secondary">Ajouter</x-ui.button></div>
        </form>
    </section>
</div>

<h2 class="mt-8 font-display text-base font-semibold text-ink">Historique</h2>
@if ($payouts->isEmpty())
    <p class="mt-2 text-sm text-ink-3">Aucune demande de versement.</p>
@else
    <div class="mt-3 space-y-2">
        @foreach ($payouts as $p)
            <div class="flex items-center justify-between rounded-xl border border-line bg-surface px-4 py-3 text-sm">
                <span class="font-mono text-ink">{{ $p->reference }} <span class="text-ink-3">· {{ $p->created_at->format('d/m/Y') }}</span></span>
                <span class="flex items-center gap-3">
                    <span class="font-semibold text-ink">{{ $money($p->net_amount) }} {{ $p->currency }}</span>
                    <x-ui.status-pill :status="$p->status" />
                </span>
            </div>
        @endforeach
    </div>
    <x-ui.pagination :paginator="$payouts" class="mt-4" />
@endif
@endsection
