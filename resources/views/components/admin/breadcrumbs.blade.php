@props([
    'items' => [],
    'includeHome' => true,
])

@php
    $items = collect($items)->values();
    $firstLabel = strtolower((string) data_get($items->first(), 'label', ''));

    if ($includeHome && $firstLabel !== 'home') {
        $items->prepend(['label' => __('Home'), 'url' => url('/admin')]);
    }
@endphp

<nav {{ $attributes->class('text-sm') }} aria-label="{{ __('Breadcrumb') }}">
    <ol class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 text-zinc-500 dark:text-zinc-400">
        @foreach ($items as $item)
            @php
                $isCurrent = $loop->last;
                $label = (string) data_get($item, 'label', '');
                $url = data_get($item, 'url');
            @endphp

            <li class="flex min-w-0 items-center gap-2">
                @if (! $loop->first)
                    <svg aria-hidden="true" class="size-3.5 shrink-0 text-zinc-400 dark:text-zinc-600" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="m6 3 5 5-5 5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                @endif

                @if (! $isCurrent && filled($url))
                    <a href="{{ $url }}" wire:navigate class="rounded-sm font-medium underline-offset-4 transition hover:text-zinc-950 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:hover:text-white">
                        {{ $label }}
                    </a>
                @else
                    <span class="admin-line-clamp-1 font-medium text-zinc-800 dark:text-zinc-200" @if ($isCurrent) aria-current="page" @endif>{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
