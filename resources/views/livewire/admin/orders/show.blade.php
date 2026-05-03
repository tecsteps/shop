<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div>
            <flux:heading size="xl">{{ $order->order_number }}</flux:heading>
            <flux:text>{{ $order->placed_at?->format('M j, Y H:i') }} · {{ $order->email }}</flux:text>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($canConfirmBankTransfer)
                <flux:button variant="primary" wire:click="confirmBankTransfer">Confirm payment</flux:button>
            @endif
            <flux:button :href="route('admin.orders.index')" wire:navigate>Back to orders</flux:button>
        </div>
    </div>

    @error('order')
        <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" />
    @enderror

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <section class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 p-5 dark:border-zinc-800">
                    <flux:heading size="lg">Line items</flux:heading>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($order->lines as $line)
                        <div wire:key="admin-order-line-{{ $line->id }}" class="grid grid-cols-[1fr_auto] gap-4 p-5 text-sm">
                            <div>
                                <div class="font-medium">{{ $line->title_snapshot }}</div>
                                <div class="text-zinc-500">{{ $line->sku_snapshot ?: 'No SKU' }} · Qty {{ $line->quantity }}</div>
                            </div>
                            <div class="font-semibold">{{ \Illuminate\Support\Number::currency($line->total_amount / 100, $order->currency) }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="space-y-2 border-t border-zinc-200 p-5 text-sm dark:border-zinc-800">
                    <div class="flex justify-between"><span>Subtotal</span><span>{{ \Illuminate\Support\Number::currency($order->subtotal_amount / 100, $order->currency) }}</span></div>
                    <div class="flex justify-between"><span>Discount</span><span>-{{ \Illuminate\Support\Number::currency($order->discount_amount / 100, $order->currency) }}</span></div>
                    <div class="flex justify-between"><span>Shipping</span><span>{{ \Illuminate\Support\Number::currency($order->shipping_amount / 100, $order->currency) }}</span></div>
                    <div class="flex justify-between"><span>Tax</span><span>{{ \Illuminate\Support\Number::currency($order->tax_amount / 100, $order->currency) }}</span></div>
                    <div class="flex justify-between text-base font-semibold"><span>Total</span><span>{{ \Illuminate\Support\Number::currency($order->total_amount / 100, $order->currency) }}</span></div>
                </div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg">Fulfillment</flux:heading>
                @if (! $canFulfill)
                    <flux:callout class="mt-4" icon="information-circle" heading="Fulfillment cannot be created until payment is confirmed." />
                @else
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <flux:input wire:model="trackingCompany" label="Carrier" />
                        <flux:input wire:model="trackingNumber" label="Tracking number" />
                        <flux:input wire:model="trackingUrl" label="Tracking URL" />
                    </div>
                    <flux:button class="mt-4" wire:click="fulfillAll">Create fulfillment</flux:button>
                @endif

                <div class="mt-5 space-y-3">
                    @foreach ($order->fulfillments as $fulfillment)
                        <div wire:key="fulfillment-{{ $fulfillment->id }}" class="rounded-md border border-zinc-200 p-4 text-sm dark:border-zinc-800">
                            <div class="font-medium">{{ ucfirst($fulfillment->status->value) }}</div>
                            <div class="text-zinc-500">{{ $fulfillment->tracking_company }} {{ $fulfillment->tracking_number }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg">Refund</flux:heading>
                <div class="mt-4 grid gap-4 sm:grid-cols-[12rem_1fr_auto] sm:items-end">
                    <flux:input wire:model="refundAmount" type="number" min="0" label="Amount cents" />
                    <flux:input wire:model="refundReason" label="Reason" />
                    <flux:checkbox wire:model="restockRefund" label="Restock" />
                </div>
                <flux:button class="mt-4" wire:click="refund">Process refund</flux:button>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg">Status</flux:heading>
                <div class="mt-4 flex flex-wrap gap-2">
                    <flux:badge>{{ $order->status->value }}</flux:badge>
                    <flux:badge>{{ $order->financial_status->value }}</flux:badge>
                    <flux:badge>{{ $order->fulfillment_status->value }}</flux:badge>
                </div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 text-sm dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg">Customer</flux:heading>
                <div class="mt-4 font-medium">{{ $order->customer?->name ?? 'Guest customer' }}</div>
                <div class="text-zinc-500">{{ $order->email }}</div>
                @if ($order->customer)
                    <a href="{{ route('admin.customers.show', $order->customer) }}" wire:navigate class="mt-3 inline-flex text-sm font-medium underline">View customer</a>
                @endif
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 text-sm dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg">Shipping address</flux:heading>
                <div class="mt-4 text-zinc-600 dark:text-zinc-300">
                    @include('storefront.components.address', ['address' => $order->shipping_address_json])
                </div>
            </section>
        </aside>
    </div>
</div>
