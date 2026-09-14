<nav class="flex items-center gap-1.5 text-sm text-ink-3">
    <a href="{{ route('catalog.index') }}" class="hover:text-ink">Boutique</a>
    @if ($product->category)
        <span>/</span>
        <a href="{{ route('catalog.index', ['category' => $product->category]) }}" class="hover:text-ink">{{ $product->category }}</a>
    @endif
</nav>
