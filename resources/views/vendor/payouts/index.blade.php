@extends('layouts.app')

@section('title', 'Mes versements')
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
@php
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $methodLabel = ['mobile_money' => 'Mobile Money', 'bank_transfer' => 'Virement bancaire'];
@endphp

<header>
    <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Mes versements</h1>
    <p class="mt-2 text-[15px] text-ink-2">Demandez le versement de votre solde et gérez vos moyens de réception.</p>
</header>

<div class="mt-8 grid gap-6 lg:grid-cols-2 lg:items-start">
    {{-- Demande --}}
    <section class="rounded-2xl bg-ink p-6 text-white sm:p-7" aria-labelledby="payout-balance">
        <h2 id="payout-balance" class="font-sans text-sm font-semibold text-accent-light">Solde disponible</h2>
        <p class="mt-3 font-display text-[44px] font-semibold leading-none">
            {{ $money($balance->available_amount) }}<span class="ml-2 font-sans text-base font-medium text-white/70">{{ $balance->currency }}</span>
        </p>

        @if ($accounts->isEmpty())
            <p class="mt-5 rounded-xl border border-white/20 px-4 py-3 text-sm text-white/85">Ajoutez un moyen de versement pour pouvoir demander un retrait.</p>
        @else
            <form method="POST" action="{{ route('vendor.payouts.store') }}" class="mt-6 grid gap-5 border-t border-white/15 pt-5">
                @csrf
                <div class="flex flex-col gap-1.5">
                    <label for="payout-amount" class="text-sm font-semibold text-white">Montant</label>
                    <input id="payout-amount" name="amount" type="number" step="0.01" min="0" placeholder="{{ $money($balance->available_amount) }}"
                           value="{{ old('amount') }}" aria-describedby="payout-amount-hint"
                           class="min-h-11 w-full rounded-xl border border-white/25 bg-white/10 px-3.5 text-[15px] text-white placeholder:text-white/60 focus:border-accent-light focus:outline-none focus:ring-2 focus:ring-accent-light/30">
                    <p id="payout-amount-hint" class="text-[13px] text-white/70">Laissez vide pour demander tout le solde.</p>
                    @error('amount')<p class="text-[13px] font-medium text-rust-tint">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="payout-account" class="text-sm font-semibold text-white">Vers</label>
                    <select id="payout-account" name="payout_account_id" class="min-h-11 w-full rounded-xl border border-white/25 bg-white/10 px-3.5 text-[15px] text-white focus:border-accent-light focus:outline-none focus:ring-2 focus:ring-accent-light/30">
                        @foreach ($accounts as $acc)
                            <option value="{{ $acc->id }}" class="text-ink" @selected($acc->is_default)>{{ $acc->label }} ({{ $methodLabel[$acc->method] ?? $acc->method }})</option>
                        @endforeach
                    </select>
                    @error('account')<p class="text-[13px] font-medium text-rust-tint">{{ $message }}</p>@enderror
                </div>
                <div>
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-full bg-accent px-6 text-sm font-semibold text-white transition-colors hover:bg-accent-2">Demander le versement</button>
                </div>
            </form>
        @endif
    </section>

    {{-- Moyens de versement --}}
    <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="payout-methods">
        <h2 id="payout-methods" class="font-sans text-lg font-semibold text-ink">Moyens de versement</h2>

        @if ($accounts->isEmpty())
            <p class="mt-2 text-sm text-ink-2">Aucun moyen enregistré.</p>
        @else
            <ul class="mt-3 divide-y divide-line rounded-xl border border-line">
                @foreach ($accounts as $acc)
                    <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-4 py-2.5 text-sm">
                        <span>
                            <span class="font-medium text-ink">{{ $acc->label }}</span>
                            <span class="text-ink-2"> · {{ $methodLabel[$acc->method] ?? $acc->method }}</span>
                            @if ($acc->is_default) <span class="ml-1 rounded-full bg-space-vendor-soft px-2 py-0.5 text-xs font-semibold text-space-vendor">Par défaut</span>@endif
                        </span>
                        <span class="flex gap-1">
                            @unless ($acc->is_default)
                                <form method="POST" action="{{ route('vendor.payouts.accounts.default', $acc) }}">@csrf
                                    <button class="inline-flex min-h-11 items-center px-2 text-sm font-semibold text-ink underline decoration-line-strong underline-offset-4 hover:decoration-ink">Définir par défaut</button>
                                </form>
                            @endunless
                            <form method="POST" action="{{ route('vendor.payouts.accounts.destroy', $acc) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')
                                <button class="inline-flex min-h-11 items-center px-2 text-sm font-semibold text-rust underline decoration-rust/40 underline-offset-4 hover:decoration-rust">Supprimer</button>
                            </form>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('vendor.payouts.accounts.store') }}" class="mt-6 border-t border-line pt-5">
            @csrf
            <h3 class="text-base font-semibold text-ink">Ajouter un moyen</h3>
            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                <x-ui.select name="method" label="Type" :options="$methodLabel" />
                <x-ui.field name="label" label="Libellé" :required="true" placeholder="Ex. : MTN" />
                <div class="sm:col-span-2">
                    <x-ui.field name="number" label="Numéro ou IBAN" :required="true" />
                </div>
            </div>
            <div class="mt-5"><x-ui.button type="submit" variant="secondary">Ajouter</x-ui.button></div>
        </form>
    </section>
</div>

<section class="mt-12" aria-labelledby="payout-history">
    <h2 id="payout-history" class="font-display text-[28px] font-semibold text-ink">Historique</h2>
    @if ($payouts->isEmpty())
        <x-ui.empty-state class="mt-4" icon="wallet" title="Aucune demande de versement" />
    @else
        <ul class="mt-4 divide-y divide-line rounded-2xl border border-line bg-surface">
            @foreach ($payouts as $p)
                <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-5 py-4 text-sm">
                    <span>
                        <span class="font-mono text-ink">{{ $p->reference }}</span>
                        <span class="ml-1 text-ink-2">· {{ $p->created_at->format('d/m/Y') }}</span>
                    </span>
                    <span class="flex items-center gap-3">
                        <span class="whitespace-nowrap font-semibold text-ink">{{ $money($p->net_amount) }} {{ $p->currency }}</span>
                        <x-ui.status-pill :status="$p->status" />
                    </span>
                </li>
            @endforeach
        </ul>
        <x-ui.pagination :paginator="$payouts" />
    @endif
</section>
@endsection
