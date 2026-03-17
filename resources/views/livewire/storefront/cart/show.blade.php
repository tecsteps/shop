<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Shopping Cart</h1>

    @if(count($lines) === 0)
        <div class="mt-8 text-center">
            <p class="text-gray-500 dark:text-gray-400">Your cart is empty.</p>
            <a href="{{ route('home') }}"
               class="mt-4 inline-block rounded-md bg-gray-900 px-6 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                Continue Shopping
            </a>
        </div>
    @else
        <div class="mt-8">
            {{-- Line Items --}}
            <div class="space-y-4">
                @foreach($lines as $line)
                    <div wire:key="cart-line-{{ $line['id'] }}"
                         class="flex items-center gap-4 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-900 dark:text-white">{{ $line['product_title'] }}</h3>
                            @if($line['variant_title'])
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $line['variant_title'] }}</p>
                            @endif
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ number_format($line['unit_price_amount'] / 100, 2) }} each</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button wire:click="updateQuantity({{ $line['id'] }}, {{ $line['quantity'] - 1 }})"
                                    class="rounded border border-gray-300 px-3 py-1 text-sm hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800">-</button>
                            <span class="min-w-[2rem] text-center text-sm font-medium">{{ $line['quantity'] }}</span>
                            <button wire:click="updateQuantity({{ $line['id'] }}, {{ $line['quantity'] + 1 }})"
                                    class="rounded border border-gray-300 px-3 py-1 text-sm hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800">+</button>
                        </div>

                        <div class="text-right">
                            <p class="font-medium text-gray-900 dark:text-white">{{ number_format($line['line_total_amount'] / 100, 2) }}</p>
                            <button wire:click="removeLine({{ $line['id'] }})"
                                    class="mt-1 text-xs text-red-600 hover:text-red-800 dark:text-red-400">Remove</button>
                        </div>
                    </div>
                @endforeach
            </div>

            @error('cart')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror

            {{-- Summary --}}
            <div class="mt-8 rounded-lg border border-gray-200 p-6 dark:border-gray-800">
                <div class="flex items-center justify-between text-lg font-semibold text-gray-900 dark:text-white">
                    <span>Subtotal</span>
                    <span>{{ number_format($subtotal / 100, 2) }}</span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Shipping and taxes calculated at checkout.</p>
                <button wire:click="proceedToCheckout"
                        class="mt-4 w-full rounded-md bg-gray-900 px-6 py-3 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                    Proceed to Checkout
                </button>
                <a href="{{ route('home') }}"
                   class="mt-2 block text-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                    Continue Shopping
                </a>
            </div>
        </div>
    @endif
</div>
