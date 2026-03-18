<div>
    @if($isOpen)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Shopping cart">
            <div wire:click="closeDrawer" class="fixed inset-0 bg-black/50 transition-opacity"></div>

            <div class="fixed inset-y-0 right-0 flex max-w-full">
                <div class="w-screen max-w-sm bg-white p-6 shadow-xl dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Your Cart</h2>
                        <button wire:click="closeDrawer"
                                class="rounded-md p-2 text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                                aria-label="Close cart">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    @if($cart && $cart->lines->count() > 0)
                        <div class="mt-6 flow-root">
                            <ul role="list" class="-my-6 divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($cart->lines as $line)
                                    <li class="flex py-6">
                                        <div class="flex-1">
                                            <div class="flex justify-between text-sm font-medium text-zinc-900 dark:text-white">
                                                <h3>{{ $line->variant->product->title ?? 'Product' }}</h3>
                                                <p>{{ number_format($line->line_total_amount / 100, 2) }} {{ $cart->currency }}</p>
                                            </div>
                                            @if($line->variant->optionValues && $line->variant->optionValues->isNotEmpty())
                                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                                    {{ $line->variant->optionValues->map(fn ($ov) => $ov->option->name . ': ' . $ov->value)->join(', ') }}
                                                </p>
                                            @elseif($line->variant->sku)
                                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $line->variant->sku }}</p>
                                            @endif
                                            <div class="mt-2 flex items-center gap-2">
                                                <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity - 1 }})"
                                                        class="rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600">-</button>
                                                <span class="text-sm">{{ $line->quantity }}</span>
                                                <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})"
                                                        class="rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600">+</button>
                                                <button wire:click="removeLine({{ $line->id }})"
                                                        class="ml-auto text-sm text-red-600 hover:text-red-500">Remove</button>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="mt-6 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <div class="flex justify-between text-base font-medium text-zinc-900 dark:text-white">
                                <p>Subtotal</p>
                                <p>{{ number_format($cart->lines->sum('line_total_amount') / 100, 2) }} {{ $cart->currency }}</p>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('storefront.checkout') }}"
                                   class="flex items-center justify-center rounded-lg bg-zinc-900 px-6 py-3 text-base font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                                    Checkout
                                </a>
                            </div>
                            <div class="mt-2">
                                <a href="{{ route('storefront.cart') }}"
                                   class="flex items-center justify-center rounded-lg border border-zinc-300 px-6 py-3 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                    View Cart
                                </a>
                            </div>
                            <div class="mt-2 flex justify-center text-center text-sm text-zinc-500">
                                <button wire:click="closeDrawer" class="font-medium text-zinc-600 hover:text-zinc-500 dark:text-zinc-400">
                                    Continue Shopping
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="mt-8 flex flex-1 flex-col items-center justify-center py-12">
                            <svg class="h-16 w-16 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                            <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">Your cart is empty</p>
                            <button wire:click="closeDrawer"
                                    class="mt-4 rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                Continue shopping
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
