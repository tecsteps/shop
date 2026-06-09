@props([
    'paginator',
])

@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
    $window = \Illuminate\Pagination\UrlWindow::make($paginator);

    $elements = array_filter([
        $window['first'],
        is_array($window['slider']) ? '...' : null,
        $window['slider'],
        is_array($window['last']) ? '...' : null,
        $window['last'],
    ]);
@endphp

@if ($paginator->hasPages())
    {{-- Plain string label: __('Pagination') would resolve to the framework's pagination.php lang group. --}}
    <nav {{ $attributes->class('flex items-center justify-between gap-4') }} aria-label="Pagination">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-400 opacity-60 dark:border-zinc-700 dark:text-zinc-500" aria-disabled="true">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                {{ __('Previous') }}
            </span>
        @else
            <a
                href="{{ $paginator->previousPageUrl() }}"
                rel="prev"
                class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                {{ __('Previous') }}
            </a>
        @endif

        {{-- Mobile: page indicator --}}
        <span class="text-sm text-zinc-500 sm:hidden dark:text-zinc-400">
            {{ __('Page :current of :last', ['current' => $paginator->currentPage(), 'last' => $paginator->lastPage()]) }}
        </span>

        {{-- Desktop: numbered pages --}}
        <ul class="hidden items-center gap-1 sm:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li>
                        <span class="px-2 py-2 text-sm text-zinc-400 dark:text-zinc-500" aria-hidden="true">&hellip;</span>
                    </li>
                @else
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page === $paginator->currentPage())
                                <span
                                    class="inline-flex size-9 items-center justify-center rounded-lg bg-(--sf-primary,#1d4ed8) text-sm font-semibold text-white"
                                    aria-current="page"
                                >
                                    {{ $page }}
                                </span>
                            @else
                                <a
                                    href="{{ $url }}"
                                    class="inline-flex size-9 items-center justify-center rounded-lg text-sm font-medium text-zinc-600 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-400 dark:hover:bg-zinc-800"
                                    aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                >
                                    {{ $page }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
        </ul>

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a
                href="{{ $paginator->nextPageUrl() }}"
                rel="next"
                class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
            >
                {{ __('Next') }}
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </a>
        @else
            <span class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-400 opacity-60 dark:border-zinc-700 dark:text-zinc-500" aria-disabled="true">
                {{ __('Next') }}
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </span>
        @endif
    </nav>
@endif
