<section class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="space-y-2">
            <flux:button :href="route('admin.orders.index')" wire:navigate variant="ghost" icon="arrow-left">
                Orders
            </flux:button>

            <div>
                <flux:heading size="xl">{{ $order->order_number }}</flux:heading>
                <flux:text class="mt-1">{{ $order->email }} · {{ $order->placed_at?->format('M j, Y H:i') }}</flux:text>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($order->payment_method === \App\Enums\PaymentMethod::BankTransfer && $order->financial_status === \App\Enums\FinancialStatus::Pending)
                <flux:button wire:click="confirmBankTransferPayment" variant="primary" icon="banknotes">
                    Confirm payment
                </flux:button>
            @endif

            @if ($refundableAmount > 0)
                <flux:modal.trigger name="refund-order">
                    <flux:button variant="danger" icon="receipt-refund">
                        Refund
                    </flux:button>
                </flux:modal.trigger>
            @endif

            @if (in_array($order->financial_status, [\App\Enums\FinancialStatus::Paid, \App\Enums\FinancialStatus::PartiallyRefunded], true) && collect($remainingFulfillmentQuantities)->sum() > 0)
                <flux:modal.trigger name="fulfillment-order">
                    <flux:button variant="filled" icon="truck">
                        Create fulfillment
                    </flux:button>
                </flux:modal.trigger>
            @endif
        </div>
    </div>

    @error('orderAction')
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
            {{ $message }}
        </div>
    @enderror

    @error('fulfillment')
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300">
            {{ $message }}
        </div>
    @enderror

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500">Order</div>
            <div class="mt-2">
                <flux:badge>{{ Str::headline($order->status->value) }}</flux:badge>
            </div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500">Payment</div>
            <div class="mt-2">
                <flux:badge :color="$order->financial_status->value === 'paid' ? 'green' : ($order->financial_status->value === 'pending' ? 'amber' : 'zinc')">
                    {{ Str::headline($order->financial_status->value) }}
                </flux:badge>
            </div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500">Fulfillment</div>
            <div class="mt-2">
                <flux:badge :color="$order->fulfillment_status->value === 'fulfilled' ? 'green' : ($order->fulfillment_status->value === 'partial' ? 'amber' : 'zinc')">
                    {{ Str::headline($order->fulfillment_status->value) }}
                </flux:badge>
            </div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500">Total</div>
            <div class="mt-2 text-lg font-semibold text-zinc-950 dark:text-white">
                {{ \App\Support\Money::format($order->total_amount, $order->currency) }}
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                    <flux:heading size="lg">Line items</flux:heading>
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($order->lines as $line)
                        <div class="grid gap-3 px-5 py-4 md:grid-cols-[1fr_auto_auto]" wire:key="admin-order-line-{{ $line->getKey() }}">
                            <div>
                                <div class="font-medium text-zinc-950 dark:text-white">{{ $line->title_snapshot }}</div>
                                <div class="text-sm text-zinc-500">{{ $line->sku_snapshot ?: 'No SKU' }}</div>
                            </div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">Qty {{ $line->quantity }}</div>
                            <div class="font-medium text-zinc-950 dark:text-white">{{ \App\Support\Money::format($line->total_amount, $order->currency) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                    <flux:heading size="lg">Payments</flux:heading>
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($order->payments as $payment)
                        <div class="grid gap-3 px-5 py-4 md:grid-cols-[1fr_auto]" wire:key="admin-payment-{{ $payment->getKey() }}">
                            <div>
                                <div class="font-medium text-zinc-950 dark:text-white">{{ Str::headline($payment->method->value) }}</div>
                                <div class="text-sm text-zinc-500">{{ $payment->provider_payment_id }}</div>
                            </div>
                            <div class="flex items-center gap-3 md:justify-end">
                                <flux:badge>{{ Str::headline($payment->status->value) }}</flux:badge>
                                <span class="font-medium text-zinc-950 dark:text-white">{{ \App\Support\Money::format($payment->amount, $payment->currency) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                    <flux:heading size="lg">Fulfillments</flux:heading>
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($order->fulfillments as $fulfillment)
                        <div class="space-y-4 px-5 py-4" wire:key="admin-fulfillment-{{ $fulfillment->getKey() }}">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <flux:badge>{{ Str::headline($fulfillment->status->value) }}</flux:badge>
                                    @if ($fulfillment->tracking_number)
                                        <div class="mt-2 text-sm text-zinc-500">{{ $fulfillment->tracking_company }} {{ $fulfillment->tracking_number }}</div>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if ($fulfillment->status === \App\Enums\FulfillmentShipmentStatus::Pending)
                                        <flux:button wire:click="markFulfillmentShipped({{ $fulfillment->getKey() }})" size="sm" variant="filled">Mark shipped</flux:button>
                                    @endif
                                    @if ($fulfillment->status === \App\Enums\FulfillmentShipmentStatus::Shipped)
                                        <flux:button wire:click="markFulfillmentDelivered({{ $fulfillment->getKey() }})" size="sm" variant="filled">Mark delivered</flux:button>
                                    @endif
                                </div>
                            </div>
                            <div class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                                @foreach ($fulfillment->lines as $line)
                                    <div wire:key="admin-fulfillment-line-{{ $line->getKey() }}">
                                        {{ $line->orderLine?->title_snapshot }} · Qty {{ $line->quantity }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-zinc-500">No fulfillments yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <aside class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Customer</flux:heading>
                <div class="mt-4 space-y-1 text-sm">
                    <div class="font-medium text-zinc-950 dark:text-white">{{ $order->customer?->name ?: 'Guest checkout' }}</div>
                    <div class="text-zinc-500">{{ $order->email }}</div>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Summary</flux:heading>
                <div class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-zinc-600 dark:text-zinc-400">Subtotal</span>
                        <span class="font-medium text-zinc-950 dark:text-white">{{ \App\Support\Money::format($order->subtotal_amount, $order->currency) }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-zinc-600 dark:text-zinc-400">Discount</span>
                        <span class="font-medium text-zinc-950 dark:text-white">-{{ \App\Support\Money::format($order->discount_amount, $order->currency) }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-zinc-600 dark:text-zinc-400">Shipping</span>
                        <span class="font-medium text-zinc-950 dark:text-white">{{ \App\Support\Money::format($order->shipping_amount, $order->currency) }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-zinc-600 dark:text-zinc-400">Tax</span>
                        <span class="font-medium text-zinc-950 dark:text-white">{{ \App\Support\Money::format($order->tax_amount, $order->currency) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-t border-zinc-200 pt-3 text-base dark:border-zinc-800">
                        <span class="font-semibold text-zinc-950 dark:text-white">Total</span>
                        <span class="font-semibold text-zinc-950 dark:text-white">{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Refunds</flux:heading>
                <div class="mt-4 space-y-3">
                    @forelse ($order->refunds as $refund)
                        <div class="flex justify-between gap-4 text-sm" wire:key="admin-refund-{{ $refund->getKey() }}">
                            <div>
                                <div class="font-medium text-zinc-950 dark:text-white">{{ \App\Support\Money::format($refund->amount, $order->currency) }}</div>
                                <div class="text-zinc-500">{{ $refund->reason ?: 'No reason' }}</div>
                            </div>
                            <flux:badge>{{ Str::headline($refund->status->value) }}</flux:badge>
                        </div>
                    @empty
                        <div class="text-sm text-zinc-500">No refunds processed.</div>
                    @endforelse
                </div>
            </div>
        </aside>
    </div>

    <flux:modal name="refund-order" class="md:w-[32rem]">
        <form wire:submit="processRefund" class="space-y-6">
            <div>
                <flux:heading size="lg">Process refund</flux:heading>
                <flux:text class="mt-2">Refundable amount: {{ \App\Support\Money::format($refundableAmount, $order->currency) }}</flux:text>
            </div>

            <flux:input wire:model="refundAmount" label="Amount" placeholder="{{ number_format($refundableAmount / 100, 2, '.', '') }}" />
            <flux:error name="refundAmount" />
            <flux:textarea wire:model="refundReason" label="Reason" rows="3" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger">Process refund</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="fulfillment-order" class="md:w-[38rem]">
        <form wire:submit="createFulfillment" class="space-y-6">
            <div>
                <flux:heading size="lg">Create fulfillment</flux:heading>
                <flux:text class="mt-2">Select the remaining quantities included in this shipment.</flux:text>
            </div>

            <div class="space-y-3">
                @foreach ($order->lines as $line)
                    @php($remaining = $remainingFulfillmentQuantities[$line->getKey()] ?? 0)
                    <flux:input
                        type="number"
                        min="0"
                        max="{{ $remaining }}"
                        wire:model="fulfillmentLineQuantities.{{ $line->getKey() }}"
                        label="{{ $line->title_snapshot }} ({{ $remaining }} remaining)"
                        wire:key="fulfillment-input-{{ $line->getKey() }}"
                    />
                @endforeach
            </div>
            <flux:error name="fulfillment" />

            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input wire:model="trackingCompany" label="Carrier" placeholder="DHL" />
                <flux:input wire:model="trackingNumber" label="Tracking number" />
                <div class="sm:col-span-2">
                    <flux:input wire:model="trackingUrl" label="Tracking URL" />
                </div>
            </div>
            <flux:error name="trackingUrl" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Create fulfillment</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
