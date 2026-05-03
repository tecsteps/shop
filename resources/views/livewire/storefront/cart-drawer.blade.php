<div wire:open-cart.window="open" wire:keydown.escape.window="close">
    <div class="sr-only" aria-live="polite">
        Cart items: {{ $cart?->itemCount() ?? 0 }}
    </div>

    @if($open)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="cart-drawer-title">
            <button type="button" wire:click="close" class="absolute inset-0 bg-zinc-950/45" aria-label="Close cart"></button>

            <aside class="absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-white shadow-2xl dark:bg-zinc-950">
                <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                    <div>
                        <h2 id="cart-drawer-title" class="text-lg font-semibold tracking-normal">Cart</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $cart?->itemCount() ?? 0 }} items</p>
                    </div>
                    <button type="button" wire:click="close" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900">Close</button>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-4">
                    @error('cart')
                        <p class="mb-4 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-950/40 dark:text-red-300">{{ $message }}</p>
                    @enderror

                    @if(! $cart || $cart->lines->isEmpty())
                        <div class="flex h-full flex-col items-center justify-center text-center">
                            <h3 class="text-base font-semibold">Your cart is empty</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Add products to review them here before checkout.</p>
                            <a href="/collections" wire:click="close" class="mt-6 rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">Browse products</a>
                        </div>
                    @else
                        <div class="grid gap-4">
                            @foreach($cart->lines as $line)
                                @php
                                    $variant = $line->variant;
                                    $product = $variant?->product;
                                    $media = $product?->media?->sortBy('position')->first();
                                    $variantTitle = $variant?->optionValues?->pluck('value')->join(' / ') ?: 'Default';
                                @endphp

                                <article wire:key="cart-drawer-line-{{ $line->id }}" class="grid grid-cols-[72px_1fr] gap-4 border-b border-zinc-200 pb-4 dark:border-zinc-800">
                                    <div class="overflow-hidden rounded-md bg-zinc-100 dark:bg-zinc-900">
                                        @if($media)
                                            <img src="{{ asset('storage/'.$media->storage_key) }}" alt="{{ $media->alt_text ?: $product?->title }}" class="aspect-square h-full w-full object-cover">
                                        @else
                                            <div class="flex aspect-square items-center justify-center px-2 text-center text-xs font-semibold text-zinc-600 dark:text-zinc-300">{{ $product?->title }}</div>
                                        @endif
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <h3 class="truncate text-sm font-semibold">{{ $product?->title }}</h3>
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $variantTitle }}</p>
                                            </div>
                                            <button type="button" wire:click="removeLine({{ $line->id }})" class="text-xs font-semibold text-zinc-500 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white">Remove</button>
                                        </div>

                                        <div class="mt-3 flex items-center justify-between gap-3">
                                            <div class="inline-flex h-9 items-center rounded-md border border-zinc-300 dark:border-zinc-700">
                                                <button type="button" wire:click="decrementLine({{ $line->id }})" class="h-9 w-9 text-lg leading-none" aria-label="Decrease {{ $product?->title }} quantity">-</button>
                                                <span class="min-w-8 text-center text-sm font-medium">{{ $line->quantity }}</span>
                                                <button type="button" wire:click="incrementLine({{ $line->id }})" class="h-9 w-9 text-lg leading-none" aria-label="Increase {{ $product?->title }} quantity">+</button>
                                            </div>
                                            <div class="text-sm font-semibold">
                                                @include('storefront.components.price', ['amount' => $line->line_total_amount, 'currency' => $cart->currency])
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if($cart && $cart->lines->isNotEmpty())
                    <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">
                        <div class="grid gap-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <span class="text-zinc-600 dark:text-zinc-400">Subtotal</span>
                                <span class="font-semibold">@include('storefront.components.price', ['amount' => $cart->subtotalAmount(), 'currency' => $cart->currency])</span>
                            </div>
                            @if($cart->discountAmount() > 0)
                                <div class="flex justify-between gap-4">
                                    <span class="text-zinc-600 dark:text-zinc-400">Discount</span>
                                    <span class="font-semibold">-@include('storefront.components.price', ['amount' => $cart->discountAmount(), 'currency' => $cart->currency])</span>
                                </div>
                            @endif
                            <div class="flex justify-between gap-4 border-t border-zinc-200 pt-2 dark:border-zinc-800">
                                <span class="font-semibold">Total</span>
                                <span class="font-semibold">@include('storefront.components.price', ['amount' => $cart->totalAmount(), 'currency' => $cart->currency])</span>
                            </div>

                            <form wire:submit="applyDiscount" class="mt-2 grid gap-2">
                                <label class="text-sm font-medium" for="cart-drawer-discount">Discount code</label>
                                <div class="flex gap-2">
                                    <input id="cart-drawer-discount" wire:model="discountCode" type="text" autocomplete="off" placeholder="Discount code" class="min-w-0 flex-1 rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    <button type="submit" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold data-loading:opacity-60 dark:border-zinc-700">
                                        Apply
                                    </button>
                                </div>
                            </form>

                            @if($cart->discount_code)
                                <div class="flex items-center justify-between gap-3 rounded-md bg-zinc-100 px-3 py-2 text-sm dark:bg-zinc-900">
                                    <span class="min-w-0 truncate font-medium">{{ $cart->discount_code }}</span>
                                    <button type="button" wire:click="removeDiscount" class="shrink-0 text-xs font-semibold text-zinc-600 underline dark:text-zinc-300">Remove</button>
                                </div>
                            @endif

                            @error('discountCode')
                                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-4 grid gap-2">
                            <a href="/cart" wire:click="close" class="rounded-md bg-zinc-950 px-4 py-3 text-center text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">Checkout</a>
                            <a href="/collections" wire:click="close" class="rounded-md border border-zinc-300 px-4 py-3 text-center text-sm font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900">Continue shopping</a>
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    @endif
</div>
