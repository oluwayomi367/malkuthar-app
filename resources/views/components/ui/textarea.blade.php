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

<div class="group flex flex-col gap-1.5">
    @if ($label)
        <div class="flex items-center justify-between">
            <label for="{{ $id }}" class="text-[12.5px] font-semibold tracking-wide text-ink-2 transition-colors group-focus-within:text-accent">
                {{ $label }}@if ($required)<span class="ml-1 text-accent font-bold" title="Requis">*</span>@endif
            </label>
            @if ($hint)
                <span class="text-[11px] text-ink-3">{{ $hint }}</span>
            @endif
        </div>
    @endif

    <textarea
        id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
        @if ($required) required @endif
        {{ $attributes->except('id')->merge([
            'class' => 'w-full rounded-xl border bg-surface px-3.5 py-3 text-[14.5px] leading-relaxed text-ink placeholder:text-ink-3/70 '
                .'shadow-[0_1px_2px_rgba(46,44,40,0.03),inset_0_1px_1px_rgba(255,255,255,0.85)] '
                .'transition-all duration-200 focus:outline-none focus:ring-4 '
                .($hasError
                    ? 'border-rust/70 bg-rust/[0.015] focus:border-rust focus:ring-rust/15'
                    : 'border-line/90 hover:border-line-strong focus:border-accent focus:bg-surface focus:ring-accent/15 focus:shadow-[0_0_0_1px_var(--color-accent),0_2px_8px_rgba(138,109,44,0.08)]'),
        ]) }}
    >{{ old($name, $slot ?? '') }}</textarea>

    @if ($hasError)
        <p class="flex items-center gap-1 text-[11.5px] font-medium text-rust">
            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 16 16" fill="currentColor">
                <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm-.75-4.25a.75.75 0 1 1 1.5 0 .75.75 0 0 1-1.5 0zM8 4a.75.75 0 0 1 .75.75v4a.75.75 0 0 1-1.5 0v-4A.75.75 0 0 1 8 4z" clip-rule="evenodd" />
            </svg>
            <span>{{ $errors->first($name) }}</span>
        </p>
    @endif
</div>
