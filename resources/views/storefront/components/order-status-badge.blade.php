{{--
    Order status badge (spec 04 §10.4: pending yellow, paid green,
    fulfilled blue, cancelled gray, refunded red).

    Props:
    - status: string (required) — order status value (pending|paid|fulfilled|cancelled|refunded)
--}}
@props([
    'status',
])

@php
    $variantClasses = [
        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-300',
        'paid' => 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-400',
        'fulfilled' => 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-400',
        'cancelled' => 'bg-gray-200 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
        'refunded' => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400',
    ];
    $classes = $variantClasses[$status] ?? $variantClasses['pending'];
@endphp

<span {{ $attributes->class('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize '.$classes) }}>
    {{ str_replace('_', ' ', $status) }}
</span>
