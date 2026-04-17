<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">Your Cart</h1>
        <div class="mt-8">
            @if($lines->isEmpty())
                <p class="text-gray-500 dark:text-gray-400">Your cart is empty.</p>
                <div class="mt-4">
                    <a href="/" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">Continue shopping</a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($lines as $line)
                        <div wire:key="line-{{ $line->id }}" class="flex items-center gap-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $line->variant?->product?->title ?? 'Unknown' }}</h3>
                                @if($line->variant?->title)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $line->variant->title }}</p>
                                @endif
                                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">${{ number_format($line->unit_price_amount / 100, 2) }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:button wire:click="updateQuantity({{ $line->id }}, {{ max(1, $line->quantity - 1) }})" size="sm" variant="ghost" icon="minus" />
                                <span class="w-8 text-center text-sm">{{ $line->quantity }}</span>
                                <flux:button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})" size="sm" variant="ghost" icon="plus" />
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">${{ number_format($line->line_total_amount / 100, 2) }}</p>
                                <flux:button wire:click="removeLine({{ $line->id }})" size="sm" variant="ghost" icon="trash" class="mt-1 text-red-500" />
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
                    <div class="flex justify-between text-base font-medium text-gray-900 dark:text-white">
                        <span>Subtotal</span>
                        <span>${{ number_format($subtotal / 100, 2) }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Shipping and taxes calculated at checkout.</p>
                    <div class="mt-6">
                        <a href="/checkout" class="block w-full rounded-md bg-blue-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors">
                            Proceed to Checkout
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
