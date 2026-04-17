@php
    $formattedPrice = number_format($amount / 100, 2, '.', ',') . ' ' . ($currency ?? 'EUR');
    $hasCompare = isset($compareAtAmount) && $compareAtAmount && $compareAtAmount > $amount;
    $formattedCompare = $hasCompare ? number_format($compareAtAmount / 100, 2, '.', ',') . ' ' . ($currency ?? 'EUR') : null;
    $isCompact = isset($compact) && $compact;
@endphp

<div class="flex items-center gap-2">
    <span @class([
        'font-semibold',
        'text-sm' => $isCompact,
        'text-lg' => !$isCompact,
        'text-red-600 dark:text-red-400' => $hasCompare,
        'text-zinc-900 dark:text-white' => !$hasCompare,
    ])>
        {{ $formattedPrice }}
    </span>

    @if($hasCompare)
        <span @class([
            'text-zinc-500 line-through dark:text-zinc-400',
            'text-xs' => $isCompact,
            'text-sm' => !$isCompact,
        ])>
            {{ $formattedCompare }}
        </span>
        @if(!$isCompact)
            @include('storefront.components.badge', ['text' => 'Sale', 'variant' => 'sale'])
        @endif
    @endif
</div>
