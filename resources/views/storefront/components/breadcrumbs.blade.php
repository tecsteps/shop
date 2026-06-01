@props([
    // Array of ['label' => string, 'url' => ?string]. The last item is the
    // current page and should omit its url.
    'items' => [],
])

@php
    $items = collect($items)->values();
    $position = 0;
@endphp

<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'text-sm']) }}>
    <ol class="flex flex-wrap items-center gap-1.5 text-zinc-500 dark:text-zinc-400"
        itemscope itemtype="https://schema.org/BreadcrumbList">
        @foreach ($items as $item)
            @php $position++; @endphp
            <li class="flex items-center gap-1.5"
                itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                @if (! empty($item['url']) && ! $loop->last)
                    <a href="{{ $item['url'] }}"
                       class="transition hover:text-zinc-900 dark:hover:text-white"
                       itemprop="item">
                        <span itemprop="name">{{ $item['label'] }}</span>
                    </a>
                @else
                    <span class="font-medium text-zinc-900 dark:text-white"
                          itemprop="name"
                          @if ($loop->last) aria-current="page" @endif>
                        {{ $item['label'] }}
                    </span>
                @endif
                <meta itemprop="position" content="{{ $position }}" />

                @unless ($loop->last)
                    <span class="text-zinc-300 dark:text-zinc-600" aria-hidden="true">/</span>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
