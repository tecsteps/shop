<div class="mx-auto max-w-2xl space-y-6 px-6 py-16" data-testid="checkout-confirmation">
    <div class="text-center">
        <flux:icon.check-circle class="mx-auto size-16 text-emerald-500" />
        <flux:heading size="xl" class="mt-4">Thank you for your order</flux:heading>
        <flux:text class="mt-2">Order number: <span class="font-mono font-semibold" data-testid="order-number">#{{ $number }}</span></flux:text>
    </div>

    @if ($order)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:heading size="sm">Order summary</flux:heading>
                <flux:badge>{{ $order->financial_status?->value ?? 'pending' }}</flux:badge>
            </div>

            <table class="mt-4 w-full text-sm">
                <thead class="text-zinc-500">
                    <tr>
                        <th class="py-2 text-left">Item</th>
                        <th class="py-2 text-right">Qty</th>
                        <th class="py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->lines as $line)
                        <tr wire:key="line-{{ $line->id }}" class="border-t border-zinc-100 dark:border-zinc-800">
                            <td class="py-2">
                                <div>{{ $line->title_snapshot }}</div>
                                @if ($line->sku_snapshot)
                                    <flux:text size="sm" variant="subtle">{{ $line->sku_snapshot }}</flux:text>
                                @endif
                            </td>
                            <td class="py-2 text-right">{{ $line->quantity }}</td>
                            <td class="py-2 text-right">{{ number_format($line->total_amount / 100, 2) }} {{ $order->currency }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-zinc-200 dark:border-zinc-700">
                        <td colspan="2" class="py-2 text-right">Subtotal</td>
                        <td class="py-2 text-right">{{ number_format($order->subtotal_amount / 100, 2) }} {{ $order->currency }}</td>
                    </tr>
                    @if ($order->shipping_amount > 0)
                        <tr>
                            <td colspan="2" class="py-2 text-right">Shipping</td>
                            <td class="py-2 text-right">{{ number_format($order->shipping_amount / 100, 2) }} {{ $order->currency }}</td>
                        </tr>
                    @endif
                    @if ($order->tax_amount > 0)
                        <tr>
                            <td colspan="2" class="py-2 text-right">Tax</td>
                            <td class="py-2 text-right">{{ number_format($order->tax_amount / 100, 2) }} {{ $order->currency }}</td>
                        </tr>
                    @endif
                    @if ($order->discount_amount > 0)
                        <tr class="text-emerald-600">
                            <td colspan="2" class="py-2 text-right">Discount</td>
                            <td class="py-2 text-right">-{{ number_format($order->discount_amount / 100, 2) }} {{ $order->currency }}</td>
                        </tr>
                    @endif
                    <tr class="border-t border-zinc-200 font-semibold dark:border-zinc-700">
                        <td colspan="2" class="py-2 text-right">Total</td>
                        <td class="py-2 text-right">{{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}</td>
                    </tr>
                </tfoot>
            </table>

            @if ($order->financial_status?->value === 'pending')
                <flux:callout variant="warning" class="mt-6">
                    Your bank transfer is awaiting payment. We'll update you once it has been confirmed.
                </flux:callout>
            @endif
        </div>
    @endif

    <div class="text-center">
        <flux:button variant="primary" :href="route('storefront.home')">Continue shopping</flux:button>
    </div>
</div>
