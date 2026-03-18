<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Order {{ $this->order->order_number }}</h1>

    <div class="mt-6 flex flex-col gap-8 lg:flex-row">
        @include('livewire.storefront.account.partials.account-nav')

        <div class="flex-1 space-y-6">
            {{-- Order status --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                        {{ match($this->order->status) {
                            \App\Enums\OrderStatus::Paid => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                            \App\Enums\OrderStatus::Fulfilled => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                            \App\Enums\OrderStatus::Cancelled => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                            \App\Enums\OrderStatus::Refunded => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                            default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                        } }}">
                        {{ ucfirst($this->order->status->value) }}
                    </span>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                        {{ match($this->order->fulfillment_status) {
                            \App\Enums\FulfillmentStatus::Fulfilled => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                            \App\Enums\FulfillmentStatus::Partial => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                            default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                        } }}">
                        {{ ucfirst($this->order->fulfillment_status->value) }}
                    </span>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">
                        Placed {{ $this->order->placed_at?->format('M d, Y \a\t g:i A') }}
                    </span>
                </div>
            </div>

            {{-- Line items --}}
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">Items</h2>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($this->order->lines as $line)
                        <div class="flex items-start justify-between px-6 py-4">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $line->title_snapshot }}</p>
                                @if($line->variant_title_snapshot)
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $line->variant_title_snapshot }}</p>
                                @endif
                                @if($line->sku_snapshot)
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">SKU: {{ $line->sku_snapshot }}</p>
                                @endif
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Qty: {{ $line->quantity }}</p>
                            </div>
                            <div class="text-right">
                                <x-storefront.price :amount="$line->total_amount" :currency="$this->order->currency" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Totals --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="font-semibold text-zinc-900 dark:text-white">Order Summary</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">Subtotal</dt>
                        <dd><x-storefront.price :amount="$this->order->subtotal_amount" :currency="$this->order->currency" /></dd>
                    </div>
                    @if($this->order->discount_amount > 0)
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">Discount</dt>
                            <dd class="text-red-600 dark:text-red-400">-<x-storefront.price :amount="$this->order->discount_amount" :currency="$this->order->currency" /></dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">Shipping</dt>
                        <dd><x-storefront.price :amount="$this->order->shipping_amount" :currency="$this->order->currency" /></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">Tax</dt>
                        <dd><x-storefront.price :amount="$this->order->tax_amount" :currency="$this->order->currency" /></dd>
                    </div>
                    <div class="flex justify-between border-t border-zinc-200 pt-2 dark:border-zinc-700">
                        <dt class="font-semibold text-zinc-900 dark:text-white">Total</dt>
                        <dd><x-storefront.price :amount="$this->order->total_amount" :currency="$this->order->currency" size="md" /></dd>
                    </div>
                </dl>
            </div>

            {{-- Payment info --}}
            @if($this->order->payments->isNotEmpty())
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">Payment</h2>
                    <div class="mt-4 space-y-3">
                        @foreach($this->order->payments as $payment)
                            <div class="flex items-center justify-between text-sm">
                                <div>
                                    <span class="text-zinc-900 dark:text-white">{{ ucfirst($payment->method->value) }}</span>
                                    <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ match($payment->status) {
                                            \App\Enums\PaymentStatus::Captured => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                            \App\Enums\PaymentStatus::Refunded => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                            default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                                        } }}">
                                        {{ ucfirst($payment->status->value) }}
                                    </span>
                                </div>
                                <x-storefront.price :amount="$payment->amount" :currency="$payment->currency" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Fulfillment tracking --}}
            @if($this->order->fulfillments->isNotEmpty())
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">Shipping</h2>
                    <div class="mt-4 space-y-4">
                        @foreach($this->order->fulfillments as $fulfillment)
                            <div class="text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ match($fulfillment->status) {
                                            \App\Enums\FulfillmentShipmentStatus::Delivered => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                            \App\Enums\FulfillmentShipmentStatus::Shipped => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                            default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                                        } }}">
                                        {{ ucfirst(str_replace('_', ' ', $fulfillment->status->value)) }}
                                    </span>
                                </div>
                                @if($fulfillment->tracking_company || $fulfillment->tracking_number)
                                    <p class="mt-1 text-zinc-500 dark:text-zinc-400">
                                        @if($fulfillment->tracking_company)
                                            {{ $fulfillment->tracking_company }}
                                        @endif
                                        @if($fulfillment->tracking_number)
                                            @if($fulfillment->tracking_url)
                                                - <a href="{{ $fulfillment->tracking_url }}" target="_blank" rel="noopener" class="text-zinc-700 underline hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">{{ $fulfillment->tracking_number }}</a>
                                            @else
                                                - {{ $fulfillment->tracking_number }}
                                            @endif
                                        @endif
                                    </p>
                                @endif
                                @if($fulfillment->shipped_at)
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">Shipped {{ $fulfillment->shipped_at->format('M d, Y') }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Shipping address --}}
            @if($this->order->shipping_address_json)
                @php $addr = $this->order->shipping_address_json; @endphp
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">Shipping Address</h2>
                    <address class="mt-3 text-sm not-italic text-zinc-600 dark:text-zinc-400">
                        {{ $addr['first_name'] ?? '' }} {{ $addr['last_name'] ?? '' }}<br>
                        {{ $addr['address1'] ?? '' }}<br>
                        @if(!empty($addr['address2'])){{ $addr['address2'] }}<br>@endif
                        {{ $addr['postal_code'] ?? '' }} {{ $addr['city'] ?? '' }}<br>
                        {{ $addr['country_code'] ?? '' }}
                    </address>
                </div>
            @endif

            <div class="pb-4">
                <a href="{{ route('storefront.account.orders') }}"
                   class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                    &larr; Back to orders
                </a>
            </div>
        </div>
    </div>
</div>
