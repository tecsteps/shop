<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Your Cart</h1>

    @if ($cart->lines->isNotEmpty())
        <div class="mt-8 space-y-4">
            @foreach ($cart->lines as $line)
                <div class="flex items-center gap-4 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                    <div class="h-20 w-20 shrink-0 rounded-lg bg-gray-100 dark:bg-gray-800"></div>
                    <div class="flex-1">
                        <h3 class="font-medium text-gray-900 dark:text-white">{{ $line->variant->product->title ?? 'Product' }}</h3>
                        @if ($line->variant->optionValues && $line->variant->optionValues->isNotEmpty())
                            <p class="text-sm text-gray-500">{{ $line->variant->optionValues->pluck('value')->join(' / ') }}</p>
                        @endif
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">${{ number_format($line->unit_price / 100, 2) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity - 1 }})" class="rounded border px-2 py-1 text-sm dark:border-gray-700">-</button>
                        <span class="w-8 text-center text-sm text-gray-900 dark:text-white">{{ $line->quantity }}</span>
                        <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})" class="rounded border px-2 py-1 text-sm dark:border-gray-700">+</button>
                    </div>
                    <div class="w-20 text-right">
                        <p class="font-medium text-gray-900 dark:text-white">${{ number_format($line->total / 100, 2) }}</p>
                    </div>
                    <button wire:click="removeLine({{ $line->id }})" class="text-gray-400 hover:text-red-500">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endforeach
        </div>

        {{-- Discount Code --}}
        <div class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-800">
            @if ($appliedDiscount)
                <div class="flex items-center justify-between rounded-lg bg-green-50 px-4 py-3 dark:bg-green-900/20">
                    <div>
                        <p class="text-sm font-medium text-green-800 dark:text-green-300">
                            Discount applied: <span class="font-semibold">{{ $appliedCode }}</span>
                        </p>
                        @if ($discountResult && $discountResult->amount > 0)
                            <p class="text-sm text-green-700 dark:text-green-400">-${{ number_format($discountResult->amount / 100, 2) }}</p>
                        @elseif ($discountResult && $discountResult->freeShipping)
                            <p class="text-sm text-green-700 dark:text-green-400">Free shipping</p>
                        @endif
                    </div>
                    <button wire:click="removeDiscount" class="text-sm text-green-700 underline hover:text-green-900 dark:text-green-400 dark:hover:text-green-200">
                        Remove
                    </button>
                </div>
            @else
                <div>
                    <label for="discount-code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Discount code</label>
                    <div class="mt-1 flex gap-2">
                        <input
                            wire:model="discountCode"
                            wire:keydown.enter="applyDiscount"
                            type="text"
                            id="discount-code"
                            placeholder="Enter discount code"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                        <button
                            wire:click="applyDiscount"
                            class="shrink-0 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            Apply
                        </button>
                    </div>
                    @if ($this->discountError)
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $this->discountError }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Totals --}}
        <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-800">
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                <span>Subtotal</span>
                <span>${{ number_format($cart->lines->sum('total') / 100, 2) }}</span>
            </div>
            @if ($discountResult && $discountResult->amount > 0)
                <div class="mt-1 flex justify-between text-sm text-green-600 dark:text-green-400">
                    <span>Discount</span>
                    <span>-${{ number_format($discountResult->amount / 100, 2) }}</span>
                </div>
            @endif
            <div class="mt-2 flex justify-between text-lg font-semibold text-gray-900 dark:text-white">
                <span>Total</span>
                <span>${{ number_format(($cart->lines->sum('total') - ($discountResult?->amount ?? 0)) / 100, 2) }}</span>
            </div>
            <a href="{{ route('storefront.checkout') }}" class="mt-6 block w-full rounded-lg bg-blue-600 px-6 py-3 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                Proceed to checkout
            </a>
            <a href="{{ route('storefront.home') }}" class="mt-3 block text-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                Continue shopping
            </a>
        </div>
    @else
        <div class="py-20 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
            <p class="mt-4 text-gray-500 dark:text-gray-400">Your cart is empty</p>
            <a href="{{ route('storefront.home') }}" class="mt-6 inline-flex items-center rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                Continue shopping
            </a>
        </div>
    @endif
</div>
