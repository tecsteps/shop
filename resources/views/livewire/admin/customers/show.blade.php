<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ $customer->name ?? $customer->email }}</flux:heading>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Customer Orders --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="md">{{ __('Orders') }}</flux:heading>
                </div>
                @if($customer->orders->count() > 0)
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="p-3 text-left font-medium text-zinc-500">{{ __('Order') }}</th>
                                <th class="p-3 text-left font-medium text-zinc-500">{{ __('Total') }}</th>
                                <th class="p-3 text-left font-medium text-zinc-500">{{ __('Status') }}</th>
                                <th class="p-3 text-left font-medium text-zinc-500">{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customer->orders as $order)
                                <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                    <td class="p-3">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="text-accent hover:underline" wire:navigate>
                                            {{ $order->order_number }}
                                        </a>
                                    </td>
                                    <td class="p-3">${{ number_format($order->total_amount / 100, 2) }}</td>
                                    <td class="p-3">
                                        <flux:badge size="sm" :color="match($order->financial_status->value) {
                                            'paid' => 'green', 'pending' => 'yellow', 'refunded' => 'red', default => 'zinc',
                                        }">{{ ucfirst($order->financial_status->value) }}</flux:badge>
                                    </td>
                                    <td class="p-3 text-zinc-500">{{ $order->placed_at?->diffForHumans() ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="p-8 text-center">
                        <flux:text class="text-zinc-500">{{ __('No orders yet.') }}</flux:text>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            {{-- Customer Info --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 space-y-3">
                <flux:heading size="md">{{ __('Customer info') }}</flux:heading>
                <div class="text-sm space-y-2">
                    <div><span class="text-zinc-500">{{ __('Email') }}:</span> {{ $customer->email }}</div>
                    <div><span class="text-zinc-500">{{ __('Name') }}:</span> {{ $customer->name ?? '-' }}</div>
                    <div><span class="text-zinc-500">{{ __('Marketing') }}:</span> {{ $customer->marketing_opt_in ? __('Yes') : __('No') }}</div>
                    <div><span class="text-zinc-500">{{ __('Joined') }}:</span> {{ $customer->created_at->format('M d, Y') }}</div>
                </div>
            </div>

            {{-- Addresses --}}
            @if($customer->addresses->count() > 0)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4">
                    <flux:heading size="md" class="mb-3">{{ __('Addresses') }}</flux:heading>
                    @foreach($customer->addresses as $address)
                        @php $addr = $address->address_json; @endphp
                        <div class="text-sm space-y-1 @if(!$loop->last) mb-4 pb-4 border-b border-zinc-100 dark:border-zinc-800 @endif">
                            @if($address->label)
                                <div class="font-semibold">{{ $address->label }}</div>
                            @endif
                            <div>{{ $addr['first_name'] ?? '' }} {{ $addr['last_name'] ?? '' }}</div>
                            <div>{{ $addr['address1'] ?? '' }}</div>
                            @if(!empty($addr['address2']))<div>{{ $addr['address2'] }}</div>@endif
                            <div>{{ $addr['postal_code'] ?? '' }} {{ $addr['city'] ?? '' }}</div>
                            <div>{{ $addr['country_code'] ?? '' }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
