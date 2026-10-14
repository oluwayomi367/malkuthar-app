<div {{ $attributes->merge([
    'class' => 'fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface px-4 py-3 lg:hidden',
]) }}
    style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
    <div class="mx-auto flex max-w-[1280px] items-center gap-3">
        @isset($info)
            <div class="min-w-0 flex-1 text-sm">{{ $info }}</div>
        @endisset
        <div class="{{ isset($info) ? 'shrink-0' : 'flex-1' }}">{{ $slot }}</div>
    </div>
</div>
<div class="h-20 lg:hidden" aria-hidden="true"></div>
