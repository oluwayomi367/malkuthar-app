@php $accordions = $accordions ?? []; @endphp

<section class="border-t border-line pt-8">
    <h2 class="text-lg font-bold text-ink">Description</h2>
    <div class="mt-3 max-w-2xl text-[15px] leading-relaxed text-ink-2">
        {!! nl2br(e($product->description)) !!}
    </div>

    @if (! empty($accordions))
        <div class="mt-6 max-w-2xl divide-y divide-line border-y border-line">
            @foreach ($accordions as $title => $body)
                <details class="group py-3">
                    <summary class="flex cursor-pointer list-none items-center justify-between text-[15px] font-medium text-ink">
                        {{ $title }}
                        <svg class="h-4 w-4 text-ink-3 transition-transform group-open:rotate-180" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M4 6l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </summary>
                    <p class="mt-2 text-sm text-ink-2">{!! $body !!}</p>
                </details>
            @endforeach
        </div>
    @endif
</section>
