@props([
    'locked' => null,
    'value' => null,
])

@php
    $referralResolver = app(\App\Services\ReferralResolver::class);
    $linkOrCookieCode = $referralResolver->codeFromLinkOrCookie(request());
    $isLocked = $locked ?? $referralResolver->isLocked(request());
    $activeCode = old('referral_code', $value ?? $linkOrCookieCode);
    $sponsor = $activeCode ? $referralResolver->byCode($activeCode) : null;
@endphp

@if ($isLocked && $activeCode)
    <div class="flex flex-col gap-1.5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <label for="referral_code" class="text-[12.5px] font-semibold tracking-wide text-ink-2">
                Code de parrainage
            </label>
            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-ink-3">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="5" y="11" width="14" height="10" rx="2" /><path d="M8 11V7a4 4 0 018 0v4" stroke-linecap="round" />
                </svg>
                Lien détecté — Verrouillé
            </span>
        </div>

        <div class="relative flex items-center">
            <input
                id="referral_code"
                name="referral_code"
                type="text"
                readonly
                aria-describedby="referral_code_help"
                value="{{ $activeCode }}"
                class="h-11 w-full cursor-not-allowed rounded-xl border border-line bg-ground-alt px-3.5 pr-36 font-mono text-[14.5px] font-semibold text-ink focus:outline-none"
            >
            <span class="pointer-events-none absolute right-2 inline-flex max-w-[8.5rem] items-center gap-1.5 truncate rounded-lg border border-line bg-surface px-2 py-1 text-xs font-medium text-sage">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span class="truncate">{{ $sponsor ? ($sponsor->first_name ?: 'Parrain validé') : 'Parrain validé' }}</span>
            </span>
        </div>

        <p id="referral_code_help" class="text-xs leading-relaxed text-ink-3">
            Non modifiable : ce code provient du lien de recommandation que vous avez suivi.
        </p>
    </div>
@else
    <x-ui.field
        name="referral_code"
        label="Code de parrainage"
        hint="Facultatif — si un parrain vous a recommandé"
        :value="$activeCode"
        placeholder="Ex. : JEAN125"
        autocomplete="off"
    />
@endif
