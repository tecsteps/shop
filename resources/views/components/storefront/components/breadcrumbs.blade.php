@props([
    'items' => [],
])

<nav aria-label="Breadcrumb" {{ $attributes }}>
    <ol class="flex flex-wrap items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
        @foreach($items as $index => $item)
            <li class="flex items-center gap-1.5">
                @if(!$loop->last)
                    <a href="{{ $item['url'] }}" class="hover:text-gray-900 dark:hover:text-white">
                        {{ $item['label'] }}
                    </a>
                    <svg class="h-4 w-4 flex-shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                @else
                    <span class="font-medium text-gray-900 dark:text-white" aria-current="page">
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
