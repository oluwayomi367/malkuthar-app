@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'required' => false,
])

@php
    $id = $attributes->get('id', $name);
    $selected = old($name, $selected);
    $hasError = isset($errors) && $errors->has($name);
@endphp

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $id }}" class="text-[13px] font-medium text-ink-2">
            {{ $label }}@if ($required)<span class="text-accent"> *</span>@endif
        </label>
    @endif

    <div class="relative">
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            @if ($required) required @endif
            {{ $attributes->except('id')->merge([
                'class' => 'h-11 w-full appearance-none rounded-xl border bg-surface pl-3.5 pr-9 text-[15px] text-ink '
                    .'transition-colors focus:outline-none focus:ring-2 '
                    .($hasError ? 'border-rust/60 focus:ring-rust/20' : 'border-black/10 focus:border-accent focus:ring-accent/25'),
            ]) }}
        >
            @if ($placeholder)
                <option value="" @selected($selected === null || $selected === '')>{{ $placeholder }}</option>
            @endif
            @foreach ($options as $key => $option)
                @php
                    $optValue = is_array($option) ? ($option['value'] ?? $key) : $key;
                    $optLabel = is_array($option) ? ($option['label'] ?? $option['value'] ?? $key) : $option;
                @endphp
                <option value="{{ $optValue }}" @selected((string) $selected === (string) $optValue)>{{ $optLabel }}</option>
            @endforeach
        </select>
        <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M4 6l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>

    @if ($hasError)
        <p class="text-xs text-rust">{{ $errors->first($name) }}</p>
    @endif
</div>
