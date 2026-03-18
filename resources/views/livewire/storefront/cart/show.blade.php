<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Shopping Cart</h1>

    @if($cart && $cart->lines->count() > 0)
        <div class="mt-8">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="pb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Product</th>
                        <th class="pb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Price</th>
                        <th class="pb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Quantity</th>
                        <th class="pb-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($cart->lines as $line)
                        <tr>
                            <td class="py-4">
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $line->variant->product->title ?? 'Product' }}</p>
                                @if($line->variant->optionValues && $line->variant->optionValues->isNotEmpty())
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $line->variant->optionValues->map(fn ($ov) => $ov->option->name . ': ' . $ov->value)->join(', ') }}
                                    </p>
                                @elseif($line->variant->sku)
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $line->variant->sku }}</p>
                                @endif
                            </td>
                            <td class="py-4 text-sm text-zinc-900 dark:text-white">
                                {{ number_format($line->unit_price_amount / 100, 2) }} {{ $cart->currency }}
                            </td>
                            <td class="py-4">
                                <div class="flex items-center gap-2">
                                    <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity - 1 }})"
                                            class="rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600">-</button>
                                    <span class="text-sm text-zinc-900 dark:text-white">{{ $line->quantity }}</span>
                                    <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})"
                                            class="rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600">+</button>
                                </div>
                            </td>
                            <td class="py-4 text-right text-sm font-medium text-zinc-900 dark:text-white">
                                {{ number_format($line->line_total_amount / 100, 2) }} {{ $cart->currency }}
                            </td>
                            <td class="py-4 text-right">
                                <button wire:click="removeLine({{ $line->id }})"
                                        class="text-sm text-red-600 hover:text-red-500">Remove</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-8 flex justify-end">
                <div class="w-full max-w-sm space-y-4">
                    {{-- Discount code --}}
                    @if($appliedCode)
                        <div class="flex items-center justify-between rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-900/20">
                            <div>
                                <p class="text-sm font-medium text-green-800 dark:text-green-300">{{ $appliedCode }}</p>
                                <p class="text-xs text-green-600 dark:text-green-400">{{ $discountDescription }}</p>
                            </div>
                            <button wire:click="removeDiscount" class="text-sm text-green-700 hover:text-green-900 dark:text-green-400 dark:hover:text-green-200">Remove</button>
                        </div>
                    @else
                        <div>
                            <div class="flex gap-2">
                                <input wire:model="discountCode" type="text" placeholder="Discount code"
                                       class="flex-1 rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                <button wire:click="applyDiscount"
                                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                    Apply
                                </button>
                            </div>
                            @if($discountError)
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $discountError }}</p>
                            @endif
                        </div>
                    @endif

                    <div class="space-y-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <div class="flex justify-between text-sm text-zinc-600 dark:text-zinc-400">
                            <p>Subtotal</p>
                            <p>{{ number_format($cart->lines->sum('line_total_amount') / 100, 2) }} {{ $cart->currency }}</p>
                        </div>
                        @if($discountAmount && $discountAmount > 0)
                            <div class="flex justify-between text-sm text-green-600">
                                <p>Discount</p>
                                <p>-{{ number_format($discountAmount / 100, 2) }} {{ $cart->currency }}</p>
                            </div>
                        @endif
                        <div class="flex justify-between text-base font-medium text-zinc-900 dark:text-white">
                            <p>Estimated Total</p>
                            @php
                                $subtotal = $cart->lines->sum('line_total_amount');
                                $estimatedTotal = $subtotal - ($discountAmount ?? 0);
                            @endphp
                            <p>{{ number_format($estimatedTotal / 100, 2) }} {{ $cart->currency }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Shipping and taxes calculated at checkout.</p>
                    <button wire:click="proceedToCheckout"
                            class="w-full rounded-lg bg-zinc-900 px-6 py-3 text-base font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                        Proceed to Checkout
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="mt-12 text-center">
            <svg class="mx-auto h-16 w-16 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
            </svg>
            <p class="mt-4 text-lg text-zinc-500 dark:text-zinc-400">Your cart is empty</p>
            <a href="{{ route('home') }}"
               class="mt-6 inline-block rounded-lg bg-zinc-900 px-6 py-3 text-base font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                Continue Shopping
            </a>
        </div>
    @endif
</div>
