@props([
    'paginator' => null,
])

@if($paginator && $paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-center gap-1">
        {{-- Previous --}}
        @if($paginator->onFirstPage())
            <span class="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-md text-gray-400 opacity-50">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </span>
        @else
            <button wire:click="previousPage" class="inline-flex h-10 w-10 items-center justify-center rounded-md text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
        @endif

        {{-- Page Numbers --}}
        @foreach($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            @if($page == $paginator->currentPage())
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-blue-600 text-sm font-medium text-white">
                    {{ $page }}
                </span>
            @else
                <button wire:click="gotoPage({{ $page }})" class="inline-flex h-10 w-10 items-center justify-center rounded-md text-sm text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800">
                    {{ $page }}
                </button>
            @endif
        @endforeach

        {{-- Next --}}
        @if(! $paginator->hasMorePages())
            <span class="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-md text-gray-400 opacity-50">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </span>
        @else
            <button wire:click="nextPage" class="inline-flex h-10 w-10 items-center justify-center rounded-md text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>
        @endif
    </nav>
@endif
