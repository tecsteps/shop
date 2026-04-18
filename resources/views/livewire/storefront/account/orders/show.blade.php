@php
    $statusVariant = [
        'pending' => 'warning',
        'paid' => 'success',
        'fulfilled' => 'accent',
        'cancelled' => 'neutral',
        'refunded' => 'danger',
    ];
    $shipping = $order->shipping_address_json ?? [];
    $billing = $order->billing_address_json ?? [];
    $formatAddress = function (array $addr): string {
        $lines = array_filter([
            trim(($addr['first_name'] ?? '').' '.($addr['last_name'] ?? '')),
            $addr['address1'] ?? null,
            $addr['address2'] ?? null,
            trim(($addr['city'] ?? '').' '.($addr['postal_code'] ?? $addr['zip'] ?? '')),
            $addr['country_code'] ?? null,
        ]);
        return implode("\n", $lines);
    };
@endphp
<div class="mx-auto max-w-4xl px-6 py-12">
    <x-storefront.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Account', 'url' => route('account.dashboard')],
        ['label' => 'Orders', 'url' => route('account.orders.index')],
        ['label' => '#'.$order->order_number],
    ]" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Order #{{ $order->order_number }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500">
                Placed on {{ $order->placed_at?->format('F j, Y') }}
            </flux:text>
        </div>
        <div class="flex gap-2">
            <x-storefront.badge :variant="$statusVariant[$order->status->value] ?? 'neutral'">
                {{ ucfirst($order->status->value) }}
            </x-storefront.badge>
            <x-storefront.badge variant="neutral">
                {{ ucfirst(str_replace('_', ' ', $order->fulfillment_status->value)) }}
            </x-storefront.badge>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
            <thead class="bg-zinc-50 text-left text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                <tr>
                    <th class="px-4 py-3 font-medium">Item</th>
                    <th class="px-4 py-3 text-right font-medium">Qty</th>
                    <th class="px-4 py-3 text-right font-medium">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($order->lines as $line)
                    <tr wire:key="line-{{ $line->id }}">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $line->title_snapshot }}</div>
                            @if ($line->sku_snapshot)
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">SKU: {{ $line->sku_snapshot }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">{{ $line->quantity }}</td>
                        <td class="px-4 py-3 text-right">
                            <x-storefront.price :amount="$line->total_amount" :currency="$order->currency" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @if (! empty($shipping))
            <div class="rounded-lg border border-zinc-200 p-4 text-sm dark:border-zinc-800">
                <div class="mb-2 font-medium">Shipping address</div>
                <pre class="whitespace-pre-wrap font-sans text-zinc-600 dark:text-zinc-300">{{ $formatAddress($shipping) }}</pre>
            </div>
        @endif
        @if (! empty($billing))
            <div class="rounded-lg border border-zinc-200 p-4 text-sm dark:border-zinc-800">
                <div class="mb-2 font-medium">Billing address</div>
                <pre class="whitespace-pre-wrap font-sans text-zinc-600 dark:text-zinc-300">{{ $formatAddress($billing) }}</pre>
            </div>
        @endif
        <div class="rounded-lg border border-zinc-200 p-4 text-sm dark:border-zinc-800">
            <div class="mb-2 font-medium">Payment</div>
            <div class="text-zinc-600 dark:text-zinc-300">
                {{ ucfirst(str_replace('_', ' ', $order->payment_method->value)) }}
                <span class="ml-1">({{ ucfirst($order->financial_status->value) }})</span>
            </div>
        </div>
    </div>

    <div class="mt-6">
        <x-storefront.order-summary
            :subtotal="$order->subtotal_amount"
            :shipping="$order->shipping_amount"
            :tax="$order->tax_amount"
            :discount="$order->discount_amount"
            :total="$order->total_amount"
            :currency="$order->currency" />
    </div>

    @if ($order->fulfillments->isNotEmpty())
        <div class="mt-6 rounded-lg border border-zinc-200 p-4 text-sm dark:border-zinc-800">
            <div class="mb-2 font-medium">Fulfillments</div>
            <ul class="space-y-2 text-zinc-600 dark:text-zinc-300">
                @foreach ($order->fulfillments as $fulfillment)
                    <li wire:key="ful-{{ $fulfillment->id }}">
                        @if ($fulfillment->tracking_company)
                            Shipped via {{ $fulfillment->tracking_company }}
                        @else
                            Fulfillment #{{ $fulfillment->id }}
                        @endif
                        @if ($fulfillment->tracking_number)
                            - {{ $fulfillment->tracking_number }}
                        @endif
                        @if ($fulfillment->tracking_url)
                            <a href="{{ $fulfillment->tracking_url }}" target="_blank" rel="noopener noreferrer" class="ml-1 underline">Track shipment</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
