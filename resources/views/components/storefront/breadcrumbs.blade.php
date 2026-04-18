@props([
    'items' => [],
])

@if (! empty($items))
    <nav aria-label="Breadcrumb" {{ $attributes->class(['text-sm']) }}>
        <ol class="flex flex-wrap items-center gap-1.5 text-zinc-500 dark:text-zinc-400">
            @foreach ($items as $index => $item)
                @php
                    $label = data_get($item, 'label');
                    $url = data_get($item, 'url');
                    $isLast = $index === count($items) - 1;
                @endphp
                <li class="flex items-center gap-1.5">
                    @if ($url && ! $isLast)
                        <a href="{{ $url }}" class="hover:underline">{{ $label }}</a>
                    @else
                        <span @if ($isLast) aria-current="page" class="text-zinc-900 dark:text-zinc-100" @endif>{{ $label }}</span>
                    @endif
                    @unless ($isLast)
                        <flux:icon name="chevron-right" class="size-3.5 text-zinc-400" />
                    @endunless
                </li>
            @endforeach
        </ol>
    </nav>
@endif
