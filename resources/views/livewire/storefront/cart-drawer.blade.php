<div>
    <div wire:loading.class="opacity-50" @class([
        'fixed inset-0 z-40 transition',
        'pointer-events-none opacity-0' => ! $open,
        'opacity-100' => $open,
    ])>
        <div class="absolute inset-0 bg-black/40" wire:click="close"></div>

        <aside role="dialog" aria-label="Shopping cart" @class([
            'absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-white shadow-xl transition-transform dark:bg-zinc-900',
            'translate-x-full' => ! $open,
            'translate-x-0' => $open,
        ])>
            <header class="flex items-center justify-between border-b px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">Your Cart ({{ $totals['item_count'] }})</flux:heading>
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="close" aria-label="Close cart" />
            </header>

            <div class="flex-1 overflow-y-auto px-6 py-4" data-testid="cart-drawer-body">
                @if (empty($linesData))
                    <div class="flex h-full flex-col items-center justify-center gap-4 text-center" data-testid="cart-drawer-empty">
                        <flux:icon.shopping-bag class="size-12 text-zinc-400" />
                        <flux:text>Your cart is empty</flux:text>
                        <flux:button variant="ghost" wire:click="close">Continue shopping</flux:button>
                    </div>
                @else
                    <ul class="divide-y dark:divide-zinc-700">
                        @foreach ($linesData as $line)
                            <li wire:key="cart-line-{{ $line['id'] }}" class="flex gap-4 py-4" data-testid="cart-drawer-line">
                                <div class="size-16 rounded bg-zinc-100 dark:bg-zinc-800"></div>
                                <div class="flex-1">
                                    <flux:text class="font-semibold">{{ $line['title'] }}</flux:text>
                                    @if (! empty($line['sku']))
                                        <flux:text size="sm" variant="subtle">{{ $line['sku'] }}</flux:text>
                                    @endif
                                    <div class="mt-2 flex items-center gap-2">
                                        <flux:button size="sm" variant="ghost" wire:click="decrementLine({{ $line['id'] }})" aria-label="Decrease quantity">-</flux:button>
                                        <span data-testid="cart-drawer-quantity">{{ $line['quantity'] }}</span>
                                        <flux:button size="sm" variant="ghost" wire:click="incrementLine({{ $line['id'] }})" aria-label="Increase quantity">+</flux:button>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end">
                                    <flux:text class="font-semibold">{{ number_format($line['line_total_amount'] / 100, 2) }} {{ $currency }}</flux:text>
                                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeLine({{ $line['id'] }})" aria-label="Remove {{ $line['title'] }} from cart" />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if (! empty($linesData))
                <footer class="border-t px-6 py-4 dark:border-zinc-700">
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <dt>Subtotal</dt>
                            <dd>{{ number_format($totals['subtotal'] / 100, 2) }} {{ $currency }}</dd>
                        </div>
                        @if ($totals['discount'] > 0)
                            <div class="flex justify-between text-emerald-600">
                                <dt>Discount</dt>
                                <dd>-{{ number_format($totals['discount'] / 100, 2) }} {{ $currency }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between text-base font-semibold">
                            <dt>Estimated total</dt>
                            <dd data-testid="cart-drawer-total">{{ number_format($totals['total'] / 100, 2) }} {{ $currency }}</dd>
                        </div>
                    </dl>
                    <flux:text size="xs" variant="subtle" class="mt-2">Shipping and taxes calculated at checkout</flux:text>
                    <flux:button variant="primary" class="mt-4 w-full" :href="route('storefront.checkout.show')" wire:navigate>Checkout</flux:button>
                    <flux:button variant="ghost" class="mt-2 w-full" wire:click="close">Continue shopping</flux:button>
                </footer>
            @endif
        </aside>
    </div>
</div>
