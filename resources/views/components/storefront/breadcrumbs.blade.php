@props([
    'items' => [],
])

<nav aria-label="Breadcrumb" class="mb-4">
    <ol class="flex flex-wrap items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
        <li>
            <a href="/" class="transition-colors hover:text-gray-900 dark:hover:text-white">Home</a>
        </li>
        @foreach($items as $item)
            <li class="flex items-center gap-1.5">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
                @if(! empty($item['url']) && ! $loop->last)
                    <a href="{{ $item['url'] }}" class="transition-colors hover:text-gray-900 dark:hover:text-white">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="text-gray-900 dark:text-white" aria-current="page">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
