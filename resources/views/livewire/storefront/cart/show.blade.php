<div>
    <x-slot:title>Cart</x-slot:title>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold">Shopping Cart</h1>

        <div class="mt-8 lg:grid lg:grid-cols-12 lg:gap-x-12">
            {{-- Cart Items --}}
            <div class="lg:col-span-7">
                @if ($lines->count() > 0)
                    <div class="divide-y divide-gray-200 border border-gray-200 rounded-lg dark:divide-gray-700 dark:border-gray-700">
                        @foreach ($lines as $line)
                            <div class="flex items-center gap-4 p-4">
                                <div class="h-16 w-16 flex-shrink-0 rounded bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium truncate">{{ $line->variant->product->title ?? 'Product' }}</p>
                                    <p class="text-sm text-gray-500">Qty: {{ $line->quantity }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium">{{ number_format($line->line_total_amount / 100, 2) }} {{ $currency }}</p>
                                    <button wire:click="removeLine({{ $line->id }})"
                                            class="mt-1 text-sm text-red-600 hover:text-red-800">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-lg border border-gray-200 p-8 text-center dark:border-gray-700">
                        <p class="text-gray-500 dark:text-gray-400">Your cart is empty</p>
                        <a href="/collections" class="mt-4 inline-block text-sm font-medium text-blue-600 hover:text-blue-500">
                            Continue Shopping
                        </a>
                    </div>
                @endif
            </div>

            {{-- Order Summary --}}
            <div class="mt-8 lg:col-span-5 lg:mt-0">
                <div class="rounded-lg bg-gray-50 p-6 dark:bg-gray-800">
                    <h2 class="text-lg font-medium">Order Summary</h2>
                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                            <span>{{ number_format($subtotal / 100, 2) }} {{ $currency }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Shipping</span>
                            <span>Calculated at checkout</span>
                        </div>
                        <div class="border-t border-gray-200 pt-2 dark:border-gray-700">
                            <div class="flex justify-between font-medium">
                                <span>Total</span>
                                <span>{{ number_format($subtotal / 100, 2) }} {{ $currency }}</span>
                            </div>
                        </div>
                    </div>
                    <button {{ $lines->isEmpty() ? 'disabled' : '' }}
                            class="mt-6 w-full rounded-lg bg-blue-600 px-8 py-3 text-white {{ $lines->isEmpty() ? 'opacity-50' : 'hover:bg-blue-700' }}">
                        Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
