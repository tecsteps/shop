<div class="mx-auto max-w-6xl px-6 py-12">
    <x-storefront.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Cart'],
    ]" />

    <flux:heading size="xl" class="mb-6">Your cart</flux:heading>

    @if ($isEmpty)
        <flux:callout icon="shopping-cart" data-testid="cart-empty">
            Your cart is empty. Browse <a href="{{ route('storefront.collections.index') }}" class="font-medium underline">our collections</a> to get started.
        </flux:callout>
    @else
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <ul class="divide-y rounded border dark:divide-zinc-700 dark:border-zinc-700" data-testid="cart-lines">
                    @foreach ($lines as $line)
                        <li wire:key="cart-line-{{ $line['id'] }}" class="flex gap-4 p-4" data-testid="cart-line">
                            <div class="size-24 rounded bg-zinc-100 dark:bg-zinc-800"></div>
                            <div class="flex-1">
                                <flux:text class="font-semibold">{{ $line['title'] }}</flux:text>
                                @if (! empty($line['sku']))
                                    <flux:text size="sm" variant="subtle">{{ $line['sku'] }}</flux:text>
                                @endif
                                <flux:text size="sm" variant="subtle">{{ number_format($line['unit_price_amount'] / 100, 2) }} {{ $currency }}</flux:text>
                                <div class="mt-3 flex items-center gap-2">
                                    <flux:button size="sm" variant="ghost" wire:click="decrement({{ $line['id'] }})">-</flux:button>
                                    <span data-testid="cart-qty">{{ $line['quantity'] }}</span>
                                    <flux:button size="sm" variant="ghost" wire:click="increment({{ $line['id'] }})">+</flux:button>
                                    <flux:button size="sm" variant="ghost" wire:click="remove({{ $line['id'] }})">Remove</flux:button>
                                </div>
                            </div>
                            <div class="text-right">
                                <flux:text class="font-semibold" data-testid="cart-line-total">{{ number_format($line['line_total_amount'] / 100, 2) }} {{ $currency }}</flux:text>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <aside class="space-y-6">
                <div class="rounded border p-4 dark:border-zinc-700">
                    <flux:heading size="md">Discount code</flux:heading>
                    @if ($appliedDiscountCode)
                        <div class="mt-2 flex items-center justify-between text-emerald-600">
                            <span data-testid="cart-discount-applied">{{ $appliedDiscountCode }}</span>
                            <flux:button size="sm" variant="ghost" wire:click="removeDiscount">Remove</flux:button>
                        </div>
                    @else
                        <div class="mt-2 flex gap-2">
                            <flux:input wire:model="discountCode" placeholder="Discount code" data-testid="cart-discount-input" />
                            <flux:button variant="ghost" wire:click="applyDiscount" data-testid="cart-discount-apply">Apply</flux:button>
                        </div>
                        @if ($discountError)
                            <flux:text size="sm" class="mt-2 text-rose-600" data-testid="cart-discount-error">{{ $discountError }}</flux:text>
                        @endif
                    @endif
                </div>

                <div class="rounded border p-4 dark:border-zinc-700">
                    <flux:heading size="md">Summary</flux:heading>
                    <dl class="mt-3 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <dt>Subtotal</dt>
                            <dd data-testid="cart-subtotal">{{ number_format($totals['subtotal'] / 100, 2) }} {{ $currency }}</dd>
                        </div>
                        @if ($totals['discount'] > 0)
                            <div class="flex justify-between text-emerald-600">
                                <dt>Discount</dt>
                                <dd data-testid="cart-discount">-{{ number_format($totals['discount'] / 100, 2) }} {{ $currency }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between text-base font-semibold">
                            <dt>Estimated total</dt>
                            <dd data-testid="cart-total">{{ number_format(($totals['subtotal'] - ($totals['discount'] ?? 0)) / 100, 2) }} {{ $currency }}</dd>
                        </div>
                    </dl>
                    <flux:button variant="primary" class="mt-4 w-full" wire:click="checkout" data-testid="cart-checkout">Checkout</flux:button>
                    <flux:button variant="ghost" class="mt-2 w-full" :href="route('storefront.collections.index')">Continue shopping</flux:button>
                </div>
            </aside>
        </div>
    @endif
</div>
