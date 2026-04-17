<div>
    <h1 class="text-3xl font-bold tracking-tight">
        Your Cart
        @if($this->cartItemCount > 0)
            <span class="text-lg font-normal text-zinc-500 dark:text-zinc-400">({{ $this->cartItemCount }} {{ Str::plural('item', $this->cartItemCount) }})</span>
        @endif
    </h1>

    @if(! $cart || $cart->lines->isEmpty())
        {{-- Empty Cart State --}}
        <div class="mt-8 py-12 text-center">
            <p class="text-zinc-500 dark:text-zinc-400">Your cart is empty.</p>
            <div class="mt-6">
                <flux:button variant="primary" href="{{ route('storefront.collections.index') }}">
                    Continue Shopping
                </flux:button>
            </div>
        </div>
    @else
        {{-- Cart Lines --}}
        <div class="mt-8 divide-y divide-zinc-200 border-t border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach($cart->lines as $line)
                <div wire:key="cart-line-{{ $line->id }}" class="flex gap-4 py-6 sm:gap-6">
                    {{-- Product Thumbnail --}}
                    <div class="h-16 w-16 shrink-0 overflow-hidden rounded-md bg-zinc-100 dark:bg-zinc-800">
                        @if($line->variant?->product?->media?->isNotEmpty())
                            <img
                                src="{{ $line->variant->product->media->first()->url }}"
                                alt="{{ $line->variant->product->title }}"
                                class="h-full w-full object-cover"
                            >
                        @endif
                    </div>

                    {{-- Product Info --}}
                    <div class="flex min-w-0 flex-1 flex-col justify-between sm:flex-row sm:items-center sm:gap-6">
                        <div class="min-w-0 flex-1">
                            @if($line->variant?->product)
                                <a
                                    href="{{ route('storefront.products.show', $line->variant->product->handle) }}"
                                    class="truncate text-sm font-semibold hover:text-zinc-600 dark:hover:text-zinc-300"
                                >
                                    {{ $line->variant->product->title }}
                                </a>
                            @endif
                            @if($line->variant?->sku)
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">SKU: {{ $line->variant->sku }}</p>
                            @endif
                        </div>

                        {{-- Quantity Stepper --}}
                        <div class="mt-2 flex items-center gap-4 sm:mt-0">
                            <div class="flex items-center rounded-md border border-zinc-300 dark:border-zinc-600">
                                <button
                                    wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity - 1 }})"
                                    class="px-2.5 py-1 text-sm text-zinc-600 transition-colors hover:text-zinc-900 disabled:cursor-not-allowed disabled:opacity-50 dark:text-zinc-400 dark:hover:text-zinc-100"
                                    @if($line->quantity <= 1) disabled @endif
                                >
                                    -
                                </button>
                                <span class="min-w-[2rem] border-x border-zinc-300 px-2 py-1 text-center text-sm dark:border-zinc-600">
                                    {{ $line->quantity }}
                                </span>
                                <button
                                    wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})"
                                    class="px-2.5 py-1 text-sm text-zinc-600 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                                >
                                    +
                                </button>
                            </div>

                            {{-- Line Total --}}
                            <span class="w-24 text-right text-sm font-medium">
                                {{ number_format($line->unit_price_amount * $line->quantity / 100, 2) }} EUR
                            </span>

                            {{-- Remove Button --}}
                            <button
                                wire:click="removeLine({{ $line->id }})"
                                wire:confirm="Remove this item from your cart?"
                                class="ml-2 text-sm text-zinc-400 transition-colors hover:text-red-500 dark:text-zinc-500 dark:hover:text-red-400"
                                title="Remove item"
                            >
                                <flux:icon name="trash" class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Discount Code Section --}}
        <div class="mt-6 border-t border-zinc-200 pt-6 dark:border-zinc-700">
            @if(session('discount_code') && $discountSuccess)
                {{-- Applied Discount --}}
                <div class="flex items-center gap-3">
                    <span class="text-sm text-zinc-600 dark:text-zinc-400">Applied:</span>
                    <flux:badge>{{ session('discount_code') }}</flux:badge>
                    <button
                        wire:click="removeDiscount"
                        class="text-sm text-zinc-400 transition-colors hover:text-red-500 dark:text-zinc-500 dark:hover:text-red-400"
                    >
                        Remove
                    </button>
                </div>
                @if($discountSuccess)
                    <p class="mt-2 text-sm text-green-600 dark:text-green-400">{{ $discountSuccess }}</p>
                @endif
            @else
                {{-- Discount Input --}}
                <div class="flex items-end gap-3">
                    <div class="w-full max-w-xs">
                        <flux:input
                            wire:model="discountCode"
                            label="Discount Code"
                            placeholder="Enter code"
                            wire:keydown.enter="applyDiscount"
                        />
                    </div>
                    <flux:button wire:click="applyDiscount" variant="primary" size="sm">
                        Apply
                    </flux:button>
                </div>
                @if($discountError)
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $discountError }}</p>
                @endif
            @endif
        </div>

        {{-- Totals Section --}}
        <div class="mt-6 border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <div class="flex items-center justify-between text-base font-medium">
                <span>Subtotal</span>
                <span>{{ number_format($this->subtotal / 100, 2) }} EUR</span>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-end">
            <flux:button href="{{ route('storefront.collections.index') }}" variant="ghost">
                Continue Shopping
            </flux:button>
            <flux:button href="/checkout" variant="primary">
                Proceed to Checkout
            </flux:button>
        </div>
    @endif
</div>
