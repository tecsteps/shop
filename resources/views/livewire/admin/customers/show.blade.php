<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $customer->name ?? $customer->email }}</flux:heading>
        <flux:button variant="ghost" href="{{ url('/admin/customers') }}">Back</flux:button>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                <flux:heading size="md">Customer info</flux:heading>
                <flux:separator class="my-3" />
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-neutral-500">Name</dt><dd>{{ $customer->name ?? '-' }}</dd></div>
                    <div><dt class="text-neutral-500">Email</dt><dd>{{ $customer->email }}</dd></div>
                    <div><dt class="text-neutral-500">Created</dt><dd>{{ $customer->created_at?->format('Y-m-d') }}</dd></div>
                    <div><dt class="text-neutral-500">Marketing</dt><dd>{{ $customer->marketing_opt_in ? 'Opted in' : 'No' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-800">
                    <flux:heading size="md">Order history</flux:heading>
                </div>
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase text-neutral-500">
                        <tr>
                            <th class="px-4 py-2">Order #</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customer->orders as $order)
                            <tr wire:key="c-order-{{ $order->id }}" class="border-t border-neutral-100 dark:border-neutral-800">
                                <td class="px-4 py-2">
                                    <a href="{{ url('/admin/orders/'.$order->id) }}" class="font-medium hover:underline">{{ $order->order_number }}</a>
                                </td>
                                <td class="px-4 py-2 text-neutral-500">{{ optional($order->placed_at)->format('Y-m-d') }}</td>
                                <td class="px-4 py-2"><flux:badge size="sm">{{ $order->financial_status->value }}</flux:badge></td>
                                <td class="px-4 py-2">{{ number_format($order->total_amount / 100, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-neutral-500">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                <flux:heading size="md">Addresses</flux:heading>
                <flux:separator class="my-3" />
                @if ($customer->addresses->isEmpty())
                    <p class="text-sm text-neutral-500">No addresses on file.</p>
                @else
                    <ul class="space-y-3 text-sm">
                        @foreach ($customer->addresses as $address)
                            <li wire:key="address-{{ $address->id }}" class="rounded border border-neutral-100 p-3 dark:border-neutral-800">
                                <div class="font-medium">{{ $address->label ?? 'Address' }}</div>
                                @php $json = $address->address_json ?? []; @endphp
                                <div>{{ $json['line1'] ?? '' }}</div>
                                <div>{{ $json['city'] ?? '' }} {{ $json['region'] ?? '' }} {{ $json['postal_code'] ?? '' }}</div>
                                <div>{{ $json['country'] ?? '' }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
