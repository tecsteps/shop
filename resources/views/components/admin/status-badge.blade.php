@props([
    'status',
    'label' => null,
    'variant' => null,
    'showDot' => true,
])

@php
    $status = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $normalizedStatus = strtolower(str_replace([' ', '-'], '_', $status));
    $variant ??= match ($normalizedStatus) {
        'active', 'paid', 'fulfilled', 'published', 'completed', 'enabled', 'ready', 'success', 'in_stock', 'verified', 'installed', 'connected' => 'success',
        'pending', 'draft', 'processing', 'unfulfilled', 'scheduled', 'partially_refunded', 'partial', 'low_stock', 'unverified' => 'warning',
        'cancelled', 'canceled', 'failed', 'expired', 'disabled', 'refunded', 'archived', 'voided', 'out_of_stock' => 'danger',
        'authorized', 'shipping_selected', 'payment_selected', 'started', 'addressed', 'available', 'backorder' => 'info',
        default => 'neutral',
    };
    $variant = in_array($variant, ['success', 'warning', 'danger', 'info', 'neutral'], true) ? $variant : 'neutral';
    $label ??= \Illuminate\Support\Str::headline($status);

    $variantClasses = [
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-400/20',
        'warning' => 'bg-amber-50 text-amber-800 ring-amber-600/15 dark:bg-amber-950/60 dark:text-amber-300 dark:ring-amber-400/20',
        'danger' => 'bg-red-50 text-red-700 ring-red-600/15 dark:bg-red-950/60 dark:text-red-300 dark:ring-red-400/20',
        'info' => 'bg-blue-50 text-blue-700 ring-blue-600/15 dark:bg-blue-950/60 dark:text-blue-300 dark:ring-blue-400/20',
        'neutral' => 'bg-zinc-100 text-zinc-700 ring-zinc-500/15 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-white/10',
    ];

    $dotClasses = [
        'success' => 'bg-emerald-500',
        'warning' => 'bg-amber-500',
        'danger' => 'bg-red-500',
        'info' => 'bg-blue-500',
        'neutral' => 'bg-zinc-400',
    ];
@endphp

<span {{ $attributes->class(['inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium leading-none ring-1 ring-inset', $variantClasses[$variant]]) }}>
    @if ($showDot)
        <span class="size-1.5 rounded-full {{ $dotClasses[$variant] }}" aria-hidden="true"></span>
    @endif
    {{ $label }}
</span>
