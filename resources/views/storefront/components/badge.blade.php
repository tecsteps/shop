@php
    $variant = $variant ?? 'default';
    $classes = match($variant) {
        'sale' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
        'sold-out' => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300',
        'new' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300',
        default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300',
    };
@endphp

<span class="absolute left-2 top-2 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $classes }}">
    {{ $text }}
</span>
