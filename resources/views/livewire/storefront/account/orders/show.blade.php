<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Order #{{ $order->order_number }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Placed {{ $order->placed_at?->format('F j, Y') }}</p>
        </div>
        <a href="{{ url('/account/orders') }}" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Back to orders</a>
    </div>

    <div class="mb-4 flex gap-2">
        <flux:badge :variant="match($order->financial_status?->value) { 'paid' => 'primary', 'pending' => 'warning', default => 'outline' }">
            {{ ucfirst(str_replace('_', ' ', $order->financial_status?->value ?? 'unknown')) }}
        </flux:badge>
        <flux:badge :variant="match($order->fulfillment_status?->value) { 'fulfilled' => 'primary', 'partial' => 'warning', default => 'outline' }">
            {{ ucfirst($order->fulfillment_status?->value ?? 'unfulfilled') }}
        </flux:badge>
    </div>

    {{-- Line items --}}
    <div class="rounded-lg border border-gray-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Item</th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Qty</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Price</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @foreach($order->lines as $line)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                            {{ $line->title_snapshot }}
                            @if($line->variant_title_snapshot) <span class="text-gray-500">- {{ $line->variant_title_snapshot }}</span> @endif
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">{{ $line->quantity }}</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-400">${{ number_format($line->unit_price / 100, 2) }}</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-white">${{ number_format($line->total / 100, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="border-t border-gray-200 px-6 py-4 dark:border-zinc-700">
            <div class="ml-auto w-64 space-y-1 text-sm">
                <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Subtotal</span><span>${{ number_format($order->subtotal / 100, 2) }}</span></div>
                @if($order->discount_amount)
                    <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Discount</span><span class="text-green-600">-${{ number_format($order->discount_amount / 100, 2) }}</span></div>
                @endif
                <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Shipping</span><span>${{ number_format($order->shipping_amount / 100, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Tax</span><span>${{ number_format($order->tax_amount / 100, 2) }}</span></div>
                <div class="flex justify-between border-t pt-1 font-semibold dark:border-zinc-700"><span>Total</span><span>${{ number_format($order->total / 100, 2) }}</span></div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
        @if($order->shipping_address_json)
            <x-admin.card>
                <h3 class="mb-2 font-semibold text-gray-900 dark:text-white">Shipping Address</h3>
                @php $addr = $order->shipping_address_json; @endphp
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <p>{{ $addr['first_name'] ?? '' }} {{ $addr['last_name'] ?? '' }}</p>
                    <p>{{ $addr['address1'] ?? '' }}</p>
                    <p>{{ $addr['city'] ?? '' }}, {{ $addr['province'] ?? '' }} {{ $addr['zip'] ?? '' }}</p>
                    <p>{{ $addr['country'] ?? '' }}</p>
                </div>
            </x-admin.card>
        @endif
        <x-admin.card>
            <h3 class="mb-2 font-semibold text-gray-900 dark:text-white">Payment</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'N/A')) }}</p>
        </x-admin.card>
    </div>

    {{-- Fulfillments --}}
    @if($order->fulfillments->count())
        <x-admin.card class="mt-6">
            <h3 class="mb-4 font-semibold text-gray-900 dark:text-white">Shipping Updates</h3>
            @foreach($order->fulfillments as $fulfillment)
                <div class="mb-3 rounded border border-gray-100 p-3 dark:border-zinc-700">
                    <flux:badge size="sm">{{ ucfirst($fulfillment->status->value) }}</flux:badge>
                    @if($fulfillment->tracking_number)
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $fulfillment->tracking_company }} - {{ $fulfillment->tracking_number }}</p>
                    @endif
                    @if($fulfillment->shipped_at)
                        <p class="text-xs text-gray-400">Shipped {{ $fulfillment->shipped_at->format('M d, Y') }}</p>
                    @endif
                </div>
            @endforeach
        </x-admin.card>
    @endif
</div>
