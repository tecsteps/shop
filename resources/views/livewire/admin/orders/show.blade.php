<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <flux:heading size="xl" level="1">{{ $order->order_number }}</flux:heading>
            <flux:badge size="sm" :color="match($order->financial_status->value) { 'paid' => 'green', 'pending' => 'zinc', 'refunded' => 'yellow', 'partially_refunded' => 'yellow', default => 'red' }">
                {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
            </flux:badge>
            <flux:badge size="sm" :color="match($order->fulfillment_status->value) { 'fulfilled' => 'green', 'partial' => 'yellow', 'unfulfilled' => 'zinc' }">
                {{ ucfirst($order->fulfillment_status->value) }}
            </flux:badge>
        </div>
        <div class="flex gap-2">
            @if($order->payment_method === \App\Enums\PaymentMethod::BankTransfer && $order->financial_status === \App\Enums\FinancialStatus::Pending)
                <flux:button variant="primary" wire:click="confirmPayment">Confirm payment</flux:button>
            @endif
            @if($order->financial_status === \App\Enums\FinancialStatus::Paid && $order->fulfillment_status !== \App\Enums\FulfillmentStatus::Fulfilled)
                <flux:modal.trigger name="create-fulfillment">
                    <flux:button variant="primary">Create fulfillment</flux:button>
                </flux:modal.trigger>
            @endif
            @if(in_array($order->financial_status, [\App\Enums\FinancialStatus::Paid, \App\Enums\FinancialStatus::PartiallyRefunded]))
                <flux:modal.trigger name="create-refund">
                    <flux:button variant="ghost">Refund</flux:button>
                </flux:modal.trigger>
            @endif
        </div>
    </div>

    @if($order->financial_status === \App\Enums\FinancialStatus::Pending && $order->payment_method !== \App\Enums\PaymentMethod::BankTransfer)
        <flux:callout variant="warning" class="mb-6">
            Cannot create fulfillment. Payment must be confirmed before items can be fulfilled.
        </flux:callout>
    @endif

    <flux:text class="mb-6 text-sm text-zinc-500">
        Placed {{ $order->placed_at ? \Carbon\Carbon::parse($order->placed_at)->format('M d, Y \a\t h:i A') : '-' }}
    </flux:text>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left Column (2/3) --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Line Items --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md" class="mb-4">Items</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Product</flux:table.column>
                        <flux:table.column>SKU</flux:table.column>
                        <flux:table.column>Qty</flux:table.column>
                        <flux:table.column>Unit Price</flux:table.column>
                        <flux:table.column>Total</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($order->lines as $line)
                            <flux:table.row>
                                <flux:table.cell variant="strong">{{ $line->title_snapshot }}</flux:table.cell>
                                <flux:table.cell>{{ $line->sku_snapshot ?? '-' }}</flux:table.cell>
                                <flux:table.cell>{{ $line->quantity }}</flux:table.cell>
                                <flux:table.cell>{{ $this->formatCurrency($line->unit_price_amount) }}</flux:table.cell>
                                <flux:table.cell variant="strong">{{ $this->formatCurrency($line->total_amount) }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                {{-- Summary --}}
                <div class="mt-4 space-y-1 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-700">
                    <div class="flex justify-between"><span class="text-zinc-500">Subtotal</span><span>{{ $this->formatCurrency($order->subtotal_amount) }}</span></div>
                    @if($order->discount_amount > 0)
                        <div class="flex justify-between"><span class="text-zinc-500">Discount</span><span class="text-red-500">-{{ $this->formatCurrency($order->discount_amount) }}</span></div>
                    @endif
                    <div class="flex justify-between"><span class="text-zinc-500">Shipping</span><span>{{ $this->formatCurrency($order->shipping_amount) }}</span></div>
                    <div class="flex justify-between"><span class="text-zinc-500">Tax</span><span>{{ $this->formatCurrency($order->tax_amount) }}</span></div>
                    <div class="flex justify-between border-t border-zinc-200 pt-1 font-semibold dark:border-zinc-700"><span>Total</span><span>{{ $this->formatCurrency($order->total_amount) }}</span></div>
                </div>
            </div>

            {{-- Fulfillments --}}
            @foreach($order->fulfillments as $fulfillment)
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <flux:heading size="md">Fulfillment #{{ $fulfillment->id }}</flux:heading>
                            <flux:badge size="sm" :color="match($fulfillment->status->value) { 'shipped' => 'blue', 'delivered' => 'green', default => 'zinc' }">
                                {{ ucfirst($fulfillment->status->value) }}
                            </flux:badge>
                        </div>
                        <div class="flex gap-2">
                            @if($fulfillment->status->value === 'pending')
                                <flux:button size="sm" wire:click="markAsShipped({{ $fulfillment->id }})">Mark as shipped</flux:button>
                            @elseif($fulfillment->status->value === 'shipped')
                                <flux:button size="sm" wire:click="markAsDelivered({{ $fulfillment->id }})">Mark as delivered</flux:button>
                            @endif
                        </div>
                    </div>
                    @if($fulfillment->tracking_company || $fulfillment->tracking_number)
                        <div class="mb-3 text-sm text-zinc-600 dark:text-zinc-400">
                            @if($fulfillment->tracking_company)<span>{{ $fulfillment->tracking_company }}</span>@endif
                            @if($fulfillment->tracking_number) - <span>{{ $fulfillment->tracking_number }}</span>@endif
                            @if($fulfillment->tracking_url) <a href="{{ $fulfillment->tracking_url }}" target="_blank" class="text-blue-500 hover:underline">Track</a>@endif
                        </div>
                    @endif
                    <div class="text-sm text-zinc-500">
                        @foreach($fulfillment->fulfillmentLines as $fl)
                            @php $ol = $order->lines->firstWhere('id', $fl->order_line_id); @endphp
                            <div>{{ $ol?->title_snapshot }} x {{ $fl->quantity }}</div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            {{-- Refunds --}}
            @if($order->refunds->count() > 0)
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="md" class="mb-4">Refunds</flux:heading>
                    @foreach($order->refunds as $refund)
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <div>
                                <span class="font-medium">{{ $this->formatCurrency($refund->amount) }}</span>
                                @if($refund->reason) <span class="text-zinc-500"> - {{ $refund->reason }}</span> @endif
                            </div>
                            <flux:badge size="sm" :color="$refund->status === 'processed' ? 'green' : 'red'">{{ ucfirst($refund->status) }}</flux:badge>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Timeline --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md" class="mb-4">Timeline</flux:heading>
                <div class="space-y-4">
                    @if($order->placed_at)
                        <div class="flex gap-3">
                            <div class="mt-1 h-2 w-2 shrink-0 rounded-full bg-blue-500"></div>
                            <div>
                                <flux:text class="text-sm font-medium">Order placed</flux:text>
                                <flux:text class="text-xs text-zinc-500">{{ \Carbon\Carbon::parse($order->placed_at)->format('M d, Y h:i A') }}</flux:text>
                            </div>
                        </div>
                    @endif
                    @foreach($order->payments as $payment)
                        @if($payment->status === \App\Enums\PaymentStatus::Captured)
                            <div class="flex gap-3">
                                <div class="mt-1 h-2 w-2 shrink-0 rounded-full bg-green-500"></div>
                                <div>
                                    <flux:text class="text-sm font-medium">Payment received</flux:text>
                                    <flux:text class="text-xs text-zinc-500">{{ $payment->created_at ? \Carbon\Carbon::parse($payment->created_at)->format('M d, Y h:i A') : '' }}</flux:text>
                                </div>
                            </div>
                        @endif
                    @endforeach
                    @foreach($order->fulfillments as $f)
                        <div class="flex gap-3">
                            <div class="mt-1 h-2 w-2 shrink-0 rounded-full bg-purple-500"></div>
                            <div>
                                <flux:text class="text-sm font-medium">Fulfillment created</flux:text>
                                <flux:text class="text-xs text-zinc-500">{{ $f->created_at ? \Carbon\Carbon::parse($f->created_at)->format('M d, Y h:i A') : '' }}</flux:text>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right Column (1/3) --}}
        <div class="space-y-6">
            {{-- Customer --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md" class="mb-3">Customer</flux:heading>
                @if($order->customer)
                    <flux:text class="font-medium">{{ $order->customer->name }}</flux:text>
                    <flux:text class="text-sm text-zinc-500">{{ $order->customer->email }}</flux:text>
                    <a href="{{ route('admin.customers.show', $order->customer) }}" class="mt-2 block text-sm text-blue-600 hover:underline dark:text-blue-400" wire:navigate>View customer</a>
                @else
                    <flux:text class="text-zinc-500">Guest</flux:text>
                @endif
            </div>

            {{-- Payment --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md" class="mb-3">Payment</flux:heading>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between"><span class="text-zinc-500">Method</span><span>{{ ucfirst(str_replace('_', ' ', $order->payment_method?->value ?? '-')) }}</span></div>
                    @if($order->payments->first())
                        <div class="flex justify-between"><span class="text-zinc-500">Status</span><span>{{ ucfirst($order->payments->first()->status->value) }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-500">Amount</span><span>{{ $this->formatCurrency($order->payments->first()->amount) }}</span></div>
                        @if($order->payments->first()->provider_payment_id)
                            <div class="flex justify-between"><span class="text-zinc-500">Reference</span><span class="truncate max-w-32">{{ $order->payments->first()->provider_payment_id }}</span></div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Shipping Address --}}
            @if($order->shipping_address_json)
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="md" class="mb-3">Shipping address</flux:heading>
                    @php $addr = $order->shipping_address_json; @endphp
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        <div>{{ data_get($addr, 'first_name') }} {{ data_get($addr, 'last_name') }}</div>
                        <div>{{ data_get($addr, 'address1') }}</div>
                        <div>{{ data_get($addr, 'city') }}, {{ data_get($addr, 'zip') }}</div>
                        <div>{{ data_get($addr, 'country') }}</div>
                    </div>
                </div>
            @endif

            {{-- Billing Address --}}
            @if($order->billing_address_json)
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="md" class="mb-3">Billing address</flux:heading>
                    @php $addr = $order->billing_address_json; @endphp
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        <div>{{ data_get($addr, 'first_name') }} {{ data_get($addr, 'last_name') }}</div>
                        <div>{{ data_get($addr, 'address1') }}</div>
                        <div>{{ data_get($addr, 'city') }}, {{ data_get($addr, 'zip') }}</div>
                        <div>{{ data_get($addr, 'country') }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Fulfillment Modal --}}
    <flux:modal name="create-fulfillment" class="md:w-[32rem]">
        <div class="space-y-6">
            <flux:heading size="lg">Create fulfillment</flux:heading>
            <div class="space-y-3">
                @foreach($order->lines as $line)
                    @php
                        $fulfilled = $line->fulfillmentLines()->sum('quantity');
                        $remaining = $line->quantity - $fulfilled;
                    @endphp
                    @if($remaining > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-sm">{{ $line->title_snapshot }} ({{ $remaining }} unfulfilled)</span>
                            <input type="number" wire:model="fulfillLines.{{ $line->id }}" min="0" max="{{ $remaining }}" class="w-16 rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                        </div>
                    @endif
                @endforeach
            </div>
            <flux:input wire:model="trackingCompany" label="Tracking company" placeholder="DHL" />
            <flux:input wire:model="trackingNumber" label="Tracking number" placeholder="DHL123456789" />
            <flux:input wire:model="trackingUrl" label="Tracking URL" placeholder="https://..." />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="createFulfillment">Create fulfillment</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Refund Modal --}}
    <flux:modal name="create-refund" class="md:w-96">
        <div class="space-y-6">
            <flux:heading size="lg">Refund order</flux:heading>
            <flux:input wire:model="refundAmount" label="Amount" type="number" step="0.01" min="0.01" placeholder="10.00" />
            <flux:textarea wire:model="refundReason" label="Reason" placeholder="Customer requested partial refund" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="createRefund">Create refund</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
