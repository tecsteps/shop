@props(['text', 'variant' => 'default'])

@php
    $classes = match($variant) {
        'sale' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        'sold-out' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
        'new' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {$classes}"]) }}>
    {{ $text }}
</span>
