@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="mt-8 flex items-center justify-between gap-3 border-t border-line pt-4 text-sm" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="inline-flex min-h-11 items-center gap-1.5 px-1 text-ink-3" aria-disabled="true">
                <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M10 3.5L5.5 8l4.5 4.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Précédent
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex min-h-11 items-center gap-1.5 px-1 font-semibold text-ink transition-colors hover:text-accent-2">
                <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M10 3.5L5.5 8l4.5 4.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Précédent
            </a>
        @endif

        <span class="text-ink-3">Page {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex min-h-11 items-center gap-1.5 px-1 font-semibold text-ink transition-colors hover:text-accent-2">
                Suivant
                <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M6 3.5L10.5 8 6 12.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        @else
            <span class="inline-flex min-h-11 items-center gap-1.5 px-1 text-ink-3" aria-disabled="true">
                Suivant
                <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M6 3.5L10.5 8 6 12.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
        @endif
    </nav>
@endif
