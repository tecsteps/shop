@props([
    'text' => '',
    'variant' => 'default',
])

@php
    $classes = match ($variant) {
        'sale' => 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300',
        'sold-out' => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
        'new' => 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300',
        'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300',
        'success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
        'info' => 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300',
        'danger' => 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300',
        'muted' => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
        default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.$classes]) }}>
    {{ $text }}
</span>
