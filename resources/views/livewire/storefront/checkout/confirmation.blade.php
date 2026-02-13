<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8 text-center">
    <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>

    <h1 class="mt-4 text-3xl font-bold text-zinc-900 dark:text-white">Order Confirmed</h1>
    <p class="mt-2 text-zinc-500 dark:text-zinc-400">Thank you for your order.</p>

    @if($checkout->totals_json)
        <div class="mt-8 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 text-left">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Order Summary</h2>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500">Subtotal</span>
                    <span>${{ number_format(($checkout->totals_json['subtotal'] ?? 0) / 100, 2) }}</span>
                </div>
                @if(($checkout->totals_json['discount'] ?? 0) > 0)
                    <div class="flex justify-between text-sm text-green-600">
                        <span>Discount</span>
                        <span>-${{ number_format($checkout->totals_json['discount'] / 100, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500">Shipping</span>
                    <span>${{ number_format(($checkout->totals_json['shipping'] ?? 0) / 100, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500">Tax</span>
                    <span>${{ number_format(($checkout->totals_json['tax_total'] ?? 0) / 100, 2) }}</span>
                </div>
                <div class="flex justify-between text-base font-semibold text-zinc-900 dark:text-white border-t border-zinc-200 dark:border-zinc-700 pt-2">
                    <span>Total</span>
                    <span>${{ number_format(($checkout->totals_json['total'] ?? 0) / 100, 2) }}</span>
                </div>
            </div>
        </div>
    @endif

    <a href="{{ route('storefront.home') }}"
       class="mt-8 inline-block rounded-md bg-zinc-900 dark:bg-white px-6 py-3 text-sm font-medium text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-colors">
        Continue Shopping
    </a>
</div>
