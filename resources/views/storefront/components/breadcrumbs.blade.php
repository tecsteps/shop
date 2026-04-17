@php $items = $items ?? []; @endphp

<nav aria-label="Breadcrumb">
    <ol class="flex items-center gap-1 text-sm text-zinc-600 dark:text-zinc-400">
        @foreach($items as $i => $item)
            <li class="flex items-center gap-1">
                @if(!$loop->last && isset($item['url']))
                    <a href="{{ $item['url'] }}" class="transition hover:text-zinc-900 dark:hover:text-white">
                        {{ $item['label'] }}
                    </a>
                    <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                @else
                    <span class="font-medium text-zinc-900 dark:text-white" aria-current="page">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
