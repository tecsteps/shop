@props([
    'type' => 'default',
])

@php
    $classes = match($type) {
        'sale' => 'bg-red-600 text-white',
        'sold-out' => 'bg-zinc-800 text-white dark:bg-zinc-600',
        default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-block rounded-full px-2.5 py-0.5 text-xs font-medium {$classes}"]) }}>
    <span class="sr-only">{{ $type === 'sale' ? 'On sale' : ($type === 'sold-out' ? 'Sold out' : '') }}</span>
    {{ $slot }}
</span>
