@props(['items' => []])

<nav {{ $attributes->merge(['class' => 'text-sm']) }} aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li>
            <a href="/" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Home</a>
        </li>
        @foreach ($items as $item)
            <li class="flex items-center space-x-2">
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                @if (!empty($item['url']))
                    <a href="{{ $item['url'] }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="text-gray-900 dark:text-gray-100">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
