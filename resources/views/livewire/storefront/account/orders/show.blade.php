<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'My Account', 'url' => route('customer.dashboard')],
        ['label' => 'Orders', 'url' => route('customer.orders')],
        ['label' => $order->order_number],
    ]" class="mb-8" />

    <div class="lg:grid lg:grid-cols-12 lg:gap-x-8">
        {{-- Sidebar --}}
        <aside class="lg:col-span-3 mb-8 lg:mb-0">
            @include('livewire.storefront.account.partials.sidebar')
        </aside>

        {{-- Main Content --}}
        <div class="lg:col-span-9">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Order {{ $order->order_number }}</h1>
                <div class="flex items-center gap-2">
                    @include('livewire.storefront.account.partials.order-status-badge', ['status' => $order->status])
                    @include('livewire.storefront.account.partials.financial-status-badge', ['status' => $order->financial_status])
                </div>
            </div>

            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Placed on {{ $order->placed_at?->format('F j, Y \a\t g:i A') }}
            </p>

            {{-- Line Items --}}
            <div class="mt-8">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Items</h2>
                <div class="mt-4 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Product</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">SKU</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Qty</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Unit Price</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                            @foreach($order->lines as $line)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">
                                        {{ $line->title_snapshot }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $line->sku_snapshot ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $line->quantity }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-zinc-900 dark:text-white">
                                        <x-storefront.price :amount="$line->unit_price_amount" :currency="$order->currency" />
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-zinc-900 dark:text-white">
                                        <x-storefront.price :amount="$line->total_amount" :currency="$order->currency" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Order Totals --}}
            <div class="mt-6 rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Order Summary</h2>
                <dl class="mt-4 space-y-3">
                    <div class="flex justify-between text-sm">
                        <dt class="text-zinc-600 dark:text-zinc-400">Subtotal</dt>
                        <dd class="text-zinc-900 dark:text-white">
                            <x-storefront.price :amount="$order->subtotal_amount" :currency="$order->currency" />
                        </dd>
                    </div>
                    @if($order->discount_amount > 0)
                        <div class="flex justify-between text-sm">
                            <dt class="text-zinc-600 dark:text-zinc-400">Discount</dt>
                            <dd class="text-red-600 dark:text-red-400">
                                -<x-storefront.price :amount="$order->discount_amount" :currency="$order->currency" />
                            </dd>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <dt class="text-zinc-600 dark:text-zinc-400">Shipping</dt>
                        <dd class="text-zinc-900 dark:text-white">
                            <x-storefront.price :amount="$order->shipping_amount" :currency="$order->currency" />
                        </dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-zinc-600 dark:text-zinc-400">Tax</dt>
                        <dd class="text-zinc-900 dark:text-white">
                            <x-storefront.price :amount="$order->tax_amount" :currency="$order->currency" />
                        </dd>
                    </div>
                    <div class="flex justify-between border-t border-zinc-200 pt-3 text-base font-semibold dark:border-zinc-700">
                        <dt class="text-zinc-900 dark:text-white">Total</dt>
                        <dd class="text-zinc-900 dark:text-white">
                            <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" />
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Payment Info --}}
            @if($order->payments->isNotEmpty())
                <div class="mt-6 rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Payment</h2>
                    @foreach($order->payments as $payment)
                        <div class="mt-3 flex items-center justify-between text-sm">
                            <div class="text-zinc-600 dark:text-zinc-400">
                                {{ ucwords(str_replace('_', ' ', $payment->method->value)) }}
                            </div>
                            <div class="flex items-center gap-2">
                                <x-storefront.price :amount="$payment->amount" :currency="$payment->currency" />
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $payment->status === \App\Enums\PaymentStatus::Captured ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
                                    {{ ucfirst($payment->status->value) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Fulfillment Info --}}
            @if($order->fulfillments->isNotEmpty())
                <div class="mt-6 rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Fulfillment</h2>
                    @foreach($order->fulfillments as $fulfillment)
                        <div class="mt-3 space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Status</span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $fulfillment->status === \App\Enums\FulfillmentShipmentStatus::Shipped ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : ($fulfillment->status === \App\Enums\FulfillmentShipmentStatus::Delivered ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400') }}">
                                    {{ ucfirst($fulfillment->status->value) }}
                                </span>
                            </div>
                            @if($fulfillment->tracking_number)
                                <div class="flex items-center justify-between">
                                    <span class="text-zinc-600 dark:text-zinc-400">Tracking Number</span>
                                    <span class="text-zinc-900 dark:text-white">
                                        @if($fulfillment->tracking_url)
                                            <a href="{{ $fulfillment->tracking_url }}" target="_blank" rel="noopener noreferrer" class="underline hover:no-underline">
                                                {{ $fulfillment->tracking_number }}
                                            </a>
                                        @else
                                            {{ $fulfillment->tracking_number }}
                                        @endif
                                    </span>
                                </div>
                            @endif
                            @if($fulfillment->shipped_at)
                                <div class="flex items-center justify-between">
                                    <span class="text-zinc-600 dark:text-zinc-400">Shipped Date</span>
                                    <span class="text-zinc-900 dark:text-white">{{ $fulfillment->shipped_at->format('M d, Y') }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-6">
                <a href="{{ route('customer.orders') }}"
                   wire:navigate
                   class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                    &larr; Back to orders
                </a>
            </div>
        </div>
    </div>
</div>
