@props([
    'variant' => 'default',
])

@php
    $classes = match($variant) {
        'sale' => 'bg-red-500 text-white',
        'sold-out' => 'bg-gray-500 text-white',
        'success' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
        'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' . $classes]) }}>
    {{ $slot }}
</span>
