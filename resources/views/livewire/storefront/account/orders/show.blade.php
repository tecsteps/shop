@php
    use App\Enums\PaymentMethod;

    $shippingAddress = $order->shipping_address_json ?? [];
    $billingAddress = $order->billing_address_json;

    $paymentLabel = match ($order->payment_method) {
        PaymentMethod::CreditCard => __('Credit card'),
        PaymentMethod::Paypal => __('PayPal'),
        PaymentMethod::BankTransfer => __('Bank transfer'),
        default => null,
    };
@endphp

<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs
        :items="[
            ['label' => __('Account'), 'url' => route('storefront.account.index')],
            ['label' => __('Orders'), 'url' => route('storefront.account.orders.index')],
            ['label' => $order->order_number],
        ]"
    />

    {{-- Header --}}
    <div class="mt-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                {{ __('Order :number', ['number' => $order->order_number]) }}
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Placed on :date', ['date' => $order->placed_at?->format('F j, Y')]) }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-storefront.order-status-badge :status="$order->status" />
            <x-storefront.order-status-badge :status="$order->fulfillment_status" />
        </div>
    </div>

    {{-- Timeline --}}
    @if ($timeline !== [])
        <section class="mt-8 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800" aria-labelledby="order-timeline-heading">
            <h2 id="order-timeline-heading" class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Order timeline') }}</h2>
            <ol class="mt-4 space-y-0">
                @foreach ($timeline as $event)
                    <li class="relative flex gap-4 pb-6 last:pb-0">
                        @unless ($loop->last)
                            <span class="absolute top-3 left-[5px] h-full w-px bg-zinc-200 dark:bg-zinc-700" aria-hidden="true"></span>
                        @endunless
                        <span class="relative mt-1.5 size-[11px] shrink-0 rounded-full border-2 border-white bg-(--sf-primary,#2563eb) ring-1 ring-zinc-200 dark:border-zinc-950 dark:ring-zinc-700" aria-hidden="true"></span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $event['label'] }}</p>
                            @if (filled($event['description']))
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $event['description'] }}</p>
                            @endif
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">
                                <time datetime="{{ $event['timestamp']->toIso8601String() }}">{{ $event['timestamp']->format('M j, Y, H:i') }}</time>
                            </p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    {{-- Items --}}
    <section class="mt-6 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800" aria-labelledby="order-items-heading">
        <h2 id="order-items-heading" class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Items') }}</h2>
        <ul class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800" role="list">
            @foreach ($lines as $line)
                <li class="flex items-center gap-4 py-4">
                    <div class="size-16 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-900">
                        @if (filled($line['image_url']))
                            <img src="{{ $line['image_url'] }}" alt="" class="size-full object-cover" loading="lazy" />
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $line['title'] }}</p>
                        @if ($line['variant_label'] !== '')
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $line['variant_label'] }}</p>
                        @endif
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Qty :quantity', ['quantity' => $line['quantity']]) }}
                            &times;
                            {{ \App\Support\Storefront\PriceFormatter::format($line['unit_price_amount'], $order->currency) }}
                        </p>
                    </div>
                    <x-storefront.price :amount="$line['total_amount']" :currency="$order->currency" class="text-sm" />
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Addresses and payment --}}
    <section class="mt-6 grid grid-cols-1 gap-6 rounded-2xl border border-zinc-200 p-6 sm:grid-cols-3 dark:border-zinc-800" aria-label="{{ __('Shipping, billing, and payment') }}">
        <div>
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Shipping Address') }}</h2>
            @if ($shippingAddress === [])
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No shipping required') }}</p>
            @else
                <address class="mt-2 text-sm text-zinc-600 not-italic dark:text-zinc-400">
                    {{ trim(($shippingAddress['first_name'] ?? '').' '.($shippingAddress['last_name'] ?? '')) }}<br />
                    {{ $shippingAddress['address1'] ?? '' }}<br />
                    @if (filled($shippingAddress['address2'] ?? null))
                        {{ $shippingAddress['address2'] }}<br />
                    @endif
                    {{ trim((($shippingAddress['postal_code'] ?? $shippingAddress['zip'] ?? '')).' '.($shippingAddress['city'] ?? '')) }}<br />
                    {{ \App\Support\Storefront\Countries::name($shippingAddress['country_code'] ?? '') }}
                </address>
            @endif
        </div>
        <div>
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Billing Address') }}</h2>
            @if (blank($billingAddress))
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Same as shipping') }}</p>
            @else
                <address class="mt-2 text-sm text-zinc-600 not-italic dark:text-zinc-400">
                    {{ trim(($billingAddress['first_name'] ?? '').' '.($billingAddress['last_name'] ?? '')) }}<br />
                    {{ $billingAddress['address1'] ?? '' }}<br />
                    {{ trim((($billingAddress['postal_code'] ?? $billingAddress['zip'] ?? '')).' '.($billingAddress['city'] ?? '')) }}<br />
                    {{ \App\Support\Storefront\Countries::name($billingAddress['country_code'] ?? '') }}
                </address>
            @endif
        </div>
        <div>
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Payment') }}</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $paymentLabel }}</p>
            <p class="mt-1"><x-storefront.order-status-badge :status="$order->financial_status" /></p>
        </div>
    </section>

    {{-- Totals --}}
    <section class="mt-6 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800" aria-labelledby="order-totals-heading">
        <h2 id="order-totals-heading" class="sr-only">{{ __('Order totals') }}</h2>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                <dt>{{ __('Subtotal') }}</dt>
                <dd><x-storefront.price :amount="$order->subtotal_amount" :currency="$order->currency" class="text-sm font-normal" /></dd>
            </div>
            @if ($order->discount_amount > 0)
                <div class="flex justify-between text-green-700 dark:text-green-400">
                    <dt>{{ __('Discount') }}</dt>
                    <dd>-{{ \App\Support\Storefront\PriceFormatter::format($order->discount_amount, $order->currency) }}</dd>
                </div>
            @endif
            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                <dt>{{ __('Shipping') }}</dt>
                <dd><x-storefront.price :amount="$order->shipping_amount" :currency="$order->currency" class="text-sm font-normal" /></dd>
            </div>
            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                <dt>{{ __('Tax') }}</dt>
                <dd><x-storefront.price :amount="$order->tax_amount" :currency="$order->currency" class="text-sm font-normal" /></dd>
            </div>
            <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold text-zinc-900 dark:border-zinc-700 dark:text-white">
                <dt>{{ __('Total') }}</dt>
                <dd><x-storefront.price :amount="$order->total_amount" :currency="$order->currency" /></dd>
            </div>
        </dl>
    </section>

    {{-- Fulfillment tracking --}}
    @if ($order->fulfillments->isNotEmpty())
        <section class="mt-6 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800" aria-labelledby="order-fulfillment-heading">
            <h2 id="order-fulfillment-heading" class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Fulfillment') }}</h2>
            <ul class="mt-4 space-y-3" role="list">
                @foreach ($order->fulfillments as $fulfillment)
                    <li class="flex flex-wrap items-center justify-between gap-3 text-sm">
                        <div class="flex items-center gap-3">
                            <x-storefront.order-status-badge :status="$fulfillment->status" />
                            <span class="text-zinc-600 dark:text-zinc-400">
                                @if (filled($fulfillment->tracking_number))
                                    {{ __('Shipped via :company - :number', [
                                        'company' => $fulfillment->tracking_company ?: __('carrier'),
                                        'number' => $fulfillment->tracking_number,
                                    ]) }}
                                @else
                                    {{ __('Fulfillment created :date', ['date' => $fulfillment->created_at?->format('M j, Y')]) }}
                                @endif
                            </span>
                        </div>
                        @if (filled($fulfillment->tracking_url))
                            <a
                                href="{{ $fulfillment->tracking_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded text-sm font-medium text-blue-600 transition hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-blue-400"
                            >
                                {{ __('Track shipment') }} &rarr;
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
