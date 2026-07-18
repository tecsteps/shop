@php
    $cart = $this->cart;
    $subtotal = $cart?->lines->sum('line_subtotal_amount') ?? 0;
    $discount = $this->discountAmount;
    $total = $subtotal - $discount;
    $currency = $cart?->currency ?? 'EUR';
@endphp

<div>
    <button
        type="button"
        wire:click="openDrawer"
        class="relative flex size-10 items-center justify-center rounded-lg text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
        aria-label="Open cart"
    >
        <flux:icon name="shopping-bag" class="size-5" />
        @if ($this->itemCount > 0)
            <span class="absolute -top-0.5 -right-0.5 flex size-5 items-center justify-center rounded-full bg-blue-600 text-[10px] font-semibold text-white">
                {{ $this->itemCount }}
            </span>
        @endif
    </button>

    <div
        x-show="$wire.open"
        x-cloak
        class="fixed inset-0 z-50"
        role="dialog"
        aria-modal="true"
        aria-label="Shopping cart"
        @keydown.escape.window="$wire.closeDrawer()"
    >
        <div
            x-show="$wire.open"
            x-transition.opacity
            wire:click="closeDrawer"
            class="fixed inset-0 bg-black/50"
        ></div>

        <div
            x-show="$wire.open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 flex w-full max-w-sm flex-col bg-white shadow-xl dark:bg-zinc-950"
        >
            <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Your Cart ({{ $this->itemCount }})</h2>
                <button type="button" wire:click="closeDrawer" aria-label="Close cart" class="flex size-8 items-center justify-center rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <flux:icon name="x-mark" class="size-5" />
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4" aria-live="polite">
                @if (! $cart || $cart->lines->isEmpty())
                    <div class="flex h-full flex-col items-center justify-center text-center">
                        <flux:icon name="shopping-bag" class="size-14 text-zinc-300 dark:text-zinc-700" />
                        <p class="mt-4 text-zinc-500 dark:text-zinc-400">Your cart is empty</p>
                        <flux:button wire:click="closeDrawer" variant="filled" class="mt-6">Continue shopping</flux:button>
                    </div>
                @else
                    <ul class="space-y-4">
                        @foreach ($cart->lines as $line)
                            <li wire:key="drawer-line-{{ $line->id }}" class="flex gap-3 border-b border-zinc-100 pb-4 dark:border-zinc-900">
                                @php $thumb = $line->variant->product->media->sortBy('position')->first(); @endphp
                                <div class="size-16 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                    @if ($thumb)
                                        <img src="{{ $thumb->url ?? Storage::url($thumb->storage_key) }}" alt="" class="size-full object-cover" />
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $line->variant->product->title }}</p>
                                    @if ($line->variant->optionValues->isNotEmpty())
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $line->variant->optionValues->pluck('value')->join(' / ') }}</p>
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
                                <button type="button" wire:click="removeLine({{ $line->id }})" aria-label="Remove {{ $line->variant->product->title }} from cart" class="self-start text-zinc-400 hover:text-red-600 dark:hover:text-red-400">
                                    <flux:icon name="trash" class="size-4" />
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if ($cart && $cart->lines->isNotEmpty())
                <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-800">
                    @if (session('cart_discount_code'))
                        <div class="mb-3 flex items-center justify-between rounded-lg bg-green-50 px-3 py-2 text-sm dark:bg-green-950">
                            <span class="font-medium text-green-700 dark:text-green-400">{{ session('cart_discount_code') }}</span>
                            <button type="button" wire:click="removeDiscount" class="text-green-700 underline hover:text-green-900 dark:text-green-400">Remove</button>
                        </div>
                    @else
                        <form wire:submit.prevent="applyDiscount" class="mb-3 flex gap-2">
                            <flux:input wire:model="discountCode" placeholder="Discount code" size="sm" class="flex-1" aria-label="Discount code" />
                            <flux:button type="submit" size="sm" variant="filled">Apply</flux:button>
                        </form>
                        @if ($discountError)
                            <p class="mb-2 text-xs text-red-600 dark:text-red-400">{{ $discountError }}</p>
                        @endif
                    @endif

                    <dl class="space-y-1 text-sm" aria-live="polite">
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
                        <div class="flex justify-between border-t border-zinc-200 pt-1 text-base font-semibold text-zinc-900 dark:border-zinc-800 dark:text-white">
                            <dt>Estimated total</dt>
                            <dd><x-storefront.price :amount="$total" :currency="$currency" /></dd>
                        </div>
                    </dl>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Shipping and taxes calculated at checkout.</p>

                    <flux:button wire:click="proceedToCheckout" variant="primary" class="mt-4 w-full">Checkout</flux:button>
                    <div class="mt-2 text-center">
                        <button type="button" wire:click="closeDrawer" class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">Continue shopping</button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
