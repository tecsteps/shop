<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex items-center gap-4">
        <a href="{{ route('customer.orders') }}"
           class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            &larr; {{ __('Back to orders') }}
        </a>
    </div>

    <div class="mt-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ __('Order :number', ['number' => $order->order_number]) }}
        </h1>
        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium
            @switch($order->status->value)
                @case('paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 @break
                @case('fulfilled') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 @break
                @case('cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @break
                @case('refunded') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @break
                @default bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
            @endswitch
        ">
            {{ ucfirst($order->status->value) }}
        </span>
    </div>

    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ __('Placed on :date', ['date' => $order->placed_at?->format('F j, Y \a\t g:i A')]) }}
    </p>

    {{-- Order lines --}}
    <section class="mt-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Items') }}</h2>
        <div class="mt-4 overflow-x-auto overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Product') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('SKU') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Qty') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    @foreach($order->lines as $line)
                        <tr wire:key="line-{{ $line->id }}">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $line->title_snapshot }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $line->sku_snapshot ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ $line->quantity }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white">
                                ${{ number_format($line->total_amount / 100, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Order summary --}}
    <section class="mt-6">
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Subtotal') }}</dt>
                    <dd class="text-gray-900 dark:text-white">${{ number_format($order->subtotal_amount / 100, 2) }}</dd>
                </div>
                @if($order->discount_amount > 0)
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Discount') }}</dt>
                        <dd class="text-green-600 dark:text-green-400">-${{ number_format($order->discount_amount / 100, 2) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Shipping') }}</dt>
                    <dd class="text-gray-900 dark:text-white">${{ number_format($order->shipping_amount / 100, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Tax') }}</dt>
                    <dd class="text-gray-900 dark:text-white">${{ number_format($order->tax_amount / 100, 2) }}</dd>
                </div>
                <div class="flex justify-between border-t border-gray-200 pt-2 dark:border-gray-600">
                    <dt class="font-semibold text-gray-900 dark:text-white">{{ __('Total') }}</dt>
                    <dd class="font-semibold text-gray-900 dark:text-white">${{ number_format($order->total_amount / 100, 2) }}</dd>
                </div>
            </dl>
        </div>
    </section>

    {{-- Fulfillment timeline --}}
    @if($order->fulfillments->isNotEmpty())
        <section class="mt-8">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Fulfillment') }}</h2>
            <div class="mt-4 space-y-4">
                @foreach($order->fulfillments as $fulfillment)
                    <div wire:key="fulfillment-{{ $fulfillment->id }}" class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ __('Fulfillment') }} #{{ $loop->iteration }}
                            </span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                @if($fulfillment->status->value === 'delivered') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($fulfillment->status->value === 'shipped') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                @endif
                            ">
                                {{ ucfirst($fulfillment->status->value) }}
                            </span>
                        </div>
                        @if($fulfillment->tracking_number)
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Tracking') }}: {{ $fulfillment->tracking_number }}
                                @if($fulfillment->tracking_url)
                                    (<a href="{{ $fulfillment->tracking_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ __('Track') }}</a>)
                                @endif
                            </p>
                        @endif
                        @if($fulfillment->shipped_at)
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Shipped') }}: {{ $fulfillment->shipped_at->format('M d, Y') }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Addresses --}}
    <section class="mt-8 grid gap-6 sm:grid-cols-2">
        @if($order->shipping_address_json)
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Shipping Address') }}</h2>
                <div class="mt-2 rounded-lg border border-gray-200 p-4 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">
                    <p>{{ $order->shipping_address_json['first_name'] ?? '' }} {{ $order->shipping_address_json['last_name'] ?? '' }}</p>
                    <p>{{ $order->shipping_address_json['address1'] ?? '' }}</p>
                    @if($order->shipping_address_json['address2'] ?? null)
                        <p>{{ $order->shipping_address_json['address2'] }}</p>
                    @endif
                    <p>{{ $order->shipping_address_json['city'] ?? '' }}, {{ $order->shipping_address_json['province'] ?? '' }} {{ $order->shipping_address_json['zip'] ?? '' }}</p>
                    <p>{{ $order->shipping_address_json['country'] ?? '' }}</p>
                </div>
            </div>
        @endif

        @if($order->billing_address_json)
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Billing Address') }}</h2>
                <div class="mt-2 rounded-lg border border-gray-200 p-4 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">
                    <p>{{ $order->billing_address_json['first_name'] ?? '' }} {{ $order->billing_address_json['last_name'] ?? '' }}</p>
                    <p>{{ $order->billing_address_json['address1'] ?? '' }}</p>
                    @if($order->billing_address_json['address2'] ?? null)
                        <p>{{ $order->billing_address_json['address2'] }}</p>
                    @endif
                    <p>{{ $order->billing_address_json['city'] ?? '' }}, {{ $order->billing_address_json['province'] ?? '' }} {{ $order->billing_address_json['zip'] ?? '' }}</p>
                    <p>{{ $order->billing_address_json['country'] ?? '' }}</p>
                </div>
            </div>
        @endif
    </section>
</div>
