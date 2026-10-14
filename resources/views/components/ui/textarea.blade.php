@props([
    'name',
    'label' => null,
    'rows' => 5,
    'hint' => null,
    'required' => false,
])

@php
    // « detail[sku] » → clé « detail.sku » pour old() et les erreurs de validation.
    $key = trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $id = $attributes->get('id', str_contains($name, '[') ? str_replace('.', '-', $key) : $name);
    $hasError = isset($errors) && $errors->has($key);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($hasError ? $id.'-error' : ''));
@endphp

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $id }}" class="text-sm font-semibold text-ink">
            {{ $label }}@if ($required)<span class="ml-0.5 text-rust" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <textarea
        id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
        @if ($required) required @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes->except('id')->merge([
            'class' => 'w-full rounded-xl border bg-surface px-3.5 py-3 text-[15px] leading-relaxed text-ink placeholder:text-ink-3 '
                .'transition-colors focus:outline-none focus:ring-2 '
                .($hasError
                    ? 'border-rust focus:border-rust focus:ring-rust/20'
                    : 'border-line-strong hover:border-ink-3 focus:border-accent focus:ring-accent/20'),
        ]) }}
    >{{ old($key, $slot ?? '') }}</textarea>

    @if ($hint)
        <p id="{{ $id }}-hint" class="text-[13px] leading-snug text-ink-3">{{ $hint }}</p>
    @endif

    @if ($hasError)
        <p id="{{ $id }}-error" class="text-[13px] font-medium text-rust">{{ $errors->first($key) }}</p>
    @endif
</div>
