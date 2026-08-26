@props([
    'paginator' => null,
])

@php
    if (! $paginator || ! $paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
        return;
    }

    $current = $paginator->currentPage();
    $last = $paginator->lastPage();

    $start = max(1, $current - 2);
    $end = min($last, $current + 2);
    $pages = collect(range($start, $end))->all();

    $pageButton = 'inline-flex h-10 min-w-10 items-center justify-center rounded-lg px-3 text-sm font-medium transition';
    $muted = 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800';
    $active = 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900';
    $disabled = 'cursor-not-allowed opacity-40';
@endphp

@if ($paginator->hasPages())
    <nav aria-label="Pagination" class="mt-10">
        {{-- Desktop: numbered buttons --}}
        <div class="hidden items-center justify-center gap-1.5 sm:flex">
            <button
                type="button"
                wire:click="setPage({{ max(1, $current - 1) }})"
                @disabled($current <= 1)
                aria-label="Previous page"
                class="{{ $pageButton }} {{ $current <= 1 ? $disabled : $muted }}"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 18-6-6 6-6" />
                </svg>
            </button>

            @if ($start > 1)
                <button type="button" wire:click="setPage(1)" class="{{ $pageButton }} {{ $muted }}">1</button>
                @if ($start > 2)
                    <span class="px-1 text-zinc-400 dark:text-zinc-500" aria-hidden="true">&hellip;</span>
                @endif
            @endif

            @foreach ($pages as $page)
                <button
                    type="button"
                    wire:click="setPage({{ $page }})"
                    aria-current="{{ $page === $current ? 'page' : 'false' }}"
                    class="{{ $pageButton }} {{ $page === $current ? $active : $muted }}"
                >
                    {{ $page }}
                </button>
            @endforeach

            @if ($end < $last)
                @if ($end < $last - 1)
                    <span class="px-1 text-zinc-400 dark:text-zinc-500" aria-hidden="true">&hellip;</span>
                @endif
                <button type="button" wire:click="setPage({{ $last }})" class="{{ $pageButton }} {{ $muted }}">{{ $last }}</button>
            @endif

            <button
                type="button"
                wire:click="setPage({{ min($last, $current + 1) }})"
                @disabled($current >= $last)
                aria-label="Next page"
                class="{{ $pageButton }} {{ $current >= $last ? $disabled : $muted }}"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </button>
        </div>

        {{-- Mobile: previous / next only --}}
        <div class="flex items-center justify-between gap-4 sm:hidden">
            <button
                type="button"
                wire:click="setPage({{ max(1, $current - 1) }})"
                @disabled($current <= 1)
                class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-4 py-2.5 text-sm font-medium text-zinc-700 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-200"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 18-6-6 6-6" />
                </svg>
                Previous
            </button>

            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Page <span class="font-medium text-zinc-900 dark:text-white">{{ $current }}</span> of {{ $last }}
            </p>

            <button
                type="button"
                wire:click="setPage({{ min($last, $current + 1) }})"
                @disabled($current >= $last)
                class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-4 py-2.5 text-sm font-medium text-zinc-700 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-200"
            >
                Next
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </button>
        </div>
    </nav>
@endif
