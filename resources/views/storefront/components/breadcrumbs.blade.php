{{--
    Breadcrumb navigation trail with BreadcrumbList structured data (spec 04 §16).

    Props:
    - items: array (required) — list of ['label' => string, 'url' => string|null].
      The last item is the current page and needs no URL.
--}}
@props(['items'])

@php
    $items = array_values($items);
    $lastIndex = count($items) - 1;
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($items)->map(fn (array $item, int $index): array => array_filter([
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['label'],
            'item' => isset($item['url']) ? url($item['url']) : null,
        ]))->values()->all(),
    ];
@endphp

@if (count($items) > 1)
    <nav {{ $attributes->class('text-sm') }} aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
            @foreach ($items as $index => $item)
                <li class="flex items-center gap-2">
                    @if ($index > 0)
                        <span class="text-gray-400 dark:text-gray-600" aria-hidden="true">/</span>
                    @endif
                    @if ($index === $lastIndex || empty($item['url']))
                        <span class="text-gray-500 dark:text-gray-400" @if ($index === $lastIndex) aria-current="page" @endif>{{ $item['label'] }}</span>
                    @else
                        <a href="{{ $item['url'] }}" class="text-gray-500 hover:text-gray-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-gray-400 dark:hover:text-gray-200">{{ $item['label'] }}</a>
                    @endif
                </li>
            @endforeach
        </ol>
        <script type="application/ld+json">@json($jsonLd)</script>
    </nav>
@endif
