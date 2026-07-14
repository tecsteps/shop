@props([
    'items',
])

@php
    $items = collect($items)->values();

    $structuredItems = $items->map(function (mixed $item, int $index): array {
        $structuredItem = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => (string) data_get($item, 'label', ''),
        ];

        if (filled(data_get($item, 'url'))) {
            $structuredItem['item'] = (string) data_get($item, 'url');
        }

        return $structuredItem;
    })->all();

    $structuredData = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $structuredItems,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp

<nav {{ $attributes->class('text-sm') }} aria-label="{{ __('Breadcrumb') }}">
    <ol class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 text-zinc-500 dark:text-zinc-400">
        @foreach ($items as $item)
            @php
                $isCurrent = $loop->last;
                $label = (string) data_get($item, 'label', '');
                $url = data_get($item, 'url');
            @endphp

            <li class="flex min-w-0 items-center gap-2">
                @if (! $loop->first)
                    <svg aria-hidden="true" class="size-3.5 shrink-0 text-zinc-400 dark:text-zinc-600" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="m6 3 5 5-5 5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                @endif

                @if (! $isCurrent && filled($url))
                    <a href="{{ $url }}" wire:navigate class="rounded-sm underline-offset-4 transition hover:text-zinc-950 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--storefront-primary)] dark:hover:text-white">
                        {{ $label }}
                    </a>
                @else
                    <span class="storefront-line-clamp-1 font-medium text-zinc-800 dark:text-zinc-200" @if ($isCurrent) aria-current="page" @endif>
                        {{ $label }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

<script type="application/ld+json">{!! $structuredData !!}</script>
