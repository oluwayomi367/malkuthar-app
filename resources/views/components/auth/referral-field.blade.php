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
    <div class="group flex flex-col gap-1.5">
        <div class="flex items-center justify-between">
            <label for="referral_code" class="flex items-center gap-1.5 text-[12.5px] font-semibold tracking-wide text-ink">
                <svg class="h-3.5 w-3.5 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <span>Code de parrainage</span>
                <span class="inline-flex items-center rounded-full bg-accent-soft px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-accent border border-accent/30">
                    Lien détecté — Verrouillé
                </span>
            </label>
            <span class="text-[11px] font-semibold text-accent flex items-center gap-1">
                <span>🔒</span> Non modifiable
            </span>
        </div>

        <div class="relative flex items-center">
            <input
                id="referral_code"
                name="referral_code"
                type="text"
                readonly
                tabindex="-1"
                value="{{ $activeCode }}"
                class="h-11 w-full rounded-xl border border-accent/40 bg-accent-soft/20 px-3.5 font-mono text-[14.5px] font-bold text-ink cursor-not-allowed select-none shadow-inner focus:outline-none focus:ring-0"
            >
            <div class="absolute right-3 flex items-center gap-1.5 rounded-lg bg-surface/90 border border-line/70 px-2 py-1 text-[11px] font-semibold text-brand-green select-none pointer-events-none shadow-xs">
                <svg class="h-3.5 w-3.5 text-brand-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ $sponsor ? ($sponsor->first_name ?: 'Parrain validé') : 'Parrain validé' }}</span>
            </div>
        </div>

        <p class="text-[11px] leading-relaxed text-ink-3">
            Ce code provient du lien de recommandation que vous avez suivi. Il est sécurisé et ne peut pas être modifié.
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
