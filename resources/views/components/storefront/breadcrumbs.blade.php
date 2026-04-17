@props([
    'items' => [],
])

<nav aria-label="Breadcrumb" {{ $attributes }}>
    <ol class="flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
        @foreach ($items as $index => $item)
            <li class="flex items-center gap-1.5">
                @if ($index > 0)
                    <span aria-hidden="true" class="text-zinc-300 dark:text-zinc-600">/</span>
                @endif

                @if (isset($item['url']) && $index < count($items) - 1)
                    <a href="{{ $item['url'] }}" class="hover:text-zinc-900 dark:hover:text-white transition-colors" wire:navigate>
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="text-zinc-900 dark:text-white font-medium" aria-current="page">
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
