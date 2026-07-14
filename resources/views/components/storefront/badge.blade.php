@props([
    'text',
    'variant' => 'default',
])

@php
    $variant = in_array($variant, ['sale', 'sold-out', 'new', 'default'], true)
        ? $variant
        : 'default';

    $variantClasses = [
        'sale' => 'bg-red-50 text-red-700 ring-red-600/10 dark:bg-red-950/60 dark:text-red-300 dark:ring-red-400/20',
        'sold-out' => 'bg-zinc-100 text-zinc-600 ring-zinc-500/10 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-white/10',
        'new' => 'bg-blue-50 text-blue-700 ring-blue-600/10 dark:bg-blue-950/60 dark:text-blue-300 dark:ring-blue-400/20',
        'default' => 'bg-zinc-100 text-zinc-700 ring-zinc-500/10 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-white/10',
    ];

    $accessibleLabels = [
        'sale' => __('On sale'),
        'sold-out' => __('Sold out'),
        'new' => __('New product'),
        'default' => (string) $text,
    ];
@endphp

<span
    {{ $attributes->except('aria-label')->class([
        'inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-medium leading-none ring-1 ring-inset',
        $variantClasses[$variant],
    ]) }}
    aria-label="{{ $attributes->get('aria-label', $accessibleLabels[$variant]) }}"
>
    {{ $text }}
</span>
