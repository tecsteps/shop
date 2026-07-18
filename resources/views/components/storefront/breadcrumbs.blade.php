@props([
    'items' => [],
])

<nav aria-label="Breadcrumb" {{ $attributes->class(['text-sm']) }}>
    <ol class="flex flex-wrap items-center gap-1 text-zinc-500 dark:text-zinc-400" itemscope itemtype="https://schema.org/BreadcrumbList">
        @foreach ($items as $index => $item)
            <li class="flex items-center gap-1" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                @if (! empty($item['url']))
                    <a href="{{ $item['url'] }}" wire:navigate itemprop="item" class="hover:text-zinc-700 hover:underline dark:hover:text-zinc-200">
                        <span itemprop="name">{{ $item['label'] }}</span>
                    </a>
                @else
                    <span itemprop="name" aria-current="page" class="font-medium text-zinc-700 dark:text-zinc-200">
                        {{ $item['label'] }}
                    </span>
                @endif
                <meta itemprop="position" content="{{ $index + 1 }}" />

                @if (! $loop->last)
                    <span aria-hidden="true">/</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
