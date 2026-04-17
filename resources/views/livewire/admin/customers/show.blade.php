<div>
    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('admin.customers.index') }}" wire:navigate>Customers</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $customer->first_name }} {{ $customer->last_name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Customer Info --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:heading size="lg" class="mb-3">{{ $customer->first_name }} {{ $customer->last_name }}</flux:heading>
                <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    <p>{{ $customer->email }}</p>
                    <p>Created: {{ $customer->created_at->format('M j, Y') }}</p>
                    @if ($customer->accepts_marketing)
                        <flux:badge color="green" size="sm">Opted In</flux:badge>
                    @else
                        <flux:badge color="zinc" size="sm">Not Subscribed</flux:badge>
                    @endif
                </div>
            </div>

            {{-- Order History --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:heading size="lg" class="mb-4">Order history</flux:heading>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="pb-2 font-medium text-gray-500">Order #</th>
                            <th class="pb-2 font-medium text-gray-500">Date</th>
                            <th class="pb-2 font-medium text-gray-500">Status</th>
                            <th class="pb-2 text-right font-medium text-gray-500">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="border-b border-gray-100 dark:border-gray-800" wire:key="order-{{ $order->id }}">
                                <td class="py-2">
                                    <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="text-blue-600 hover:underline">#{{ $order->order_number }}</a>
                                </td>
                                <td class="py-2 text-gray-500">{{ $order->placed_at?->format('M j, Y') }}</td>
                                <td class="py-2">
                                    <flux:badge :color="match($order->financial_status->value) { 'paid' => 'green', default => 'zinc' }" size="sm">
                                        {{ ucfirst($order->financial_status->value) }}
                                    </flux:badge>
                                </td>
                                <td class="py-2 text-right">${{ number_format($order->total_amount / 100, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-500">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3">{{ $orders->links() }}</div>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Addresses --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:heading size="md" class="mb-3">Addresses</flux:heading>
                @foreach ($customer->addresses as $address)
                    <div class="mb-3 rounded border border-gray-200 p-3 dark:border-gray-700" wire:key="addr-{{ $address->id }}">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">{{ $address->label ?? 'Address' }}</span>
                            @if ($address->is_default)
                                <flux:badge color="green" size="sm">Default</flux:badge>
                            @endif
                        </div>
                        @php $a = $address->address_json ?? []; @endphp
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $a['line1'] ?? '' }}<br>
                            {{ $a['city'] ?? '' }}, {{ $a['state'] ?? '' }} {{ $a['zip'] ?? '' }}
                        </p>
                        <div class="mt-2 flex gap-2">
                            <flux:button variant="ghost" size="sm" wire:click="openAddressForm({{ $address->id }})">Edit</flux:button>
                            <flux:button variant="ghost" size="sm" wire:click="deleteAddress({{ $address->id }})" wire:confirm="Delete this address?">Delete</flux:button>
                            @if (! $address->is_default)
                                <flux:button variant="ghost" size="sm" wire:click="setDefaultAddress({{ $address->id }})">Set Default</flux:button>
                            @endif
                        </div>
                    </div>
                @endforeach
                <flux:button variant="ghost" wire:click="openAddressForm" size="sm">
                    <flux:icon name="plus" variant="mini" class="mr-1 h-4 w-4" />
                    Add address
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Address Modal --}}
    <flux:modal name="address-form" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingAddress ? 'Edit address' : 'Add address' }}</flux:heading>
            <flux:field>
                <flux:label>Label</flux:label>
                <flux:input wire:model="addressLabel" placeholder="Home, Office..." />
            </flux:field>
            <flux:field>
                <flux:label>Address line 1</flux:label>
                <flux:input wire:model="addressJson.line1" />
            </flux:field>
            <flux:field>
                <flux:label>Address line 2</flux:label>
                <flux:input wire:model="addressJson.line2" />
            </flux:field>
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>City</flux:label>
                    <flux:input wire:model="addressJson.city" />
                </flux:field>
                <flux:field>
                    <flux:label>State / Province</flux:label>
                    <flux:input wire:model="addressJson.state" />
                </flux:field>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>ZIP / Postal code</flux:label>
                    <flux:input wire:model="addressJson.zip" />
                </flux:field>
                <flux:field>
                    <flux:label>Country</flux:label>
                    <flux:input wire:model="addressJson.country" />
                </flux:field>
            </div>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('address-form').close()">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveAddress">Save</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
