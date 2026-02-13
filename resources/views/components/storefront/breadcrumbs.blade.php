@props([
    'items' => [],
])

<nav aria-label="Breadcrumb" {{ $attributes->class(['text-sm text-zinc-500 dark:text-zinc-400']) }}>
    <ol class="flex items-center space-x-2" itemscope itemtype="https://schema.org/BreadcrumbList">
        @foreach($items as $index => $item)
            <li class="flex items-center" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                @if($index > 0)
                    <svg class="mr-2 h-4 w-4 flex-shrink-0 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                @endif

                @if(!$loop->last && !empty($item['url']))
                    <a href="{{ $item['url'] }}"
                       itemprop="item"
                       class="hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors">
                        <span itemprop="name">{{ $item['label'] }}</span>
                    </a>
                @else
                    <span itemprop="name" class="text-zinc-900 dark:text-white font-medium" aria-current="page">
                        {{ $item['label'] }}
                    </span>
                @endif
                <meta itemprop="position" content="{{ $index + 1 }}">
            </li>
        @endforeach
    </ol>
</nav>
