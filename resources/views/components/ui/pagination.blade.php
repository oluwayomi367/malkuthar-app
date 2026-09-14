@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="mt-8 flex items-center justify-between border-t border-line pt-4 text-sm" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="text-ink-3">‹ Précédent</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="text-ink transition-colors hover:text-accent">‹ Précédent</a>
        @endif

        <span class="text-ink-3">Page {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="text-ink transition-colors hover:text-accent">Suivant ›</a>
        @else
            <span class="text-ink-3">Suivant ›</span>
        @endif
    </nav>
@endif
