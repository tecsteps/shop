<div>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 text-center">
        <div class="mb-6">
            <div class="size-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto">
                <flux:icon name="check" class="size-8 text-green-600 dark:text-green-400" />
            </div>
        </div>

        <h1 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white mb-2">Thank you for your order!</h1>
        <p class="text-zinc-500 dark:text-zinc-400 mb-8">
            Your order has been received and is being processed.
            @if ($checkout->email)
                A confirmation will be sent to <strong class="text-zinc-700 dark:text-zinc-300">{{ $checkout->email }}</strong>.
            @endif
        </p>

        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 text-left mb-8">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Order Summary</h2>

            <ul class="space-y-3 mb-4">
                @foreach ($lines as $line)
                    <li wire:key="confirm-line-{{ $line->id }}" class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-zinc-900 dark:text-white">{{ $line->variant?->product?->title ?? 'Product' }}</span>
                            @if ($line->variant?->title && $line->variant->title !== 'Default')
                                <span class="text-zinc-500 dark:text-zinc-400">({{ $line->variant->title }})</span>
                            @endif
                            <span class="text-zinc-400">&times; {{ $line->quantity }}</span>
                        </div>
                        <x-storefront.price :amount="$line->line_total_amount" :currency="$currency" class="text-sm" />
                    </li>
                @endforeach
            </ul>

            <dl class="space-y-2 pt-4 border-t border-zinc-200 dark:border-zinc-700 text-sm">
                @if ($totals)
                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-600 dark:text-zinc-400">Subtotal</dt>
                        <dd><x-storefront.price :amount="$totals['subtotal'] ?? 0" :currency="$currency" /></dd>
                    </div>
                    @if (($totals['discount'] ?? 0) > 0)
                        <div class="flex items-center justify-between text-green-600 dark:text-green-400">
                            <dt>Discount</dt>
                            <dd>-<x-storefront.price :amount="$totals['discount']" :currency="$currency" /></dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-600 dark:text-zinc-400">Shipping</dt>
                        <dd><x-storefront.price :amount="$totals['shipping'] ?? 0" :currency="$currency" /></dd>
                    </div>
                    @if (($totals['tax_total'] ?? 0) > 0)
                        <div class="flex items-center justify-between">
                            <dt class="text-zinc-600 dark:text-zinc-400">Tax</dt>
                            <dd><x-storefront.price :amount="$totals['tax_total']" :currency="$currency" /></dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700">
                        <dt class="font-semibold text-zinc-900 dark:text-white">Total</dt>
                        <dd><x-storefront.price :amount="$totals['total'] ?? 0" :currency="$currency" class="font-semibold" /></dd>
                    </div>
                @endif
            </dl>
        </div>

        <flux:button href="{{ route('storefront.home') }}" variant="primary" wire:navigate>
            Continue Shopping
        </flux:button>
    </div>
</div>
