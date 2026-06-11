@props([
    'items' => [],
])

@if ($items !== [])
    <nav {{ $attributes }} aria-label="{{ __('Breadcrumb') }}">
        <ol class="flex flex-wrap items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
            @foreach ($items as $item)
                <li class="flex items-center gap-1.5">
                    @if (! $loop->last && filled($item['url'] ?? null))
                        <a
                            href="{{ $item['url'] }}"
                            class="rounded transition hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:hover:text-white"
                        >
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span aria-current="page" class="font-medium text-zinc-900 dark:text-white">{{ $item['label'] }}</span>
                    @endif
                    @unless ($loop->last)
                        <svg class="size-3.5 shrink-0 text-zinc-400 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    @endunless
                </li>
            @endforeach
        </ol>
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => collect($items)->values()->map(fn (array $item, int $index): array => array_filter([
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['label'],
                    'item' => filled($item['url'] ?? null) ? url($item['url']) : null,
                ]))->all(),
            ], JSON_UNESCAPED_SLASHES) !!}
        </script>
    </nav>
@endif
