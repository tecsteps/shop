@props([
    'paginator',
])

@if($paginator->hasPages())
    <nav aria-label="Pagination" {{ $attributes->class(['flex items-center justify-center']) }}>
        <div class="flex items-center space-x-1">
            {{-- Previous --}}
            @if($paginator->onFirstPage())
                <span class="inline-flex items-center justify-center rounded-md p-2 text-zinc-300 dark:text-zinc-600 cursor-not-allowed" aria-disabled="true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </span>
            @else
                <button wire:click="previousPage" class="inline-flex items-center justify-center rounded-md p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors" aria-label="Previous page">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </button>
            @endif

            {{-- Page Numbers (hidden on mobile) --}}
            <div class="hidden sm:flex items-center space-x-1">
                @foreach($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                    @if($page == $paginator->currentPage())
                        <span class="inline-flex items-center justify-center rounded-md h-10 min-w-[2.5rem] px-3 text-sm font-medium bg-blue-600 text-white" aria-current="page">
                            {{ $page }}
                        </span>
                    @else
                        <button wire:click="gotoPage({{ $page }})" class="inline-flex items-center justify-center rounded-md h-10 min-w-[2.5rem] px-3 text-sm font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 transition-colors">
                            {{ $page }}
                        </button>
                    @endif
                @endforeach
            </div>

            {{-- Mobile: Page indicator --}}
            <div class="sm:hidden px-3 text-sm text-zinc-500 dark:text-zinc-400">
                Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
            </div>

            {{-- Next --}}
            @if(!$paginator->hasMorePages())
                <span class="inline-flex items-center justify-center rounded-md p-2 text-zinc-300 dark:text-zinc-600 cursor-not-allowed" aria-disabled="true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </span>
            @else
                <button wire:click="nextPage" class="inline-flex items-center justify-center rounded-md p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors" aria-label="Next page">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
            @endif
        </div>
    </nav>
@endif
