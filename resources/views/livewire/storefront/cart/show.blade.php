<div class="flex flex-col gap-8">
    <flux:heading size="xl">Your cart</flux:heading>

    @if (!$cart || $cart->lines->isEmpty())
        <div class="rounded-xl bg-zinc-50 p-10 text-center dark:bg-zinc-900" data-testid="empty-cart">
            <div class="text-lg font-medium">Your cart is empty</div>
            <a href="{{ route('storefront.home') }}" class="mt-3 inline-block text-sm font-medium text-zinc-900 underline dark:text-white" wire:navigate>Continue shopping</a>
        </div>
    @else
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2 flex flex-col gap-4" data-testid="cart-lines">
                @foreach ($cart->lines as $line)
                    <div class="flex items-center gap-4 rounded-xl bg-white p-4 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
                        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            @php($image = $line->variant->product->media->first())
                            @if ($image)
                                <img src="{{ asset('storage/'.$image->storage_key) }}" alt="" class="h-full w-full object-cover">
                            @endif
                        </div>
                        <div class="flex-1">
                            <div class="font-medium">{{ $line->variant->product->title }}</div>
                            <div class="text-sm text-zinc-500">{{ $line->variant->displayTitle() }}</div>
                            <div class="mt-1 text-sm font-semibold">{{ $cart->currency }} {{ number_format($line->unit_price_amount / 100, 2) }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="inline-flex items-center overflow-hidden rounded-lg border border-zinc-300 dark:border-zinc-700">
                                <button type="button" wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity - 1 }})" class="px-2 py-1 hover:bg-zinc-100 dark:hover:bg-zinc-800">−</button>
                                <span class="px-3 py-1 text-sm">{{ $line->quantity }}</span>
                                <button type="button" wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})" class="px-2 py-1 hover:bg-zinc-100 dark:hover:bg-zinc-800">+</button>
                            </div>
                            <button type="button" wire:click="removeLine({{ $line->id }})" class="text-sm text-zinc-500 hover:text-red-600">Remove</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <aside class="flex flex-col gap-4 rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800" data-testid="cart-summary">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span>Subtotal</span><span>{{ $totals['currency'] ?? $cart->currency }} {{ number_format(($totals['subtotal'] ?? 0) / 100, 2) }}</span></div>
                    @if (($totals['discount'] ?? 0) > 0)
                        <div class="flex justify-between text-emerald-600"><span>Discount</span><span>−{{ $totals['currency'] }} {{ number_format($totals['discount'] / 100, 2) }}</span></div>
                    @endif
                    <div class="flex justify-between"><span>Shipping</span><span>Calculated at checkout</span></div>
                    <div class="flex justify-between"><span>Tax</span><span>{{ $totals['currency'] ?? $cart->currency }} {{ number_format(($totals['tax'] ?? 0) / 100, 2) }}</span></div>
                </div>
                <div class="flex justify-between border-t border-zinc-200 pt-3 text-lg font-semibold dark:border-zinc-800">
                    <span>Total</span>
                    <span data-testid="cart-total">{{ $totals['currency'] ?? $cart->currency }} {{ number_format(($totals['total'] ?? 0) / 100, 2) }}</span>
                </div>

                <div class="flex flex-col gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                    <label class="text-xs font-medium uppercase text-zinc-500">Discount code</label>
                    <div class="flex gap-2">
                        <input type="text" wire:model="discountCode" class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800" placeholder="SAVE10" />
                        <button type="button" wire:click="applyDiscount" class="rounded-lg bg-zinc-900 px-4 py-1.5 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">Apply</button>
                    </div>
                    @if ($discountError)<div class="text-xs text-red-600">{{ $discountError }}</div>@endif
                    @if ($discountSuccess)<div class="text-xs text-emerald-600">{{ $discountSuccess }} <button type="button" wire:click="removeDiscount" class="ml-1 underline">Remove</button></div>@endif
                </div>

                <a href="{{ route('storefront.checkout') }}" class="mt-2 inline-flex w-full items-center justify-center rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900" data-testid="checkout-button" wire:navigate>
                    Checkout
                </a>
            </aside>
        </div>
    @endif
</div>
