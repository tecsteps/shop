@php
    $variant = $variant ?? 'default';
    $styles = [
        'sale' => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200',
        'sold-out' => 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-200',
        'new' => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
        'pending' => 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
        'paid' => 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
        'fulfilled' => 'bg-blue-100 text-blue-900 dark:bg-blue-950 dark:text-blue-200',
        'cancelled' => 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-200',
        'refunded' => 'bg-rose-100 text-rose-900 dark:bg-rose-950 dark:text-rose-200',
        'default' => 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-200',
    ];
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $styles[$variant] ?? $styles['default'] }}">{{ $text ?? 'Info' }}</span>
