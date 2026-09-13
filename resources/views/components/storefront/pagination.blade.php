@props(['paginator'])

@if ($paginator->hasPages())
    <nav aria-label="Pagination" class="mt-8 flex items-center justify-center gap-1">
        <button type="button" wire:click="previousPage" @disabled($paginator->onFirstPage()) class="rounded px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50 hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Previous page">&larr;</button>
        @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
            <button type="button" wire:click="gotoPage({{ $page }})" @if($page === $paginator->currentPage()) aria-current="page" @endif class="rounded px-3 py-2 text-sm {{ $page === $paginator->currentPage() ? 'bg-blue-600 text-white' : 'hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">{{ $page }}</button>
        @endforeach
        <button type="button" wire:click="nextPage" @disabled(! $paginator->hasMorePages()) class="rounded px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50 hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Next page">&rarr;</button>
    </nav>
    <p class="mt-2 text-center text-sm text-zinc-500 lg:hidden">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</p>
@endif
