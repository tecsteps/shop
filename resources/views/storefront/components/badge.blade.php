{{--
    Styled badge/tag (spec 04 §16).

    Props:
    - text: string (required) — badge text
    - variant: string — sale | sold-out | new | default
--}}
@props([
    'text',
    'variant' => 'default',
])

@php
    $variantClasses = [
        'sale' => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400',
        'sold-out' => 'bg-gray-200 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
        'new' => 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-400',
        'default' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    ];
    $classes = $variantClasses[$variant] ?? $variantClasses['default'];
@endphp

<span {{ $attributes->class('inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium '.$classes) }}>
    <span class="sr-only">{{ $variant === 'sale' ? 'On sale: ' : '' }}</span>{{ $text }}
</span>
