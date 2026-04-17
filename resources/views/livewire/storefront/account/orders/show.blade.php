@php
    $store = app()->bound('current_store') ? app('current_store') : null;
    $currency = $store?->default_currency ?? 'EUR';
    $shipping = $order->shipping_address_json ?? [];
    $billing = $order->billing_address_json ?? [];
@endphp

<div>
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront.breadcrumbs :items="[
            ['label' => 'Account', 'url' => route('storefront.account.dashboard')],
            ['label' => 'Orders', 'url' => route('storefront.account.orders')],
            ['label' => $order->order_number],
        ]" />

        {{-- Header --}}
        <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Order {{ $order->order_number }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Placed on {{ $order->placed_at?->format('F d, Y') }}
                </p>
            </div>
            <div class="flex gap-2">
                <x-storefront.badge :variant="match($order->status->value) {
                    'pending' => 'warning',
                    'paid' => 'success',
                    'fulfilled' => 'info',
                    'cancelled' => 'default',
                    'refunded' => 'sale',
                    default => 'default',
                }">{{ ucfirst($order->status->value) }}</x-storefront.badge>
                <x-storefront.badge :variant="match($order->fulfillment_status->value) {
                    'unfulfilled' => 'warning',
                    'partial' => 'info',
                    'fulfilled' => 'success',
                    default => 'default',
                }">{{ ucfirst(str_replace('_', ' ', $order->fulfillment_status->value)) }}</x-storefront.badge>
            </div>
        </div>

        {{-- Items --}}
        <div class="mt-8 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Items</h2>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach($order->lines as $line)
                    <div wire:key="line-{{ $line->id }}" class="flex items-center gap-4 px-4 py-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-md bg-gray-100 dark:bg-gray-800">
                            <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $line->title }}</p>
                            @if($line->variant_title)
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $line->variant_title }}</p>
                            @endif
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">x{{ $line->quantity }}</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            <x-storefront.price :amount="$line->total_amount" :currency="$currency" />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Address & Payment Info --}}
        <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-3">
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Shipping Address</h3>
                <div class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    @if(! empty($shipping['first_name']) || ! empty($shipping['last_name']))
                        <p>{{ ($shipping['first_name'] ?? '').' '.($shipping['last_name'] ?? '') }}</p>
                    @endif
                    @if(! empty($shipping['address1']))
                        <p>{{ $shipping['address1'] }}</p>
                    @endif
                    @if(! empty($shipping['address2']))
                        <p>{{ $shipping['address2'] }}</p>
                    @endif
                    <p>{{ ($shipping['city'] ?? '').', '.($shipping['postal_code'] ?? $shipping['zip'] ?? '') }}</p>
                    <p>{{ $shipping['country'] ?? '' }}</p>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Billing Address</h3>
                <div class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    @if($billing === $shipping || empty($billing))
                        <p>Same as shipping</p>
                    @else
                        @if(! empty($billing['first_name']) || ! empty($billing['last_name']))
                            <p>{{ ($billing['first_name'] ?? '').' '.($billing['last_name'] ?? '') }}</p>
                        @endif
                        @if(! empty($billing['address1']))
                            <p>{{ $billing['address1'] }}</p>
                        @endif
                        <p>{{ ($billing['city'] ?? '').', '.($billing['postal_code'] ?? $billing['zip'] ?? '') }}</p>
                        <p>{{ $billing['country'] ?? '' }}</p>
                    @endif
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Payment</h3>
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    <p>{{ ucfirst(str_replace('_', ' ', $order->payment_method->value)) }}</p>
                </div>
            </div>
        </div>

        {{-- Order Totals --}}
        <div class="mt-8 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                    <span class="text-gray-900 dark:text-white"><x-storefront.price :amount="$order->subtotal_amount" :currency="$currency" /></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Shipping</span>
                    <span class="text-gray-900 dark:text-white"><x-storefront.price :amount="$order->shipping_amount" :currency="$currency" /></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Tax</span>
                    <span class="text-gray-900 dark:text-white"><x-storefront.price :amount="$order->tax_amount" :currency="$currency" /></span>
                </div>
                @if($order->discount_amount > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Discount</span>
                        <span class="text-red-600 dark:text-red-400">-<x-storefront.price :amount="$order->discount_amount" :currency="$currency" /></span>
                    </div>
                @endif
                <div class="border-t border-gray-200 pt-2 dark:border-gray-800">
                    <div class="flex justify-between text-sm font-semibold">
                        <span class="text-gray-900 dark:text-white">Total</span>
                        <span class="text-gray-900 dark:text-white"><x-storefront.price :amount="$order->total_amount" :currency="$currency" /></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Fulfillments --}}
        @if($order->fulfillments->isNotEmpty())
            <div class="mt-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Fulfillment</h2>
                @foreach($order->fulfillments as $fulfillment)
                    <div wire:key="fulfillment-{{ $fulfillment->id }}" class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="text-gray-500 dark:text-gray-400">
                                Shipped via {{ $fulfillment->tracking_company ?? 'carrier' }}
                            </span>
                            @if($fulfillment->tracking_number)
                                <span class="font-mono text-gray-900 dark:text-white">{{ $fulfillment->tracking_number }}</span>
                            @endif
                        </div>
                        @if($fulfillment->tracking_url)
                            <a href="{{ $fulfillment->tracking_url }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                Track shipment
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
