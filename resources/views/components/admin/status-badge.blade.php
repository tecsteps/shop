@props(['status', 'size' => 'sm'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;

    $color = match ($value) {
        'active', 'paid', 'fulfilled', 'captured', 'delivered', 'processed', 'published' => 'green',
        'partial', 'partially_refunded', 'refunded', 'scheduled' => 'yellow',
        'archived', 'cancelled', 'failed', 'voided', 'expired' => 'red',
        'shipped' => 'blue',
        default => 'zinc',
    };
@endphp

<flux:badge :color="$color" :size="$size" {{ $attributes }}>
    {{ \Illuminate\Support\Str::headline($value) }}
</flux:badge>
