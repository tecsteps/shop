@props([
    'text' => '',
    'variant' => 'default',
])

@php
    $classes = match($variant) {
        'sale' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        'sold-out' => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
        'new' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', $classes]) }}>
    {{ $text }}
</span>
