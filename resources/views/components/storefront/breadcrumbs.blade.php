@props(['items'])

<nav aria-label="Breadcrumb" class="mb-4">
    <ol class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
        @foreach($items as $index => $item)
            @if($index > 0)
                <li aria-hidden="true" class="text-gray-300 dark:text-gray-600">/</li>
            @endif
            <li>
                @if($loop->last)
                    <span class="text-gray-900 dark:text-white font-medium" aria-current="page">{{ $item['label'] }}</span>
                @else
                    <a href="{{ $item['url'] }}" class="hover:text-gray-900 dark:hover:text-white transition-colors">{{ $item['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
