@props(['items' => []])

<nav {{ $attributes->class('flex flex-wrap items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400') }} aria-label="Breadcrumb">
    <a href="{{ route('home') }}" class="hover:text-zinc-950 dark:hover:text-white" wire:navigate>Home</a>

    @foreach ($items as $item)
        <span aria-hidden="true">/</span>

        @if (($item['url'] ?? null) && ! $loop->last)
            <a href="{{ $item['url'] }}" class="hover:text-zinc-950 dark:hover:text-white" wire:navigate>{{ $item['label'] }}</a>
        @else
            <span class="text-zinc-700 dark:text-zinc-200">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
