@props(['items'])

<nav aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-1 text-sm">
        @foreach ($items as $item)
            <li wire:key="crumb-{{ $loop->index }}" class="flex items-center gap-1">
                @if (! $loop->first)
                    <span aria-hidden="true" class="text-zinc-400">/</span>
                @endif
                @if (isset($item['url']) && ! $loop->last)
                    <a href="{{ $item['url'] }}" class="text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ $item['label'] }}</a>
                @else
                    <span aria-current="page" class="font-medium">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
