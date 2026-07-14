@props([
    'amount',
    'currency' => 'USD',
    'compareAtAmount' => null,
    'showSaleBadge' => true,
])

@php
    $amount = (int) $amount;
    $currency = strtoupper((string) $currency);
    $currency = preg_match('/^[A-Z]{3}$/', $currency) === 1 ? $currency : 'USD';
    $compareAtAmount = filled($compareAtAmount) ? (int) $compareAtAmount : null;
    $isOnSale = $compareAtAmount !== null && $compareAtAmount > $amount;

    $formatMoney = static function (int $minorUnits, string $currencyCode): string {
        $sign = $minorUnits < 0 ? '-' : '';
        $absoluteAmount = abs($minorUnits);

        return $sign.number_format($absoluteAmount / 100, 2, '.', ',').' '.$currencyCode;
    };

    $formattedAmount = $formatMoney($amount, $currency);
    $formattedCompareAtAmount = $compareAtAmount !== null
        ? $formatMoney($compareAtAmount, $currency)
        : null;
    $accessibleLabel = $attributes->get(
        'aria-label',
        $isOnSale ? __('Sale price :sale, regular price :regular', ['sale' => $formattedAmount, 'regular' => $formattedCompareAtAmount]) : null,
    );
@endphp

<span
    {{ $attributes->except('aria-label')->class('inline-flex flex-wrap items-baseline gap-x-2 gap-y-1 tabular-nums') }}
    @if (filled($accessibleLabel)) aria-label="{{ $accessibleLabel }}" @endif
>
    <span class="font-semibold text-zinc-950 dark:text-white">
        {{ $formattedAmount }}
    </span>

    @if ($isOnSale)
        <span class="text-sm text-zinc-500 line-through decoration-1 dark:text-zinc-400" aria-hidden="true">
            {{ $formattedCompareAtAmount }}
        </span>

        @if ($showSaleBadge)
            <x-storefront.badge :text="__('Sale')" variant="sale" />
        @endif
    @endif
</span>
