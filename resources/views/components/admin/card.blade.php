@props([
    'as' => 'section',
    'title' => null,
    'description' => null,
    'headingLevel' => 'h2',
    'padding' => true,
])

@php
    $tag = in_array(strtolower((string) $as), ['div', 'section', 'article', 'aside'], true)
        ? strtolower((string) $as)
        : 'section';
    $headingLevel = in_array(strtolower((string) $headingLevel), ['h2', 'h3', 'h4'], true)
        ? strtolower((string) $headingLevel)
        : 'h2';
@endphp

<{{ $tag }} {{ $attributes->class(['admin-card', 'p-6' => $padding]) }}>
    @if (filled($title) || filled($description) || isset($actions))
        <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                @if (filled($title))
                    <{{ $headingLevel }} class="text-base font-semibold text-zinc-950 dark:text-white">{{ $title }}</{{ $headingLevel }}>
                @endif

                @if (filled($description))
                    <p class="mt-1 text-sm leading-6 text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['mt-5' => filled($title) || filled($description) || isset($actions)])>
        {{ $slot }}
    </div>
</{{ $tag }}>
