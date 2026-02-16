<div class="mx-auto max-w-2xl px-4 py-12 text-center sm:px-6 lg:px-8">
    @if ($order)
        <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <h1 class="mt-4 text-3xl font-bold text-gray-900 dark:text-white">Thank you for your order!</h1>
        <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">Order {{ $order->order_number }}</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">A confirmation has been sent to {{ $order->email }}</p>

        <div class="mt-8 rounded-lg border bg-gray-50 p-6 text-left dark:border-gray-700 dark:bg-gray-800/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Order details</h2>
            <div class="mt-4 space-y-3">
                @foreach ($order->lines as $line)
                    <div class="flex items-center justify-between text-sm">
                        <div>
                            <span class="text-gray-900 dark:text-white">{{ $line->title_snapshot }}</span>
                            <span class="text-gray-500"> x{{ $line->quantity }}</span>
                        </div>
                        <span class="text-gray-900 dark:text-white">${{ number_format($line->total / 100, 2) }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 border-t pt-4 dark:border-gray-700">
                <div class="flex justify-between font-semibold text-gray-900 dark:text-white">
                    <span>Total</span>
                    <span>${{ number_format($order->total / 100, 2) }}</span>
                </div>
            </div>
        </div>

        <a href="{{ route('storefront.home') }}" class="mt-8 inline-flex items-center rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
            Continue shopping
        </a>
    @else
        <p class="text-gray-500 dark:text-gray-400">No order found.</p>
    @endif
</div>
