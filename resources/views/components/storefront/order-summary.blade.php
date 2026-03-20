@props(['checkout', 'showDiscountInput' => true])

@php
    $currency = app()->bound('current_store') ? app('current_store')->default_currency : 'EUR';
@endphp

<div {{ $attributes->class(['rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-700 dark:bg-gray-800/50']) }}>
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Order Summary</h2>

    {{-- Line items --}}
    <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
        {{-- Items will be rendered from checkout data in Phase 4/5 --}}
    </div>

    {{-- Discount code --}}
    @if($showDiscountInput)
        <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
            <div class="flex gap-2">
                <input type="text"
                       placeholder="Discount code"
                       class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <button type="button"
                        class="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                    Apply
                </button>
            </div>
        </div>
    @endif

    {{-- Totals --}}
    <div class="mt-4 space-y-2 border-t border-gray-200 pt-4 dark:border-gray-700">
        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
            <span>Subtotal</span>
            <span>0.00 {{ $currency }}</span>
        </div>
        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
            <span>Shipping</span>
            <span>Calculated at next step</span>
        </div>
        <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-semibold text-gray-900 dark:border-gray-700 dark:text-white">
            <span>Total</span>
            <span>0.00 {{ $currency }}</span>
        </div>
    </div>
</div>
