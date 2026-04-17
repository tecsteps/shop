<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Your Cart</h1>

    @if($lines->isEmpty())
        <div class="mt-16 flex flex-col items-center justify-center text-center">
            <svg class="h-16 w-16 text-zinc-200 dark:text-zinc-700" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
            </svg>
            <p class="mt-4 text-zinc-500 dark:text-zinc-400">Your cart is empty</p>
            <a href="{{ route('storefront.home') }}"
               class="mt-4 rounded-md border border-zinc-300 dark:border-zinc-600 px-6 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                Continue shopping
            </a>
        </div>
    @else
        <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
            {{-- Cart Items --}}
            <div class="lg:col-span-2 space-y-4">
                @foreach($lines as $line)
                    <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-4" wire:key="cart-line-{{ $line->id }}">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ $line->variant->product->title ?? 'Product' }}
                            </p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                ${{ number_format($line->unit_price_amount / 100, 2) }} each
                            </p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center space-x-2">
                                <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity - 1 }})"
                                        class="rounded border border-zinc-300 dark:border-zinc-600 px-2 py-1 text-xs hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                    -
                                </button>
                                <span class="text-sm w-8 text-center text-zinc-700 dark:text-zinc-300">{{ $line->quantity }}</span>
                                <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})"
                                        class="rounded border border-zinc-300 dark:border-zinc-600 px-2 py-1 text-xs hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                    +
                                </button>
                            </div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white w-20 text-right">
                                ${{ number_format($line->line_total_amount / 100, 2) }}
                            </p>
                            <button wire:click="removeLine({{ $line->id }})"
                                    class="text-xs text-red-500 hover:text-red-700">
                                Remove
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Summary --}}
            <div class="lg:col-span-1">
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Order Summary</h2>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500 dark:text-zinc-400">Subtotal</span>
                        <span class="text-zinc-900 dark:text-white font-medium">${{ number_format($subtotal / 100, 2) }}</span>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Shipping and taxes calculated at checkout.</p>
                    <a href="{{ route('storefront.checkout') }}"
                       class="block w-full rounded-md bg-zinc-900 dark:bg-white px-4 py-3 text-center text-sm font-medium text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-colors">
                        Proceed to Checkout
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
