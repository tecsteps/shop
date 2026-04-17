@php
    $classes = match($status->value) {
        'paid' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'authorized' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        'partially_refunded' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        'refunded' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
        'voided' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
    };

    $label = str_replace('_', ' ', $status->value);
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $classes }}">
    {{ ucwords($label) }}
</span>
