@props([
    // Checkout model (Phase 4) or a normalized array. Read defensively so this
    // component renders before the Checkout/Cart models are finalised. Expected:
    // lines [{title, variant_title, quantity, line_total, image_url}],
    // subtotal, discount_total, shipping_total, tax_total, total, currency.
    'checkout',
    'showDiscountInput' => true,
])

@php
    use App\Support\Storefront\PriceFormatter;

    $get = fn (string $key, $default = null) => is_array($checkout) ? ($checkout[$key] ?? $default) : data_get($checkout, $key, $default);
    $currency = $get('currency', app()->bound('current_store') ? app('current_store')->default_currency : 'USD');
    $lines = collect($get('lines', []));
    $money = fn ($amount) => $amount === null ? null : PriceFormatter::format((int) $amount, $currency);
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl bg-zinc-50 p-6 dark:bg-zinc-900']) }}>
    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Order summary') }}</h2>

    <ul role="list" class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-800">
        @forelse ($lines as $line)
            @php $get = fn ($k, $d = null) => is_array($line) ? ($line[$k] ?? $d) : data_get($line, $k, $d); @endphp
            <li class="flex items-center gap-3 py-3">
                <div class="size-12 shrink-0 overflow-hidden rounded-md bg-zinc-200 dark:bg-zinc-700">
                    @if ($get('image_url'))
                        <img src="{{ $get('image_url') }}" alt="" class="h-full w-full object-cover" />
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">
                        {{ $get('title') }} <span class="text-zinc-500">&times;{{ $get('quantity', 1) }}</span>
                    </p>
                    @if ($get('variant_title'))
                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $get('variant_title') }}</p>
                    @endif
                </div>
                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $money($get('line_total', 0)) }}</span>
            </li>
        @empty
            <li class="py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Your cart is empty.') }}</li>
        @endforelse
    </ul>

    @if ($showDiscountInput)
        <div class="mt-4 flex gap-2">
            <flux:input wire:model="discountCode" :placeholder="__('Discount code')" class="flex-1" />
            <flux:button type="button" variant="filled" wire:click="applyDiscount">{{ __('Apply') }}</flux:button>
        </div>
    @endif

    <dl class="mt-4 space-y-2 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-800" aria-live="polite">
        <div class="flex justify-between text-zinc-600 dark:text-zinc-300">
            <dt>{{ __('Subtotal') }}</dt>
            <dd>{{ $money($get('subtotal', 0)) }}</dd>
        </div>
        @if ($get('discount_total'))
            <div class="flex justify-between text-green-600 dark:text-green-400">
                <dt>{{ __('Discount') }}</dt>
                <dd>-{{ $money($get('discount_total')) }}</dd>
            </div>
        @endif
        <div class="flex justify-between text-zinc-600 dark:text-zinc-300">
            <dt>{{ __('Shipping') }}</dt>
            <dd>{{ $get('shipping_total') === null ? __('Calculated at next step') : $money($get('shipping_total')) }}</dd>
        </div>
        @if ($get('tax_total') !== null)
            <div class="flex justify-between text-zinc-600 dark:text-zinc-300">
                <dt>{{ __('Tax') }}</dt>
                <dd>{{ $money($get('tax_total')) }}</dd>
            </div>
        @endif
        <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold text-zinc-900 dark:border-zinc-800 dark:text-white">
            <dt>{{ __('Total') }}</dt>
            <dd>{{ $money($get('total', 0)) }}</dd>
        </div>
    </dl>
</div>
