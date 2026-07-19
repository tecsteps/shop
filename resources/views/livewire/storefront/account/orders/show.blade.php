@php
    use App\Enums\FinancialStatus;
    use App\Enums\FulfillmentShipmentStatus;
    use App\Enums\PaymentMethod;
    use App\Enums\PaymentStatus;
    use App\Support\Money;

    $paymentMethodLabels = [
        PaymentMethod::CreditCard->value => 'Credit card',
        PaymentMethod::Paypal->value => 'PayPal',
        PaymentMethod::BankTransfer->value => 'Bank transfer',
    ];

    $formatAddress = function (?array $address): array {
        if (empty($address)) {
            return [];
        }

        return array_values(array_filter([
            trim(($address['first_name'] ?? '').' '.($address['last_name'] ?? '')),
            $address['company'] ?? null,
            $address['address1'] ?? null,
            $address['address2'] ?? null,
            trim(($address['city'] ?? '').' '.($address['province_code'] ?? $address['province'] ?? '').' '.($address['postal_code'] ?? '')),
            $address['country'] ?? $address['country_code'] ?? null,
            $address['phone'] ?? null,
        ]));
    };

    $shippingAddress = $formatAddress($order->shipping_address_json);
    $billingAddress = $formatAddress($order->billing_address_json);

    $payment = $order->payments->firstWhere('status', PaymentStatus::Captured) ?? $order->payments->first();

    $isPaid = in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded, FinancialStatus::Refunded], true);
    $shippedAt = $order->fulfillments->whereNotNull('shipped_at')->min('shipped_at');
    $deliveredAt = $order->fulfillments->where('status', FulfillmentShipmentStatus::Delivered)->min('created_at');

    $timeline = [
        ['label' => 'Placed', 'done' => $order->placed_at !== null, 'at' => $order->placed_at],
        ['label' => 'Paid', 'done' => $isPaid, 'at' => $payment?->created_at],
        ['label' => 'Shipped', 'done' => $shippedAt !== null, 'at' => $shippedAt],
        ['label' => 'Delivered', 'done' => $deliveredAt !== null, 'at' => $deliveredAt],
    ];
@endphp

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <x-storefront::breadcrumbs :items="[
        ['label' => 'Account', 'url' => route('storefront.account.dashboard')],
        ['label' => 'Orders', 'url' => route('storefront.account.orders.index')],
        ['label' => $order->order_number],
    ]" class="mb-6" />

    {{-- Header (spec 04 §10.5) --}}
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Order {{ $order->order_number }}</h1>
        <div class="flex items-center gap-1.5">
            <x-storefront::order-status-badge :status="$order->financial_status->value" />
            <x-storefront::order-status-badge :status="$order->fulfillment_status->value" />
        </div>
    </div>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Placed on {{ $order->placed_at?->format('F j, Y') }}</p>

    {{-- Timeline: placed -> paid -> shipped -> delivered --}}
    <ol class="mt-8 flex flex-wrap items-center gap-y-3" aria-label="Order progress">
        @foreach ($timeline as $index => $step)
            <li class="flex items-center">
                @if ($index > 0)
                    <span class="mx-3 h-px w-8 sm:w-12 {{ $step['done'] ? 'bg-blue-600 dark:bg-blue-500' : 'bg-gray-300 dark:bg-gray-700' }}" aria-hidden="true"></span>
                @endif
                <span class="flex items-center gap-2">
                    @if ($step['done'])
                        <svg class="size-5 text-blue-600 dark:text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @else
                        <svg class="size-5 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif
                    <span class="text-sm font-medium {{ $step['done'] ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                        {{ $step['label'] }}
                        @if ($step['done'] && $step['at'] !== null)
                            <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::parse($step['at'])->format('M j, Y') }}</span>
                        @endif
                    </span>
                </span>
            </li>
        @endforeach
    </ol>

    {{-- Items --}}
    <div class="mt-10">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Items</h2>
        <table class="mt-4 w-full">
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach ($order->lines as $line)
                    <tr wire:key="order-line-{{ $line->id }}">
                        <td class="py-4 pr-4">
                            <div class="flex items-center gap-4">
                                @php $image = $line->variant?->product?->media->first(); @endphp
                                @if ($image !== null)
                                    <img src="{{ $image->url() }}" alt="" class="size-14 shrink-0 rounded-md object-cover">
                                @else
                                    <div class="flex size-14 shrink-0 items-center justify-center rounded-md bg-gray-100 dark:bg-gray-800" aria-hidden="true">
                                        <svg class="size-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z" />
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $line->title_snapshot }}</p>
                                    @if ($line->sku_snapshot)
                                        <p class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $line->sku_snapshot }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="py-4 pr-4 text-sm whitespace-nowrap text-gray-600 dark:text-gray-300">&times;{{ $line->quantity }}</td>
                        <td class="py-4 text-right text-sm text-gray-900 dark:text-white">{{ Money::format($line->total_amount, $order->currency) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Info grid: shipping / billing / payment --}}
    <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-3">
        <div>
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Shipping address</h2>
            <address class="mt-2 text-sm not-italic text-gray-600 dark:text-gray-300">
                @forelse ($shippingAddress as $line)
                    {{ $line }}<br>
                @empty
                    <span class="text-gray-400 dark:text-gray-500">No shipping address</span>
                @endforelse
            </address>
        </div>
        <div>
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Billing address</h2>
            <address class="mt-2 text-sm not-italic text-gray-600 dark:text-gray-300">
                @if ($billingAddress === $shippingAddress && $billingAddress !== [])
                    <span class="text-gray-400 dark:text-gray-500">Same as shipping</span>
                @else
                    @forelse ($billingAddress as $line)
                        {{ $line }}<br>
                    @empty
                        <span class="text-gray-400 dark:text-gray-500">No billing address</span>
                    @endforelse
                @endif
            </address>
        </div>
        <div>
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Payment</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                {{ $paymentMethodLabels[$order->payment_method->value] ?? $order->payment_method->value }}
                @if ($payment !== null)
                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                        {{ Money::format($payment->amount, $payment->currency) }} &middot; {{ str_replace('_', ' ', $payment->status->value) }}
                    </span>
                @endif
            </p>
        </div>
    </div>

    {{-- Totals --}}
    <dl class="mt-10 space-y-2 border-t border-gray-200 pt-6 text-sm dark:border-gray-800 sm:ml-auto sm:max-w-xs">
        <div class="flex justify-between">
            <dt class="text-gray-600 dark:text-gray-300">Subtotal</dt>
            <dd class="text-gray-900 dark:text-white">{{ Money::format($order->subtotal_amount, $order->currency) }}</dd>
        </div>
        <div class="flex justify-between">
            <dt class="text-gray-600 dark:text-gray-300">Shipping</dt>
            <dd class="text-gray-900 dark:text-white">{{ Money::format($order->shipping_amount, $order->currency) }}</dd>
        </div>
        <div class="flex justify-between">
            <dt class="text-gray-600 dark:text-gray-300">Tax</dt>
            <dd class="text-gray-900 dark:text-white">{{ Money::format($order->tax_amount, $order->currency) }}</dd>
        </div>
        <div class="flex justify-between">
            <dt class="text-gray-600 dark:text-gray-300">Discount</dt>
            <dd class="text-gray-900 dark:text-white">-{{ Money::format($order->discount_amount, $order->currency) }}</dd>
        </div>
        <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-semibold dark:border-gray-800">
            <dt class="text-gray-900 dark:text-white">Total</dt>
            <dd class="text-gray-900 dark:text-white">{{ Money::format($order->total_amount, $order->currency) }}</dd>
        </div>
    </dl>

    {{-- Fulfillment / tracking (only when fulfillments exist) --}}
    @if ($order->fulfillments->isNotEmpty())
        <div class="mt-10">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Fulfillment</h2>
            <div class="mt-4 space-y-3">
                @foreach ($order->fulfillments as $fulfillment)
                    <div wire:key="fulfillment-{{ $fulfillment->id }}" class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                        <p class="text-sm text-gray-900 dark:text-white">
                            <span class="capitalize">{{ $fulfillment->status->value }}</span>
                            @if ($fulfillment->tracking_company)
                                via {{ $fulfillment->tracking_company }}
                            @endif
                            @if ($fulfillment->tracking_number)
                                &middot; {{ $fulfillment->tracking_number }}
                            @endif
                        </p>
                        @if ($fulfillment->tracking_url)
                            <a href="{{ $fulfillment->tracking_url }}" target="_blank" rel="noopener noreferrer"
                               class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:underline focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-blue-400">
                                Track shipment
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
