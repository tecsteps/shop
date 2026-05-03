<div class="flex flex-wrap items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
    @foreach ($items as $item)
        @if (! $loop->first)
            <span>/</span>
        @endif

        @if ($item['url'])
            <a href="{{ $item['url'] }}" wire:navigate class="hover:text-zinc-950 dark:hover:text-white">{{ $item['label'] }}</a>
        @else
            <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $item['label'] }}</span>
        @endif
    @endforeach
</div>
