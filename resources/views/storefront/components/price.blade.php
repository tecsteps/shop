@php
    $amount = (int) ($amount ?? 0);
    $currency = $currency ?? data_get(isset($currentStore) ? $currentStore : null, 'currency', 'EUR');
    $compareAtAmount = isset($compareAtAmount) ? (int) $compareAtAmount : null;
    $isSale = $compareAtAmount !== null && $compareAtAmount > $amount;
    $formatAmount = static fn (int $value): string => number_format(abs($value) / 100, 2, '.', ',');
@endphp
<span class="inline-flex flex-wrap items-baseline gap-x-2 gap-y-1" {{ !empty($live) ? 'aria-live=polite' : '' }}>
    <span class="font-semibold text-stone-950 dark:text-white">{{ $amount < 0 ? '-' : '' }}{{ $formatAmount($amount) }} {{ $currency }}</span>
    @if ($isSale)
        <span class="text-sm text-stone-500 line-through dark:text-stone-400">{{ $formatAmount($compareAtAmount) }} {{ $currency }}</span>
        <span class="sr-only">On sale</span>
    @endif
</span>
