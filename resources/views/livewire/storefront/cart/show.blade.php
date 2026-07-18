@php
    $cart = $this->cart;
    $subtotal = $cart?->lines->sum('line_subtotal_amount') ?? 0;
    $discount = $this->discountAmount;
    $total = $subtotal - $discount;
    $currency = $cart?->currency ?? 'EUR';
@endphp

<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Your Cart</h1>

    @if (! $cart || $cart->lines->isEmpty())
        <div class="mt-16 flex flex-col items-center justify-center py-16 text-center">
            <flux:icon name="shopping-bag" class="size-16 text-zinc-300 dark:text-zinc-700" />
            <p class="mt-4 text-lg text-zinc-500 dark:text-zinc-400">Your cart is empty</p>
            <flux:button :href="route('home')" wire:navigate variant="filled" class="mt-6">Continue shopping</flux:button>
        </div>
    @else
        <div class="mt-8 grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <!-- Desktop table -->
                <table class="hidden w-full lg:table">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:border-zinc-800 dark:text-zinc-400">
                            <th class="py-2" scope="col">Product</th>
                            <th class="py-2" scope="col">Price</th>
                            <th class="py-2" scope="col">Quantity</th>
                            <th class="py-2" scope="col">Total</th>
                            <th class="py-2" scope="col"><span class="sr-only">Remove</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cart->lines as $line)
                            <tr wire:key="cart-row-{{ $line->id }}" class="border-b border-zinc-100 dark:border-zinc-900">
                                <td class="py-4">
                                    <div class="flex items-center gap-3">
                                        @php $thumb = $line->variant->product->media->sortBy('position')->first(); @endphp
                                        <div class="size-16 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                            @if ($thumb)
                                                <img src="{{ $thumb->url ?? Storage::url($thumb->storage_key) }}" alt="" class="size-full object-cover" />
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-medium text-zinc-900 dark:text-white">{{ $line->variant->product->title }}</p>
                                            @if ($line->variant->optionValues->isNotEmpty())
                                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $line->variant->optionValues->pluck('value')->join(' / ') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4"><x-storefront.price :amount="$line->unit_price_amount" :currency="$currency" /></td>
                                <td class="py-4">
                                    <x-storefront.quantity-selector
                                        :value="$this->quantities[$line->id] ?? $line->quantity"
                                        :wire-model="'quantities.'.$line->id"
                                        compact
                                    />
                                </td>
                                <td class="py-4"><x-storefront.price :amount="$line->line_total_amount" :currency="$currency" /></td>
                                <td class="py-4 text-right">
                                    <button type="button" wire:click="removeLine({{ $line->id }})" wire:confirm="Remove this item from your cart?" aria-label="Remove {{ $line->variant->product->title }} from cart" class="text-zinc-400 hover:text-red-600 dark:hover:text-red-400">
                                        <flux:icon name="trash" class="size-5" />
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Mobile cards -->
                <div class="space-y-4 lg:hidden">
                    @foreach ($cart->lines as $line)
                        <div wire:key="cart-card-{{ $line->id }}" class="flex gap-3 border-b border-zinc-100 pb-4 dark:border-zinc-900">
                            @php $thumb = $line->variant->product->media->sortBy('position')->first(); @endphp
                            <div class="size-16 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                @if ($thumb)
                                    <img src="{{ $thumb->url ?? Storage::url($thumb->storage_key) }}" alt="" class="size-full object-cover" />
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $line->variant->product->title }}</p>
                                @if ($line->variant->optionValues->isNotEmpty())
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $line->variant->optionValues->pluck('value')->join(' / ') }}</p>
                                @endif
                                <div class="mt-2 flex items-center justify-between">
                                    <x-storefront.quantity-selector
                                        :value="$this->quantities[$line->id] ?? $line->quantity"
                                        :wire-model="'quantities.'.$line->id"
                                        compact
                                    />
                                    <x-storefront.price :amount="$line->line_total_amount" :currency="$currency" />
                                </div>
                            </div>
                            <button type="button" wire:click="removeLine({{ $line->id }})" wire:confirm="Remove this item from your cart?" aria-label="Remove {{ $line->variant->product->title }} from cart" class="self-start text-zinc-400 hover:text-red-600 dark:hover:text-red-400">
                                <flux:icon name="trash" class="size-5" />
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <div class="rounded-xl bg-zinc-50 p-6 dark:bg-zinc-900">
                    @if (session('cart_discount_code'))
                        <div class="flex items-center justify-between rounded-lg bg-green-50 px-3 py-2 text-sm dark:bg-green-950">
                            <span class="font-medium text-green-700 dark:text-green-400">{{ session('cart_discount_code') }} applied</span>
                            <button type="button" wire:click="removeDiscount" class="text-green-700 underline hover:text-green-900 dark:text-green-400">Remove</button>
                        </div>
                    @else
                        <form wire:submit.prevent="applyDiscount" class="flex gap-2">
                            <flux:input wire:model="discountCode" placeholder="Discount code" size="sm" class="flex-1" aria-label="Discount code" />
                            <flux:button type="submit" size="sm" variant="filled">Apply</flux:button>
                        </form>
                        @if ($discountError)
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $discountError }}</p>
                        @endif
                    @endif

                    <dl class="mt-4 space-y-2 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-800" aria-live="polite">
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">Subtotal</dt>
                            <dd><x-storefront.price :amount="$subtotal" :currency="$currency" /></dd>
                        </div>
                        @if ($discount > 0)
                            <div class="flex justify-between text-green-600 dark:text-green-400">
                                <dt>Discount</dt>
                                <dd>-{{ \App\Support\Money::format($discount, $currency) }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold text-zinc-900 dark:border-zinc-800 dark:text-white">
                            <dt>Total</dt>
                            <dd><x-storefront.price :amount="$total" :currency="$currency" /></dd>
                        </div>
                    </dl>
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Shipping and taxes calculated at checkout.</p>

                    <flux:button wire:click="proceedToCheckout" variant="primary" class="mt-6 w-full">Checkout</flux:button>
                    <div class="mt-3 text-center">
                        <a href="{{ route('home') }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">Continue shopping</a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
