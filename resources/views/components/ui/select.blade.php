@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'required' => false,
    'hint' => null,
])

@php
    // « detail[sku] » → clé « detail.sku » pour old() et les erreurs de validation.
    $key = trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $id = $attributes->get('id', str_contains($name, '[') ? str_replace('.', '-', $key) : $name);
    $selected = old($key, $selected);
    $hasError = isset($errors) && $errors->has($key);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($hasError ? $id.'-error' : ''));
@endphp

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $id }}" class="text-sm font-semibold text-ink">
            {{ $label }}@if ($required)<span class="ml-0.5 text-rust" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <div class="relative">
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            @if ($required) required @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($hasError) aria-invalid="true" @endif
            {{ $attributes->except('id')->merge([
                'class' => 'min-h-11 w-full cursor-pointer appearance-none rounded-xl border bg-surface py-2 pl-3.5 pr-10 text-[15px] text-ink '
                    .'transition-colors focus:outline-none focus:ring-2 '
                    .($hasError
                        ? 'border-rust focus:border-rust focus:ring-rust/20'
                        : 'border-line-strong hover:border-ink-3 focus:border-accent focus:ring-accent/20'),
            ]) }}
        >
            @if ($placeholder)
                <option value="" @selected($selected === null || $selected === '')>{{ $placeholder }}</option>
            @endif
            @foreach ($options as $optKey => $option)
                @php
                    $optValue = is_array($option) ? ($option['value'] ?? $optKey) : $optKey;
                    $optLabel = is_array($option) ? ($option['label'] ?? $option['value'] ?? $optKey) : $option;
                @endphp
                <option value="{{ $optValue }}" @selected((string) $selected === (string) $optValue)>{{ $optLabel }}</option>
            @endforeach
        </select>
        <svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-3" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
            <path d="M6 8l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>

    @if ($hint)
        <p id="{{ $id }}-hint" class="text-[13px] leading-snug text-ink-3">{{ $hint }}</p>
    @endif

    @if ($hasError)
        <p id="{{ $id }}-error" class="text-[13px] font-medium text-rust">{{ $errors->first($key) }}</p>
    @endif
</div>
