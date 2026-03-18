@props(['items'])

<nav aria-label="Breadcrumb" {{ $attributes }}>
    <ol class="flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
        @foreach($items as $item)
            <li class="flex items-center gap-1.5">
                @if(!$loop->first)
                    <svg class="h-4 w-4 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                @endif
                @if(isset($item['url']) && !$loop->last)
                    <a href="{{ $item['url'] }}" class="hover:text-zinc-900 dark:hover:text-white">{{ $item['label'] }}</a>
                @else
                    <span class="text-zinc-900 dark:text-white">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
