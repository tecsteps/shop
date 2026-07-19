{{--
    Numbered pagination with previous/next arrows (spec 04 §4.6 + §16).

    Props:
    - paginator: LengthAwarePaginator (required)
--}}
@props(['paginator'])

@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();

    // Windowed page list with ellipsis markers.
    $pages = [];
    $window = 2;
    for ($page = 1; $page <= $last; $page++) {
        if ($page === 1 || $page === $last || abs($page - $current) <= $window) {
            $pages[] = $page;
        } elseif (end($pages) !== '…') {
            $pages[] = '…';
        }
    }

    $linkClasses = 'inline-flex min-w-9 items-center justify-center rounded-md px-3 py-2 text-sm font-medium focus:outline-hidden focus:ring-2 focus:ring-blue-500';
    $mutedClasses = 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800';
    $disabledClasses = 'cursor-not-allowed text-gray-400 opacity-60 dark:text-gray-600';
@endphp

@if ($paginator->hasPages())
    <nav {{ $attributes->class('flex items-center justify-between gap-4 sm:justify-center') }} aria-label="Pagination">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="{{ $linkClasses }} {{ $disabledClasses }}" aria-disabled="true">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                <span class="sr-only sm:not-sr-only sm:ml-1">Previous</span>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $linkClasses }} {{ $mutedClasses }}">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                <span class="sr-only sm:not-sr-only sm:ml-1">Previous</span>
            </a>
        @endif

        {{-- Mobile page indicator --}}
        <span class="text-sm text-gray-600 sm:hidden dark:text-gray-300" aria-current="page">Page {{ $current }} of {{ $last }}</span>

        {{-- Numbered pages --}}
        <div class="hidden items-center gap-1 sm:flex">
            @foreach ($pages as $page)
                @if ($page === '…')
                    <span class="{{ $linkClasses }} {{ $disabledClasses }}" aria-hidden="true">&hellip;</span>
                @elseif ($page === $current)
                    <span class="{{ $linkClasses }} bg-blue-600 text-white dark:bg-blue-500" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="{{ $linkClasses }} {{ $mutedClasses }}">{{ $page }}</a>
                @endif
            @endforeach
        </div>

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $linkClasses }} {{ $mutedClasses }}">
                <span class="sr-only sm:not-sr-only sm:mr-1">Next</span>
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </a>
        @else
            <span class="{{ $linkClasses }} {{ $disabledClasses }}" aria-disabled="true">
                <span class="sr-only sm:not-sr-only sm:mr-1">Next</span>
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </span>
        @endif
    </nav>
@endif
