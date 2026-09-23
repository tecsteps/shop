@if (isset($paginator) && method_exists($paginator, 'hasPages') && $paginator->hasPages())
    <nav aria-label="Pagination" class="mt-10 flex items-center justify-between gap-4 border-t border-stone-200 pt-5 dark:border-stone-800">
        <a href="{{ $paginator->onFirstPage() ? '#' : $paginator->previousPageUrl() }}" @if ($paginator->onFirstPage()) aria-disabled="true" tabindex="-1" @endif class="inline-flex min-h-11 items-center gap-2 rounded-full px-3 text-sm font-medium text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-blue-700 aria-disabled:pointer-events-none aria-disabled:opacity-40 dark:text-stone-200 dark:hover:bg-stone-800">
            @include('storefront.components.icon', ['name' => 'chevron-left', 'class' => 'size-4']) <span class="hidden sm:inline">Previous</span>
        </a>
        <span class="text-sm text-stone-600 dark:text-stone-300 sm:hidden">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        <div class="hidden items-center gap-1 sm:flex">
            @foreach ($paginator->elements() as $element)
                @if (is_string($element))
                    <span class="px-2 text-sm text-stone-500" aria-hidden="true">…</span>
                @else
                    @foreach ($element as $page => $url)
                        <a href="{{ $url }}" @if ($page === $paginator->currentPage()) aria-current="page" @endif class="inline-flex size-10 items-center justify-center rounded-full text-sm font-medium {{ $page === $paginator->currentPage() ? 'bg-blue-700 text-white dark:bg-blue-400 dark:text-stone-950' : 'text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800' }} focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700">{{ $page }}</a>
                    @endforeach
                @endif
            @endforeach
        </div>
        <a href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#' }}" @if (!$paginator->hasMorePages()) aria-disabled="true" tabindex="-1" @endif class="inline-flex min-h-11 items-center gap-2 rounded-full px-3 text-sm font-medium text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-blue-700 aria-disabled:pointer-events-none aria-disabled:opacity-40 dark:text-stone-200 dark:hover:bg-stone-800">
            <span class="hidden sm:inline">Next</span> @include('storefront.components.icon', ['name' => 'chevron-right', 'class' => 'size-4'])
        </a>
    </nav>
@endif
