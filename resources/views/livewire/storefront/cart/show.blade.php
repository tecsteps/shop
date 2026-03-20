<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Shopping Cart</h1>

        @if($lines->isEmpty())
            <div class="mt-8 rounded-lg border border-zinc-200 p-8 text-center dark:border-zinc-700">
                <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                </svg>
                <h2 class="mt-4 text-lg font-medium text-zinc-900 dark:text-white">Your cart is empty</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Browse our products and add items to your cart.</p>
                <a href="/collections" class="mt-6 inline-block rounded-md bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    Continue Shopping
                </a>
            </div>
        @else
            <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <div class="space-y-4">
                        @foreach($lines as $line)
                            <div class="flex items-center gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700" wire:key="cart-line-{{ $line->id }}">
                                <div class="flex-1">
                                    <h3 class="font-medium text-zinc-900 dark:text-white">{{ $line->variant->product->title }}</h3>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">${{ number_format($line->unit_price_amount / 100, 2) }} each</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity - 1 }})" class="rounded border border-zinc-300 px-3 py-1 text-sm dark:border-zinc-600">-</button>
                                    <span class="w-8 text-center text-sm text-zinc-900 dark:text-white">{{ $line->quantity }}</span>
                                    <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})" class="rounded border border-zinc-300 px-3 py-1 text-sm dark:border-zinc-600">+</button>
                                </div>
                                <div class="w-24 text-right">
                                    <p class="font-medium text-zinc-900 dark:text-white">${{ number_format($line->line_total_amount / 100, 2) }}</p>
                                </div>
                                <button wire:click="removeLine({{ $line->id }})" class="text-sm text-red-500 hover:text-red-700">Remove</button>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Order Summary</h2>
                        <div class="mt-4 space-y-2">
                            <div class="flex justify-between text-sm text-zinc-600 dark:text-zinc-400">
                                <span>Subtotal</span>
                                <span>${{ number_format($subtotal / 100, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-zinc-600 dark:text-zinc-400">
                                <span>Shipping</span>
                                <span>Calculated at checkout</span>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <div class="flex justify-between font-semibold text-zinc-900 dark:text-white">
                                <span>Total</span>
                                <span>${{ number_format($subtotal / 100, 2) }}</span>
                            </div>
                        </div>
                        <button wire:click="proceedToCheckout" class="mt-6 w-full rounded-md bg-zinc-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            Proceed to Checkout
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
