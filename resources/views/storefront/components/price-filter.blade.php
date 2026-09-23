@php
    $filterIdPrefix = $idPrefix ?? 'storefront-price';
    $currencyCode = data_get($currentStore ?? null, 'currency', 'EUR');
    $currencySymbol = data_get($currentStore ?? null, 'currency_symbol', $currencyCode === 'EUR' ? '€' : $currencyCode);
@endphp
<div class="grid grid-cols-2 gap-2">
    <div>
        <label for="{{ $filterIdPrefix }}-min" class="sr-only">Minimum price ({{ $currencyCode }})</label>
        <div class="relative"><span aria-hidden="true" class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-stone-500">{{ $currencySymbol }}</span><input id="{{ $filterIdPrefix }}-min" type="number" inputmode="decimal" min="0" step="0.01" wire:model.live.debounce.500ms="minPrice" placeholder="0.00" class="storefront-input pl-10"></div>
    </div>
    <div>
        <label for="{{ $filterIdPrefix }}-max" class="sr-only">Maximum price ({{ $currencyCode }})</label>
        <div class="relative"><span aria-hidden="true" class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-stone-500">{{ $currencySymbol }}</span><input id="{{ $filterIdPrefix }}-max" type="number" inputmode="decimal" min="0" step="0.01" wire:model.live.debounce.500ms="maxPrice" placeholder="0.00" class="storefront-input pl-10"></div>
    </div>
</div>
