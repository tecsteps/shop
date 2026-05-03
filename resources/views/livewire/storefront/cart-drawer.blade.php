<flux:modal name="cart-drawer" class="md:w-[32rem]">
    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <flux:heading size="lg">Cart</flux:heading>
                <flux:text class="mt-1">{{ $lineCount }} {{ Str::plural('item', $lineCount) }}</flux:text>
            </div>

            <flux:button :href="route('cart.show')" wire:navigate variant="subtle" size="sm" icon="shopping-bag">
                View cart
            </flux:button>
        </div>

        @if ($lines->isEmpty())
            <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                <flux:icon name="shopping-bag" class="mx-auto size-10 text-zinc-400 dark:text-zinc-600" />
                <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">Your cart is empty.</p>
                <flux:button :href="route('collections.index')" wire:navigate variant="primary" class="mt-5">
                    Browse products
                </flux:button>
            </div>
        @else
            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($lines as $line)
                    <div class="flex gap-4 py-4" wire:key="cart-drawer-line-{{ $line->getKey() }}">
                        <a href="{{ route('products.show', $line->variant->product->handle) }}" wire:navigate class="flex size-16 shrink-0 items-center justify-center rounded-md border border-zinc-200 bg-zinc-100 text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-600">
                            <flux:icon name="shopping-bag" class="size-6" />
                        </a>

                        <div class="min-w-0 flex-1 space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('products.show', $line->variant->product->handle) }}" wire:navigate class="line-clamp-2 text-sm font-medium text-zinc-950 hover:underline dark:text-white">
                                        {{ $line->variant->product->title }}
                                    </a>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $line->variant->optionValues->map(fn ($value) => $value->option->name.': '.$value->value)->implode(' / ') ?: ($line->variant->sku ?: 'Default') }}
                                    </p>
                                </div>

                                <x-storefront.price :amount="$line->line_total_amount" :currency="$cart->currency" class="justify-end text-sm" />
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <div class="inline-flex h-9 items-center rounded-md border border-zinc-200 dark:border-zinc-700">
                                    <button type="button" wire:click="decreaseQuantity({{ $line->getKey() }})" class="flex size-9 items-center justify-center border-r border-zinc-200 text-sm dark:border-zinc-700" aria-label="Decrease {{ $line->variant->product->title }} quantity">-</button>
                                    <span class="w-10 text-center text-sm tabular-nums">{{ $line->quantity }}</span>
                                    <button type="button" wire:click="increaseQuantity({{ $line->getKey() }})" class="flex size-9 items-center justify-center border-l border-zinc-200 text-sm dark:border-zinc-700" aria-label="Increase {{ $line->variant->product->title }} quantity">+</button>
                                </div>

                                <flux:button wire:click="removeLine({{ $line->getKey() }})" variant="ghost" size="sm" icon="trash" aria-label="Remove {{ $line->variant->product->title }}" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="space-y-4 border-t border-zinc-200 pt-5 dark:border-zinc-800">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-600 dark:text-zinc-400">Subtotal</span>
                    <x-storefront.price :amount="$subtotal" :currency="$cart->currency" />
                </div>

                <flux:button wire:click="checkout" variant="primary" class="w-full">
                    Checkout
                </flux:button>
            </div>
        @endif
    </div>
</flux:modal>
