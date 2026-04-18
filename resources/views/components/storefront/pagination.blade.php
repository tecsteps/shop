@props([
    'paginator' => null,
])

@if ($paginator && $paginator->hasPages())
    <nav aria-label="Pagination" {{ $attributes->class(['flex items-center justify-between border-t border-zinc-200 px-2 py-4 text-sm dark:border-zinc-800']) }}>
        <div class="text-zinc-500 dark:text-zinc-400">
            Showing {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </div>
        <div class="flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <span class="rounded-md border border-zinc-200 px-3 py-1.5 text-zinc-400 dark:border-zinc-800">Previous</span>
            @else
                <button type="button"
                        wire:click="previousPage"
                        class="rounded-md border border-zinc-200 px-3 py-1.5 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800">
                    Previous
                </button>
            @endif

            @if ($paginator->hasMorePages())
                <button type="button"
                        wire:click="nextPage"
                        class="rounded-md border border-zinc-200 px-3 py-1.5 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800">
                    Next
                </button>
            @else
                <span class="rounded-md border border-zinc-200 px-3 py-1.5 text-zinc-400 dark:border-zinc-800">Next</span>
            @endif
        </div>
    </nav>
@endif
