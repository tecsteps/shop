@props([
    'items' => [],
])

@php
    $schemaItems = [];
    $position = 1;

    foreach ($items as $item) {
        $schemaItems[] = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $item['label'],
            ...(isset($item['url']) ? ['item' => url($item['url'])] : []),
        ];
        $position++;
    }
@endphp

<nav aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
        @foreach ($items as $item)
            <li class="flex items-center gap-x-1.5">
                @if (! $loop->last && isset($item['url']))
                    <a href="{{ $item['url'] }}" class="transition hover:text-zinc-900 hover:underline dark:hover:text-white">
                        {{ $item['label'] }}
                    </a>
                    <svg class="size-3.5 text-zinc-300 dark:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                @else
                    <span aria-current="page" class="font-medium text-zinc-900 dark:text-white">
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>

    @php($breadcrumbJson = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $schemaItems,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
    <script type="application/ld+json">{!! $breadcrumbJson !!}</script>
</nav>
