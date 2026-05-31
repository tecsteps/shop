@props([
    'paginator',
])

@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator */
    $elements = method_exists($paginator, 'links') ? $paginator->onEachSide(1)->linkCollection() : collect();
@endphp

@if ($paginator->hasPages())
    <nav aria-label="Pagination" {{ $attributes->merge(['class' => 'flex items-center justify-between gap-2']) }}>
        {{-- Mobile: previous / next + current page indicator. --}}
        <div class="flex w-full items-center justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="cursor-not-allowed rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-400 dark:border-zinc-700 dark:text-zinc-600">Previous</span>
            @else
                <button type="button" wire:click="previousPage" wire:loading.attr="disabled"
                        class="rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Previous</button>
            @endif

            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage" wire:loading.attr="disabled"
                        class="rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Next</button>
            @else
                <span class="cursor-not-allowed rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-400 dark:border-zinc-700 dark:text-zinc-600">Next</span>
            @endif
        </div>

        {{-- Desktop: numbered pagination with prev/next arrows. --}}
        <div class="hidden w-full items-center justify-center gap-1 sm:flex">
            @foreach ($elements as $element)
                @if (is_null($element['url']))
                    <span aria-hidden="true"
                          class="px-3 py-2 text-sm text-zinc-400 dark:text-zinc-600">{!! $element['label'] !!}</span>
                @else
                    @php $isActive = $element['active'] ?? false; @endphp
                    <a href="{{ $element['url'] }}" wire:navigate
                       @if ($isActive) aria-current="page" @endif
                       class="@if ($isActive) bg-blue-600 text-white @else text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800 @endif min-w-9 rounded-lg px-3 py-2 text-center text-sm font-medium transition">
                        {!! $element['label'] !!}
                    </a>
                @endif
            @endforeach
        </div>
    </nav>
@endif
