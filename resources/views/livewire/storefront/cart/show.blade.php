<div class="space-y-10">
    <header class="space-y-2">
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-4xl">Your cart</h1>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Review the items you are about to purchase.</p>
    </header>

    @if ($cart === null || $cart->lines->isEmpty())
        <div class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Your cart is empty.</p>
            <a href="{{ route('storefront.collections.index') }}" class="mt-4 inline-flex items-center rounded-full bg-zinc-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Browse collections
            </a>
        </div>
    @else
        <div class="grid gap-10 lg:grid-cols-3">
            <section class="lg:col-span-2">
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($cart->lines as $line)
                        <li wire:key="line-{{ $line->id }}" class="flex gap-4 py-6">
                            <div class="h-24 w-24 flex-shrink-0 rounded-xl bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-900"></div>
                            <div class="flex flex-1 flex-col gap-2">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                            {{ $line->variant?->product?->title ?? 'Product' }}
                                        </h2>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $line->variant?->sku }}</p>
                                    </div>
                                    <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                        <x-storefront.price :amount="$line->line_total_amount" :currency="$cart->currency" />
                                    </p>
                                </div>

                                <div class="flex items-center justify-between gap-4">
                                    <div class="inline-flex items-center rounded-full border border-zinc-300 dark:border-zinc-700">
                                        <button type="button" wire:click="updateQty({{ $line->id }}, {{ $line->quantity - 1 }})" aria-label="Decrease quantity" class="px-3 py-1.5 text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">-</button>
                                        <span class="w-8 text-center text-sm tabular-nums text-zinc-900 dark:text-zinc-100">{{ $line->quantity }}</span>
                                        <button type="button" wire:click="updateQty({{ $line->id }}, {{ $line->quantity + 1 }})" aria-label="Increase quantity" class="px-3 py-1.5 text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">+</button>
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="removeLine({{ $line->id }})"
                                        class="text-xs font-medium text-zinc-500 underline-offset-2 hover:text-zinc-900 hover:underline dark:text-zinc-400 dark:hover:text-zinc-100"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                @error('cart')
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                        {{ $message }}
                    </div>
                @enderror
            </section>

            <aside class="h-fit space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Order summary</h2>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-400">
                        <span>Subtotal</span>
                        <span class="text-zinc-900 dark:text-zinc-100"><x-storefront.price :amount="$subtotal" :currency="$cart->currency" /></span>
                    </div>
                    <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-400">
                        <span>Shipping</span>
                        <span>Calculated at checkout</span>
                    </div>
                    <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-400">
                        <span>Tax</span>
                        <span>Calculated at checkout</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-zinc-200 pt-3 text-base font-semibold text-zinc-900 dark:border-zinc-800 dark:text-zinc-100">
                        <span>Total</span>
                        <span><x-storefront.price :amount="$subtotal" :currency="$cart->currency" /></span>
                    </div>
                </div>

                <form wire:submit.prevent="applyDiscount" class="space-y-2">
                    <label for="discount-code" class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Discount code</label>
                    <div class="flex gap-2">
                        <input
                            id="discount-code"
                            type="text"
                            wire:model="discountCode"
                            class="flex-1 rounded-full border border-zinc-300 bg-white px-4 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                            placeholder="Enter code"
                        />
                        <button type="submit" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-900 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-800">Apply</button>
                    </div>
                </form>

                <a
                    href="{{ route('storefront.checkout.show') }}"
                    class="inline-flex w-full items-center justify-center rounded-full bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                >
                    Checkout
                </a>
            </aside>
        </div>
    @endif
</div>
