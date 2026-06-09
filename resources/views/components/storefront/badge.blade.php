@props([
    'text',
    'variant' => 'default',
])

@php
    $variantClasses = match ($variant) {
        'sale' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
        'sold-out' => 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300',
        'new' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400',
        default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
    };
@endphp

<span {{ $attributes->class("inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {$variantClasses}") }}>
    {{ $text }}
</span>
