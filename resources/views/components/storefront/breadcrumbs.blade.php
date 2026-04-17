@props(['items'])

<nav aria-label="Breadcrumb">
    <ol class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400" itemscope itemtype="https://schema.org/BreadcrumbList">
        @foreach($items as $index => $item)
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                @if($item['url'] ?? null)
                    <a href="{{ $item['url'] }}" itemprop="item"
                       class="hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                        <span itemprop="name">{{ $item['label'] }}</span>
                    </a>
                @else
                    <span itemprop="name" class="font-medium text-gray-900 dark:text-white" aria-current="page">
                        {{ $item['label'] }}
                    </span>
                @endif
                <meta itemprop="position" content="{{ $index + 1 }}">
            </li>
            @if(!$loop->last)
                <li aria-hidden="true" class="text-gray-300 dark:text-gray-600">/</li>
            @endif
        @endforeach
    </ol>
</nav>
