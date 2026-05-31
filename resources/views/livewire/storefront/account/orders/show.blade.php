@php
    use App\Support\Storefront\PriceFormatter;
    $money = fn ($amount) => PriceFormatter::format((int) $amount, $order->currency);
    $shipping = $order->shipping_address_json ?? [];
    $billing = $order->billing_address_json ?? [];
@endphp

<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront::breadcrumbs :items="[
        ['label' => __('Account'), 'url' => route('account.dashboard')],
        ['label' => __('Orders'), 'url' => route('account.orders.index')],
        ['label' => $order->order_number],
    ]" />

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Order :number', ['number' => $order->order_number]) }}</h1>
        <div class="flex gap-2">
            <x-storefront::badge :text="ucfirst($order->financial_status->value)" variant="new" />
            <x-storefront::badge :text="ucfirst($order->fulfillment_status->value)" />
        </div>
    </div>
    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Placed on :date', ['date' => $order->placed_at?->format('F j, Y')]) }}</p>

    {{-- Items. --}}
    <div class="mt-8 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <tbody class="divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                @foreach ($lines as $line)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $line->title_snapshot }}</p>
                            @if ($line->sku_snapshot)
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $line->sku_snapshot }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">&times;{{ $line->quantity }}</td>
                        <td class="px-4 py-3 text-right font-medium text-zinc-900 dark:text-white">{{ $money($line->total_amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Address + payment grid. --}}
    <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Shipping address') }}</h2>
            <address class="mt-2 text-sm not-italic text-zinc-700 dark:text-zinc-300">
                {{ trim(($shipping['first_name'] ?? '').' '.($shipping['last_name'] ?? '')) }}<br>
                {{ $shipping['address1'] ?? '' }}<br>
                {{ trim(($shipping['city'] ?? '').', '.($shipping['postal_code'] ?? ''), ', ') }}<br>
                {{ $shipping['country'] ?? '' }}
            </address>
        </div>
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Billing address') }}</h2>
            <address class="mt-2 text-sm not-italic text-zinc-700 dark:text-zinc-300">
                @if (empty($billing))
                    {{ __('Same as shipping') }}
                @else
                    {{ trim(($billing['first_name'] ?? '').' '.($billing['last_name'] ?? '')) }}<br>
                    {{ $billing['address1'] ?? '' }}<br>
                    {{ trim(($billing['city'] ?? '').', '.($billing['postal_code'] ?? ''), ', ') }}<br>
                    {{ $billing['country'] ?? '' }}
                @endif
            </address>
        </div>
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Payment') }}</h2>
            <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">{{ ucwords(str_replace('_', ' ', $order->payment_method->value)) }}</p>
        </div>
    </div>

    {{-- Totals. --}}
    <dl class="mt-6 space-y-2 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-800">
        <div class="flex justify-between text-zinc-600 dark:text-zinc-300"><dt>{{ __('Subtotal') }}</dt><dd>{{ $money($order->subtotal_amount) }}</dd></div>
        @if ($order->discount_amount > 0)
            <div class="flex justify-between text-green-600 dark:text-green-400"><dt>{{ __('Discount') }}</dt><dd>-{{ $money($order->discount_amount) }}</dd></div>
        @endif
        <div class="flex justify-between text-zinc-600 dark:text-zinc-300"><dt>{{ __('Shipping') }}</dt><dd>{{ $money($order->shipping_amount) }}</dd></div>
        <div class="flex justify-between text-zinc-600 dark:text-zinc-300"><dt>{{ __('Tax') }}</dt><dd>{{ $money($order->tax_amount) }}</dd></div>
        <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold text-zinc-900 dark:border-zinc-800 dark:text-white"><dt>{{ __('Total') }}</dt><dd>{{ $money($order->total_amount) }}</dd></div>
    </dl>

    {{-- Fulfillment / tracking. --}}
    @if ($fulfillments->isNotEmpty())
        <div class="mt-8 rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
            <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Fulfillment') }}</h2>
            @foreach ($fulfillments as $fulfillment)
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                    @if ($fulfillment->tracking_company)
                        {{ __('Shipped via :company', ['company' => $fulfillment->tracking_company]) }}
                        @if ($fulfillment->tracking_number) &mdash; {{ $fulfillment->tracking_number }} @endif
                    @else
                        {{ __('Shipped') }}
                    @endif
                    @if ($fulfillment->tracking_url)
                        <a href="{{ $fulfillment->tracking_url }}" target="_blank" rel="noopener noreferrer"
                           class="ml-2 font-medium text-blue-600 hover:underline dark:text-blue-400">{{ __('Track shipment') }} &rarr;</a>
                    @endif
                </p>
            @endforeach
        </div>
    @endif
</div>
