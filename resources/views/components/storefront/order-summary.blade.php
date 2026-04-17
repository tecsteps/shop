@props(['subtotal' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 0, 'currency' => 'USD'])

<div {{ $attributes->merge(['class' => 'rounded-lg bg-gray-50 p-6 dark:bg-gray-800']) }}>
    <h2 class="text-lg font-medium">Order Summary</h2>
    <div class="mt-4 space-y-2">
        <div class="flex justify-between text-sm">
            <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
            <x-storefront.price :amount="$subtotal" :currency="$currency" />
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-gray-600 dark:text-gray-400">Shipping</span>
            @if ($shipping > 0)
                <x-storefront.price :amount="$shipping" :currency="$currency" />
            @else
                <span class="text-gray-500">Calculated at checkout</span>
            @endif
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-gray-600 dark:text-gray-400">Tax</span>
            <x-storefront.price :amount="$tax" :currency="$currency" />
        </div>
        <div class="border-t border-gray-200 pt-2 dark:border-gray-700">
            <div class="flex justify-between font-medium">
                <span>Total</span>
                <x-storefront.price :amount="$total" :currency="$currency" />
            </div>
        </div>
    </div>
    {{ $slot }}
</div>
