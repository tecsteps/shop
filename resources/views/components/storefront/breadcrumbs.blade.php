@props(['items'])

<nav aria-label="Breadcrumb" {{ $attributes }}>
    <ol class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
        @foreach($items as $index => $item)
            @if($index > 0)
                <li aria-hidden="true" class="text-gray-400 dark:text-gray-600">/</li>
            @endif
            <li>
                @if($index === count($items) - 1)
                    <span aria-current="page" class="font-medium text-gray-900 dark:text-white">{{ $item['label'] }}</span>
                @else
                    <a href="{{ $item['url'] }}" class="hover:text-gray-900 dark:hover:text-white">{{ $item['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
