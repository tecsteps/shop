@props([
    'paginator',
    'scrollTo' => 'main',
])

@php
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $pageName = $paginator->getPageName();

    if ($lastPage <= 7) {
        $pages = range(1, max(1, $lastPage));
    } else {
        $pages = [1];
        $windowStart = max(2, $currentPage - 1);
        $windowEnd = min($lastPage - 1, $currentPage + 1);

        if ($windowStart > 2) {
            $pages[] = 'start-ellipsis';
        }

        foreach (range($windowStart, $windowEnd) as $page) {
            $pages[] = $page;
        }

        if ($windowEnd < $lastPage - 1) {
            $pages[] = 'end-ellipsis';
        }

        $pages[] = $lastPage;
    }

    $scrollExpression = $scrollTo === false
        ? ''
        : "document.querySelector(".Illuminate\Support\Js::from((string) $scrollTo).")?.scrollIntoView({ behavior: 'smooth', block: 'start' })";
@endphp

@if ($paginator->hasPages())
    <nav {{ $attributes->class('flex items-center justify-between gap-4') }} aria-label="{{ __('Pagination navigation') }}">
        <div class="flex w-full items-center justify-between gap-3 sm:hidden">
            <button
                type="button"
                class="storefront-pagination-button"
                wire:click="previousPage('{{ $pageName }}')"
                wire:loading.attr="disabled"
                @if ($scrollExpression !== '') x-on:click="{{ $scrollExpression }}" @endif
                @disabled($paginator->onFirstPage())
            >
                <svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"><path d="m12.5 15-5-5 5-5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                {{ __('Previous') }}
            </button>

            <span class="shrink-0 text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Page :current of :last', ['current' => $currentPage, 'last' => $lastPage]) }}
            </span>

            <button
                type="button"
                class="storefront-pagination-button"
                wire:click="nextPage('{{ $pageName }}')"
                wire:loading.attr="disabled"
                @if ($scrollExpression !== '') x-on:click="{{ $scrollExpression }}" @endif
                @disabled(! $paginator->hasMorePages())
            >
                {{ __('Next') }}
                <svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"><path d="m7.5 5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </button>
        </div>

        <p class="hidden text-sm text-zinc-600 sm:block dark:text-zinc-400">
            {{ __('Showing :first–:last of :total results', [
                'first' => $paginator->firstItem(),
                'last' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ]) }}
        </p>

        <div class="hidden items-center gap-1 sm:flex">
            <button
                type="button"
                class="storefront-pagination-button px-2.5"
                aria-label="{{ __('Previous page') }}"
                wire:click="previousPage('{{ $pageName }}')"
                wire:loading.attr="disabled"
                @if ($scrollExpression !== '') x-on:click="{{ $scrollExpression }}" @endif
                @disabled($paginator->onFirstPage())
            >
                <svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"><path d="m12.5 15-5-5 5-5" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </button>

            @foreach ($pages as $page)
                @if (is_string($page))
                    <span class="inline-flex size-10 items-center justify-center text-sm text-zinc-500" aria-hidden="true">…</span>
                @elseif ($page === $currentPage)
                    <span class="inline-flex size-10 items-center justify-center rounded-lg bg-[var(--storefront-primary)] text-sm font-semibold text-white shadow-sm" aria-current="page" aria-label="{{ __('Page :page, current page', ['page' => $page]) }}">
                        {{ $page }}
                    </span>
                @else
                    <button
                        type="button"
                        class="storefront-pagination-button size-10 px-0"
                        aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                        wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                        wire:loading.attr="disabled"
                        @if ($scrollExpression !== '') x-on:click="{{ $scrollExpression }}" @endif
                    >
                        {{ $page }}
                    </button>
                @endif
            @endforeach

            <button
                type="button"
                class="storefront-pagination-button px-2.5"
                aria-label="{{ __('Next page') }}"
                wire:click="nextPage('{{ $pageName }}')"
                wire:loading.attr="disabled"
                @if ($scrollExpression !== '') x-on:click="{{ $scrollExpression }}" @endif
                @disabled(! $paginator->hasMorePages())
            >
                <svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"><path d="m7.5 5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </button>
        </div>
    </nav>
@endif
