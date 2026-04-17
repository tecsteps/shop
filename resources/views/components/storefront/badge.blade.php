@props([
    'variant' => 'new',
])

@php
    $classes = match ($variant) {
        'sale' => 'bg-red-500 text-white',
        'sold-out' => 'bg-zinc-700 text-white dark:bg-zinc-600',
        'new' => 'bg-blue-500 text-white',
        'draft' => 'bg-yellow-500 text-zinc-900',
        default => 'bg-zinc-200 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-block px-2 py-0.5 text-xs font-semibold rounded {$classes}"]) }}>
    {{ $slot }}
</span>
