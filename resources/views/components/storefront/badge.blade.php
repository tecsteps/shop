@props(['text', 'variant' => 'default'])

@php
    $styles = [
        'sale' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        'sold-out' => 'bg-zinc-200 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
        'new' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'default' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.($styles[$variant] ?? $styles['default'])]) }}>{{ $text }}</span>
