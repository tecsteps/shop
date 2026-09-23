@php($items = $items ?? [])
<nav aria-label="Breadcrumb" class="text-sm text-stone-500 dark:text-stone-400">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
        @foreach ($items as $index => $item)
            @php($label = data_get($item, 'label', 'Page'))
            <li class="inline-flex items-center gap-2">
                @if ($index > 0)
                    <span aria-hidden="true" class="text-stone-300 dark:text-stone-600">/</span>
                @endif
                @if (data_get($item, 'url') && $index !== array_key_last($items))
                    <a href="{{ data_get($item, 'url') }}" class="rounded-sm underline-offset-4 hover:text-stone-950 hover:underline focus-visible:outline-2 focus-visible:outline-blue-700 dark:hover:text-white">{{ $label }}</a>
                @else
                    <span aria-current="page" class="font-medium text-stone-800 dark:text-stone-200">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
    @if (!empty($items))
        <script type="application/ld+json">{!! json_encode([chr(64).'context' => 'https://schema.org', chr(64).'type' => 'BreadcrumbList', 'itemListElement' => collect($items)->values()->map(fn ($item, $index) => [chr(64).'type' => 'ListItem', 'position' => $index + 1, 'name' => data_get($item, 'label'), 'item' => data_get($item, 'url') ? url(data_get($item, 'url')) : url()->current()])->all()], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @endif
</nav>
