<div>
    @if ($open)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Shopping cart">
            <button type="button" wire:click="closeDrawer" class="absolute inset-0 h-full w-full cursor-default bg-black/50" aria-label="Close cart" tabindex="-1"></button>
            <div class="absolute right-0 top-0 flex h-full w-full flex-col bg-white shadow-xl sm:w-96 dark:bg-zinc-950">
                <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-4 dark:border-zinc-800">
                    <h2 class="text-lg font-semibold">Your Cart ({{ $totals['item_count'] }})</h2>
                    <button type="button" wire:click="closeDrawer" class="flex h-11 w-11 items-center justify-center rounded hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Close cart">
                        <flux:icon.x-mark class="size-5" variant="outline" />
                    </button>
                </div>

                @if ($cartNotice)
                    <p class="border-b border-zinc-200 px-4 py-2 text-sm dark:border-zinc-800" aria-live="polite">{{ $cartNotice }}</p>
                @endif

                @if ($lines === [])
                    <div class="flex flex-1 flex-col items-center justify-center gap-3 p-8 text-center">
                        <flux:icon.shopping-bag class="size-12 text-zinc-300 dark:text-zinc-700" variant="outline" />
                        <p class="text-zinc-500 dark:text-zinc-400">Your cart is empty</p>
                        <flux:button wire:click="closeDrawer" variant="ghost">Continue shopping</flux:button>
                    </div>
                @else
                    <ul class="flex-1 divide-y divide-zinc-200 overflow-y-auto px-4 dark:divide-zinc-800" aria-live="polite">
                        @foreach ($lines as $line)
                            <li wire:key="drawer-line-{{ $line['id'] }}" class="flex gap-3 py-4">
                                @if ($line['image_url'])
                                    <img src="{{ $line['image_url'] }}" alt="" class="h-16 w-16 shrink-0 rounded object-cover" loading="lazy" />
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold">{{ $line['title'] }}</p>
                                    @if ($line['variant'] !== '')
                                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $line['variant'] }}</p>
                                    @endif
                                    @if ($line['is_backorder'] ?? false)
                                        <p class="text-xs text-blue-600 dark:text-blue-400">Available on backorder</p>
                                    @endif
                                    <div class="mt-2 flex items-center justify-between gap-2">
                                        <div class="inline-flex items-center rounded border border-zinc-300 dark:border-zinc-700">
                                            <button type="button" wire:click="decrementLine({{ $line['id'] }})" wire:loading.attr="disabled" class="flex h-8 w-8 items-center justify-center" aria-label="Decrease quantity">-</button>
                                            <span class="w-8 text-center text-sm">{{ $line['quantity'] }}</span>
                                            <button type="button" wire:click="incrementLine({{ $line['id'] }})" wire:loading.attr="disabled" class="flex h-8 w-8 items-center justify-center" aria-label="Increase quantity">+</button>
                                        </div>
                                        <p class="text-sm font-semibold">{{ \App\Support\Money::format($line['total'], $line['currency']) }}</p>
                                    </div>
                                </div>
                                <button type="button" wire:click="removeLine({{ $line['id'] }})" class="h-fit text-zinc-400 hover:text-red-600" aria-label="Remove {{ $line['title'] }} from cart">
                                    <flux:icon.trash class="size-5" variant="outline" />
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <div class="border-t border-zinc-200 px-4 py-4 dark:border-zinc-800">
                        @if ($appliedCode)
                            <p class="flex items-center justify-between text-sm text-green-700 dark:text-green-400">
                                <span>{{ $appliedCode }} applied</span>
                                <button type="button" wire:click="removeDiscount" class="underline">Remove</button>
                            </p>
                        @else
                            <div class="flex gap-2">
                                <flux:input wire:model="discountCodeInput" placeholder="Discount code" aria-label="Discount code" class="text-sm" />
                                <flux:button wire:click="applyDiscount" variant="ghost" wire:loading.attr="disabled">Apply</flux:button>
                            </div>
                            @error('discountCodeInput') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @endif

                        <dl class="mt-3 flex flex-col gap-1 text-sm">
                            <div class="flex justify-between">
                                <dt>Subtotal</dt>
                                <dd>{{ \App\Support\Money::format($totals['subtotal'], $totals['currency']) }}</dd>
                            </div>
                            @if ($totals['discount'] > 0)
                                <div class="flex justify-between text-green-700 dark:text-green-400">
                                    <dt>Discount</dt>
                                    <dd>-{{ \App\Support\Money::format($totals['discount'], $totals['currency']) }}</dd>
                                </div>
                            @endif
                            <div class="flex justify-between font-semibold">
                                <dt>Estimated total</dt>
                                <dd>{{ \App\Support\Money::format($totals['total'], $totals['currency']) }}</dd>
                            </div>
                        </dl>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Shipping and taxes calculated at checkout</p>

                        <flux:button wire:click="startCheckout" variant="primary" class="mt-3 w-full">Checkout</flux:button>
                        <button type="button" wire:click="closeDrawer" class="mt-2 w-full text-center text-sm text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Continue shopping</button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
