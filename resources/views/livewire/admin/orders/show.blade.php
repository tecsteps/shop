<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Order {{ $order->order_number }}</flux:heading>
        <a href="{{ route('admin.orders.index') }}">
            <flux:button variant="ghost">Back to Orders</flux:button>
        </a>
    </div>

    {{-- Order Info --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Status</flux:text>
            <div class="mt-1"><flux:badge>{{ ucfirst($order->status->value) }}</flux:badge></div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Payment</flux:text>
            <div class="mt-1"><flux:badge>{{ ucfirst($order->financial_status->value) }}</flux:badge></div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Fulfillment</flux:text>
            <div class="mt-1"><flux:badge>{{ ucfirst($order->fulfillment_status->value) }}</flux:badge></div>
        </div>
    </div>

    {{-- Customer Info --}}
    @if ($order->customer)
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="md" class="mb-2">Customer</flux:heading>
            <p>{{ $order->customer->name }} ({{ $order->customer->email }})</p>
        </div>
    @endif

    {{-- Line Items --}}
    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <flux:heading size="md">Line Items</flux:heading>
        </div>
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach ($order->lines as $line)
                <div wire:key="line-{{ $line->id }}" class="flex items-center justify-between px-4 py-3">
                    <div>
                        <p class="font-medium">{{ $line->title_snapshot }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">SKU: {{ $line->sku_snapshot }} x {{ $line->quantity }}</p>
                    </div>
                    <p class="font-medium">${{ number_format($line->total_amount / 100, 2) }}</p>
                </div>
            @endforeach
        </div>
        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <div class="space-y-1 text-sm">
                <div class="flex justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Subtotal</span>
                    <span>${{ number_format($order->subtotal_amount / 100, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Shipping</span>
                    <span>${{ number_format($order->shipping_amount / 100, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Tax</span>
                    <span>${{ number_format($order->tax_amount / 100, 2) }}</span>
                </div>
                <flux:separator />
                <div class="flex justify-between font-semibold">
                    <span>Total</span>
                    <span>${{ number_format($order->total_amount / 100, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Payments --}}
    @if ($order->payments->isNotEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="md" class="mb-2">Payments</flux:heading>
            @foreach ($order->payments as $payment)
                <div wire:key="payment-{{ $payment->id }}" class="flex items-center justify-between py-1 text-sm">
                    <span>{{ ucfirst($payment->method->value) }} - {{ ucfirst($payment->status->value) }}</span>
                    <span>${{ number_format($payment->amount / 100, 2) }}</span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Fulfillments --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="mb-3 flex items-center justify-between">
            <flux:heading size="md">Fulfillments</flux:heading>
            <flux:button wire:click="openFulfillmentModal" variant="primary" size="sm">Create Fulfillment</flux:button>
        </div>
        @foreach ($order->fulfillments as $fulfillment)
            <div wire:key="fulfillment-{{ $fulfillment->id }}" class="rounded-md bg-zinc-50 p-3 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:badge size="sm">{{ ucfirst($fulfillment->status->value) }}</flux:badge>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $fulfillment->created_at->format('M d, Y') }}</span>
                </div>
                @if ($fulfillment->tracking_number)
                    <p class="mt-1 text-sm">{{ $fulfillment->tracking_company }} - {{ $fulfillment->tracking_number }}</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Refunds --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="mb-3 flex items-center justify-between">
            <flux:heading size="md">Refunds</flux:heading>
            <flux:button wire:click="openRefundModal" variant="ghost" size="sm">Issue Refund</flux:button>
        </div>
        @foreach ($order->refunds as $refund)
            <div wire:key="refund-{{ $refund->id }}" class="flex items-center justify-between py-1 text-sm">
                <span>{{ $refund->reason ?? 'No reason' }} - <flux:badge size="sm">{{ ucfirst($refund->status->value) }}</flux:badge></span>
                <span>${{ number_format($refund->amount / 100, 2) }}</span>
            </div>
        @endforeach
    </div>

    {{-- Fulfillment Modal --}}
    <flux:modal wire:model="showFulfillmentModal" name="fulfillment-modal">
        <div class="space-y-4">
            <flux:heading size="lg">Create Fulfillment</flux:heading>
            <flux:input wire:model="trackingCompany" label="Tracking Company" />
            <flux:input wire:model="trackingNumber" label="Tracking Number" />
            <div class="flex items-center gap-3">
                <flux:button wire:click="createFulfillment" variant="primary">Create</flux:button>
                <flux:button wire:click="$set('showFulfillmentModal', false)" variant="ghost">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Refund Modal --}}
    <flux:modal wire:model="showRefundModal" name="refund-modal">
        <div class="space-y-4">
            <flux:heading size="lg">Issue Refund</flux:heading>
            <flux:input wire:model="refundAmount" label="Amount (cents)" type="number" />
            <flux:textarea wire:model="refundReason" label="Reason" rows="2" />
            <div class="flex items-center gap-3">
                <flux:button wire:click="processRefund" variant="primary">Process Refund</flux:button>
                <flux:button wire:click="$set('showRefundModal', false)" variant="ghost">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
