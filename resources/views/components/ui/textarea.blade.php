@props([
    'name',
    'label' => null,
    'rows' => 5,
    'hint' => null,
    'required' => false,
])

@php
    $id = $attributes->get('id', $name);
    $hasError = isset($errors) && $errors->has($name);
@endphp

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $id }}" class="text-[13px] font-medium text-ink-2">
            {{ $label }}@if ($required)<span class="text-accent"> *</span>@endif
        </label>
    @endif
    @if ($hint)
        <p class="-mt-0.5 text-xs text-ink-3">{{ $hint }}</p>
    @endif

    <textarea
        id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
        @if ($required) required @endif
        {{ $attributes->except('id')->merge([
            'class' => 'rounded-xl border bg-surface px-3.5 py-3 text-[15px] leading-relaxed text-ink placeholder:text-ink-3 '
                .'transition-colors focus:outline-none focus:ring-2 '
                .($hasError
                    ? 'border-rust/60 focus:border-rust focus:ring-rust/20'
                    : 'border-black/10 focus:border-accent focus:ring-accent/25'),
        ]) }}
    >{{ old($name, $slot ?? '') }}</textarea>

    @if ($hasError)
        <p class="text-xs text-rust">{{ $errors->first($name) }}</p>
    @endif
</div>
