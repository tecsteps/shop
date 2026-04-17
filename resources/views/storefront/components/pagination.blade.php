{{-- Uses Laravel's default pagination, customizable via vendor:publish --}}
@if($paginator->hasPages())
    <nav aria-label="Pagination" class="flex items-center justify-center gap-1">
        {{-- Previous --}}
        @if($paginator->onFirstPage())
            <span class="rounded-md p-2 text-zinc-400 dark:text-zinc-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="rounded-md p-2 text-zinc-600 transition hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </a>
        @endif

        {{-- Pages --}}
        @foreach($elements as $element)
            @if(is_string($element))
                <span class="px-2 text-zinc-400">{{ $element }}</span>
            @endif

            @if(is_array($element))
                @foreach($element as $page => $url)
                    @if($page == $paginator->currentPage())
                        <span class="flex h-9 w-9 items-center justify-center rounded-md bg-zinc-900 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="flex h-9 w-9 items-center justify-center rounded-md text-sm text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="rounded-md p-2 text-zinc-600 transition hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
        @else
            <span class="rounded-md p-2 text-zinc-400 dark:text-zinc-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </span>
        @endif
    </nav>
@endif
