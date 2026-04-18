<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Order #{{ $order->order_number }}</flux:heading>
        <flux:button variant="ghost" href="{{ route('admin.orders.index') }}" wire:navigate>Back</flux:button>
    </div>

    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif
    @if (session('error'))
        <flux:callout variant="danger">{{ session('error') }}</flux:callout>
    @endif

    <div class="flex flex-wrap gap-2">
        @if ($order->fulfillment_status?->value !== 'fulfilled' && $order->status?->value !== 'cancelled')
            <flux:button variant="primary" wire:click="openFulfillment">Fulfill items</flux:button>
        @endif
        @if (in_array($order->financial_status?->value, ['paid', 'partially_refunded'], true))
            <flux:button variant="ghost" wire:click="openRefund">Refund</flux:button>
        @endif
        @if ($canConfirmBankTransfer)
            <flux:button variant="primary" wire:click="confirmBankTransfer" data-testid="confirm-bank-transfer">Confirm payment</flux:button>
        @endif
        @if ($order->status?->value !== 'cancelled' && $order->fulfillment_status?->value !== 'fulfilled')
            <flux:button variant="danger" wire:click="cancelOrder" wire:confirm="Cancel this order?">Cancel order</flux:button>
        @endif
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
                <flux:heading size="sm">Line items</flux:heading>
                <table class="mt-3 w-full text-sm">
                    <thead class="text-zinc-500">
                        <tr>
                            <th class="p-2 text-left">Title</th>
                            <th class="p-2 text-left">SKU</th>
                            <th class="p-2 text-right">Qty</th>
                            <th class="p-2 text-right">Price</th>
                            <th class="p-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->lines as $line)
                            <tr wire:key="line-{{ $line->id }}" class="border-t border-zinc-100 dark:border-zinc-800">
                                <td class="p-2">{{ $line->title_snapshot }}</td>
                                <td class="p-2">{{ $line->sku_snapshot }}</td>
                                <td class="p-2 text-right">{{ $line->quantity }} ({{ $line->fulfilledQuantity() }} fulfilled)</td>
                                <td class="p-2 text-right">{{ number_format($line->unit_price_amount / 100, 2) }}</td>
                                <td class="p-2 text-right">{{ number_format($line->total_amount / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-3 flex justify-end">
                    <div class="space-y-1 text-sm">
                        <div>Subtotal: {{ number_format($order->subtotal_amount / 100, 2) }}</div>
                        <div>Shipping: {{ number_format($order->shipping_amount / 100, 2) }}</div>
                        <div>Tax: {{ number_format($order->tax_amount / 100, 2) }}</div>
                        <div class="font-semibold">Total: {{ number_format($order->total_amount / 100, 2) }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
                <flux:heading size="sm">Timeline</flux:heading>
                <ul class="mt-3 space-y-1 text-sm">
                    <li>Placed at {{ $order->placed_at?->format('Y-m-d H:i') }}</li>
                    @foreach ($order->fulfillments as $fulfillment)
                        <li wire:key="timeline-f-{{ $fulfillment->id }}">Fulfillment #{{ $fulfillment->id }} - {{ $fulfillment->status?->value }}</li>
                    @endforeach
                    @foreach ($order->refunds as $refund)
                        <li wire:key="timeline-r-{{ $refund->id }}">Refund of {{ number_format($refund->amount / 100, 2) }} - {{ $refund->status?->value ?? $refund->status }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
                <flux:heading size="sm">Payment</flux:heading>
                <ul class="mt-3 space-y-1 text-sm">
                    <li>Method: {{ $order->payment_method?->value }}</li>
                    <li>Financial status: {{ $order->financial_status?->value }}</li>
                    <li>Total paid: {{ number_format(($order->total_amount - $order->totalRefunded()) / 100, 2) }}</li>
                </ul>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
                <flux:heading size="sm">Customer</flux:heading>
                <div class="mt-3 text-sm">
                    <div>{{ $order->email }}</div>
                    @if ($order->customer)
                        <a class="text-sky-600 hover:underline" href="{{ route('admin.customers.show', $order->customer) }}" wire:navigate>{{ $order->customer->fullName() }}</a>
                    @endif
                </div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
                <flux:heading size="sm">Shipping address</flux:heading>
                <pre class="mt-3 whitespace-pre-wrap text-xs">{{ json_encode($order->shipping_address_json, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    </div>

    <flux:modal wire:model.self="showFulfillment">
        <div class="space-y-4 p-2">
            <flux:heading size="lg">Fulfill items</flux:heading>
            @foreach ($order->lines as $line)
                @php($remaining = $line->quantity - $line->fulfilledQuantity())
                @if ($remaining > 0)
                    <div wire:key="fulfill-{{ $line->id }}" class="flex items-center justify-between gap-3">
                        <div class="text-sm">{{ $line->title_snapshot }} ({{ $remaining }} left)</div>
                        <flux:input type="number" min="0" max="{{ $remaining }}" wire:model="fulfillLines.{{ $line->id }}" />
                    </div>
                @endif
            @endforeach
            <flux:input wire:model="trackingCompany" label="Tracking company" />
            <flux:input wire:model="trackingNumber" label="Tracking number" />
            <flux:input wire:model="trackingUrl" label="Tracking URL" />
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeFulfillment">Cancel</flux:button>
                <flux:button variant="primary" wire:click="fulfill" data-testid="submit-fulfillment">Fulfill</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model.self="showRefund">
        <div class="space-y-4 p-2">
            <flux:heading size="lg">Refund</flux:heading>
            <flux:input type="number" wire:model.number="refundAmount" label="Amount (cents)" />
            <flux:input wire:model="refundReason" label="Reason" />
            <flux:checkbox wire:model="refundRestock" label="Restock items" />
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeRefund">Cancel</flux:button>
                <flux:button variant="primary" wire:click="refund" data-testid="submit-refund">Refund</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
