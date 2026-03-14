<div>
    {{-- Overlay --}}
    <div
        x-data
        x-show="$wire.open"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="$wire.open = false"
        class="fixed inset-0 bg-black/50 z-40"
        x-cloak
        aria-hidden="true"
    ></div>

    {{-- Drawer panel --}}
    <div
        x-data
        x-show="$wire.open"
        x-transition:enter="transition ease-in-out duration-300 transform"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-300 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        @keydown.escape.window="$wire.open = false"
        x-cloak
        class="fixed inset-y-0 right-0 w-96 max-w-full bg-white dark:bg-zinc-900 z-50 flex flex-col shadow-xl"
        role="dialog"
        aria-modal="true"
        aria-label="Shopping cart"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Cart ({{ $itemCount }})</h2>
            <button
                @click="$wire.open = false"
                class="p-2 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
                aria-label="Close cart"
            >
                <flux:icon name="x-mark" class="size-5" />
            </button>
        </div>

        {{-- Cart contents --}}
        <div class="flex-1 overflow-y-auto p-4">
            @if ($lines->isEmpty())
                <div class="flex flex-col items-center justify-center h-full text-center">
                    <flux:icon name="shopping-bag" class="size-16 text-zinc-300 dark:text-zinc-600 mb-4" />
                    <p class="text-zinc-500 dark:text-zinc-400 text-sm">Your cart is empty.</p>
                    <a
                        href="{{ route('storefront.home') }}"
                        class="mt-4 text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline"
                        wire:navigate
                        @click="$wire.open = false"
                    >
                        Continue shopping
                    </a>
                </div>
            @else
                <ul class="space-y-4">
                    @foreach ($lines as $line)
                        <li wire:key="cart-line-{{ $line->id }}" class="flex gap-3">
                            {{-- Product image --}}
                            <div class="shrink-0 size-16 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                                @if ($line->variant?->product?->media?->first())
                                    <img
                                        src="{{ $line->variant->product->media->first()->url }}"
                                        alt="{{ $line->variant->product->title }}"
                                        class="size-full object-cover"
                                    />
                                @else
                                    <div class="size-full flex items-center justify-center">
                                        <flux:icon name="shopping-bag" class="size-6 text-zinc-400" />
                                    </div>
                                @endif
                            </div>

                            {{-- Product info --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">
                                    {{ $line->variant?->product?->title ?? 'Product' }}
                                </p>
                                @if ($line->variant?->title && $line->variant->title !== 'Default')
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $line->variant->title }}</p>
                                @endif
                                <div class="mt-1">
                                    <x-storefront.price :amount="$line->unit_price_amount" :currency="$currency" class="text-xs" />
                                </div>

                                {{-- Quantity controls --}}
                                <div class="flex items-center gap-2 mt-2">
                                    <div class="inline-flex items-center border border-zinc-300 dark:border-zinc-600 rounded">
                                        <button
                                            type="button"
                                            wire:click="updateQuantity({{ $line->id }}, {{ max(0, $line->quantity - 1) }})"
                                            class="flex items-center justify-center w-7 h-7 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
                                            aria-label="Decrease quantity"
                                        >
                                            <flux:icon name="minus" class="size-3" />
                                        </button>
                                        <span class="w-8 text-center text-xs font-medium text-zinc-900 dark:text-white">{{ $line->quantity }}</span>
                                        <button
                                            type="button"
                                            wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})"
                                            class="flex items-center justify-center w-7 h-7 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
                                            aria-label="Increase quantity"
                                        >
                                            <flux:icon name="plus" class="size-3" />
                                        </button>
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="removeItem({{ $line->id }})"
                                        class="text-zinc-400 hover:text-red-500 dark:hover:text-red-400 transition-colors"
                                        aria-label="Remove item"
                                    >
                                        <flux:icon name="trash" class="size-4" />
                                    </button>
                                </div>
                            </div>

                            {{-- Line total --}}
                            <div class="shrink-0 text-right">
                                <x-storefront.price :amount="$line->line_total_amount" :currency="$currency" class="text-sm" />
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Discount code --}}
                <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="flex gap-2">
                        <flux:input
                            wire:model="discountCode"
                            placeholder="Discount code"
                            class="flex-1"
                            size="sm"
                        />
                        <flux:button
                            wire:click="applyDiscount"
                            size="sm"
                            wire:loading.attr="disabled"
                        >
                            Apply
                        </flux:button>
                    </div>
                    @if ($discountError)
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $discountError }}</p>
                    @endif
                    @if ($discountSuccess)
                        <p class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $discountSuccess }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Footer --}}
        @if ($lines->isNotEmpty())
            <div class="border-t border-zinc-200 dark:border-zinc-700 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Subtotal</span>
                    <x-storefront.price :amount="$subtotal" :currency="$currency" class="text-sm" />
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Shipping and taxes calculated at checkout.</p>
                <div class="space-y-2">
                    <a
                        href="{{ route('storefront.cart') }}"
                        class="block w-full text-center text-sm font-medium py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors"
                        wire:navigate
                        @click="$wire.open = false"
                    >
                        View Cart
                    </a>
                    <flux:button
                        href="{{ route('storefront.cart') }}"
                        variant="primary"
                        class="w-full justify-center"
                    >
                        Checkout
                    </flux:button>
                </div>
            </div>
        @endif
    </div>
</div>
