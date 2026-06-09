{{--
    Status badge for orders. Accepts an OrderStatus, FinancialStatus,
    FulfillmentStatus, or FulfillmentShipmentStatus enum (or its string
    value). Colors per spec 04: pending yellow, paid green, fulfilled blue,
    cancelled gray, refunded red.
--}}
@props([
    'status',
])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;

    $classes = match ($value) {
        'pending', 'authorized', 'unfulfilled' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/15 dark:text-yellow-400',
        'paid', 'delivered' => 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-400',
        'fulfilled', 'partial', 'shipped' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400',
        'cancelled', 'voided' => 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300',
        'refunded', 'partially_refunded' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
        default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
    };

    $label = __(\Illuminate\Support\Str::of($value)->replace('_', ' ')->title()->value());
@endphp

<span {{ $attributes->class("inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {$classes}") }}>
    {{ $label }}
</span>
