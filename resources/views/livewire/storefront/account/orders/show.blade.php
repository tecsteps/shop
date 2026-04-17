<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('storefront.account') }}">Account</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('storefront.account.orders') }}">Orders</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $order->order_number }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    {{-- Header --}}
    <div class="flex flex-wrap items-center gap-3">
        <flux:heading size="xl">Order {{ $order->order_number }}</flux:heading>
        @php
            $statusColor = match($order->status) {
                \App\Enums\OrderStatus::Pending => 'yellow',
                \App\Enums\OrderStatus::Paid => 'green',
                \App\Enums\OrderStatus::Fulfilled => 'blue',
                \App\Enums\OrderStatus::Cancelled => 'zinc',
                \App\Enums\OrderStatus::Refunded => 'red',
            };
            $financialColor = match($order->financial_status) {
                \App\Enums\FinancialStatus::Pending => 'yellow',
                \App\Enums\FinancialStatus::Authorized => 'blue',
                \App\Enums\FinancialStatus::Paid => 'green',
                \App\Enums\FinancialStatus::PartiallyRefunded => 'yellow',
                \App\Enums\FinancialStatus::Refunded => 'red',
                \App\Enums\FinancialStatus::Voided => 'zinc',
            };
        @endphp
        <flux:badge color="{{ $statusColor }}" size="sm">{{ ucfirst($order->status->value) }}</flux:badge>
        <flux:badge color="{{ $financialColor }}" size="sm">{{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}</flux:badge>
    </div>
    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
        Placed on {{ \Carbon\Carbon::parse($order->placed_at)->format('F j, Y') }}
    </p>

    {{-- Line Items --}}
    <div class="mt-8">
        <flux:heading size="lg">Items</flux:heading>
        <div class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($order->lines as $line)
                <div class="flex items-start justify-between py-4" wire:key="line-{{ $line->id }}">
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $line->title_snapshot }}</p>
                        @if($line->sku_snapshot)
                            <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">SKU: {{ $line->sku_snapshot }}</p>
                        @endif
                        <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">Qty: {{ $line->quantity }}</p>
                    </div>
                    <p class="font-medium text-zinc-900 dark:text-white">${{ number_format($line->total_amount / 100, 2) }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Price Breakdown --}}
    <div class="mt-6 border-t border-zinc-200 pt-4 dark:border-zinc-700">
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-zinc-600 dark:text-zinc-400">Subtotal</span>
                <span class="text-zinc-900 dark:text-white">${{ number_format($order->subtotal_amount / 100, 2) }}</span>
            </div>
            @if($order->discount_amount > 0)
                <div class="flex justify-between">
                    <span class="text-zinc-600 dark:text-zinc-400">Discount</span>
                    <span class="text-red-600">-${{ number_format($order->discount_amount / 100, 2) }}</span>
                </div>
            @endif
            <div class="flex justify-between">
                <span class="text-zinc-600 dark:text-zinc-400">Shipping</span>
                <span class="text-zinc-900 dark:text-white">${{ number_format($order->shipping_amount / 100, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-zinc-600 dark:text-zinc-400">Tax</span>
                <span class="text-zinc-900 dark:text-white">${{ number_format($order->tax_amount / 100, 2) }}</span>
            </div>
            <div class="flex justify-between border-t border-zinc-200 pt-2 font-semibold dark:border-zinc-700">
                <span class="text-zinc-900 dark:text-white">Total</span>
                <span class="text-zinc-900 dark:text-white">${{ number_format($order->total_amount / 100, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Shipping & Payment --}}
    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
        {{-- Shipping Address --}}
        @if($order->shipping_address_json)
            <div>
                <flux:heading size="lg">Shipping Address</flux:heading>
                <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    <p>{{ data_get($order->shipping_address_json, 'first_name') }} {{ data_get($order->shipping_address_json, 'last_name') }}</p>
                    <p>{{ data_get($order->shipping_address_json, 'address1') }}</p>
                    @if(data_get($order->shipping_address_json, 'address2'))
                        <p>{{ data_get($order->shipping_address_json, 'address2') }}</p>
                    @endif
                    <p>{{ data_get($order->shipping_address_json, 'city') }}, {{ data_get($order->shipping_address_json, 'zip') }}</p>
                    <p>{{ data_get($order->shipping_address_json, 'country') }}</p>
                </div>
            </div>
        @endif

        {{-- Payment Info --}}
        <div>
            <flux:heading size="lg">Payment</flux:heading>
            <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                <p>{{ ucfirst(str_replace('_', ' ', $order->payment_method->value)) }}</p>
            </div>
        </div>
    </div>

    {{-- Fulfillments --}}
    @if($order->fulfillments->isNotEmpty())
        <div class="mt-8">
            <flux:heading size="lg">Shipments</flux:heading>
            <div class="mt-4 space-y-4">
                @foreach($order->fulfillments as $fulfillment)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700" wire:key="fulfillment-{{ $fulfillment->id }}">
                        @if($fulfillment->tracking_company)
                            <p class="font-medium text-zinc-900 dark:text-white">Shipped via {{ $fulfillment->tracking_company }}</p>
                        @endif
                        @if($fulfillment->tracking_number)
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                Tracking: {{ $fulfillment->tracking_number }}
                            </p>
                        @endif
                        @if($fulfillment->tracking_url)
                            <a href="{{ $fulfillment->tracking_url }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="mt-2 inline-block text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">
                                Track shipment &rarr;
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
