<div>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="text-center">
            <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h1 class="mt-4 text-3xl font-bold text-zinc-900 dark:text-white">Order Confirmed</h1>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Thank you for your purchase!</p>
        </div>

        <div class="mt-8 rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Order Details</h2>
            <div class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                    <span>Email</span>
                    <span>{{ $checkout->email }}</span>
                </div>
                <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                    <span>Subtotal</span>
                    <span>${{ number_format(($totals['subtotal'] ?? 0) / 100, 2) }}</span>
                </div>
                @if(($totals['discount'] ?? 0) > 0)
                    <div class="flex justify-between text-green-600">
                        <span>Discount</span>
                        <span>-${{ number_format(($totals['discount'] ?? 0) / 100, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                    <span>Shipping</span>
                    <span>${{ number_format(($totals['shipping'] ?? 0) / 100, 2) }}</span>
                </div>
                <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                    <span>Tax</span>
                    <span>${{ number_format(($totals['tax_total'] ?? 0) / 100, 2) }}</span>
                </div>
                <div class="flex justify-between border-t border-zinc-200 pt-2 font-semibold text-zinc-900 dark:border-zinc-700 dark:text-white">
                    <span>Total</span>
                    <span>${{ number_format(($totals['total'] ?? 0) / 100, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="/" class="inline-block rounded-md bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Continue Shopping
            </a>
        </div>
    </div>
</div>
