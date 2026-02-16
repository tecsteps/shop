@props(['paginator'])

@if($paginator->hasPages())
    <nav aria-label="Pagination" class="flex items-center justify-center gap-1">
        {{-- Previous --}}
        @if($paginator->onFirstPage())
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-400 dark:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </a>
        @endif

        {{-- Pages --}}
        @foreach($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            @if($page == $paginator->currentPage())
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-sm font-medium text-white">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800">{{ $page }}</a>
            @endif
        @endforeach

        {{-- Next --}}
        @if(!$paginator->hasMorePages())
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-400 dark:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </span>
        @else
            <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </a>
        @endif
    </nav>
@endif
