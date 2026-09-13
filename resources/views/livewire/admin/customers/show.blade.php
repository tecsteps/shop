<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.customers.index') }}" wire:navigate>Customers</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $customer->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="flex flex-col gap-4 lg:col-span-2">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Customer info</flux:heading>
                <div class="mt-3 flex flex-col gap-1 text-sm">
                    <p><span class="font-medium">Name:</span> {{ $customer->name }}</p>
                    <p><span class="font-medium">Email:</span> {{ $customer->email }}</p>
                    <p><span class="font-medium">Created:</span> {{ $customer->created_at?->format('M j, Y') }}</p>
                    <p class="flex items-center gap-2"><span class="font-medium">Marketing:</span>
                        <flux:badge :color="$customer->marketing_opt_in ? 'green' : 'zinc'">{{ $customer->marketing_opt_in ? 'Opted In' : 'Opted Out' }}</flux:badge>
                    </p>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Order history</flux:heading>
                @if ($orders->isEmpty())
                    <flux:text variant="subtle" class="mt-3">No orders yet.</flux:text>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                                    <th class="pb-2 font-medium">Order #</th>
                                    <th class="pb-2 font-medium">Date</th>
                                    <th class="pb-2 font-medium">Status</th>
                                    <th class="pb-2 font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $order)
                                    <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="order-{{ $order->getKey() }}">
                                        <td class="py-2"><a href="{{ route('admin.orders.show', $order) }}" class="underline" wire:navigate>{{ $order->order_number }}</a></td>
                                        <td class="py-2">{{ $order->placed_at?->format('M j, Y') }}</td>
                                        <td class="py-2"><flux:badge :color="$order->financial_status->value === 'paid' ? 'green' : 'zinc'">{{ ucfirst($order->financial_status->value) }}</flux:badge></td>
                                        <td class="py-2">{{ $this->money($order->total_amount, $order->currency) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $orders->links() }}</div>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:heading size="md">Addresses</flux:heading>
                    @can('update', $customer)
                        <flux:button variant="ghost" size="sm" wire:click="openAddressForm()">Add address</flux:button>
                    @endcan
                </div>
                <div class="mt-3 flex flex-col gap-3">
                    @forelse ($customer->customerAddresses as $address)
                        <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="address-{{ $address->getKey() }}">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium">{{ $address->label }}</p>
                                @if ($address->is_default)
                                    <flux:badge color="green">Default</flux:badge>
                                @endif
                            </div>
                            @php $json = is_array($address->address_json) ? $address->address_json : []; @endphp
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $json['address1'] ?? '' }}</p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $json['city'] ?? '' }}{{ isset($json['zip']) ? ', '.$json['zip'] : '' }}</p>
                            @can('update', $customer)
                                <div class="mt-2 flex gap-2">
                                    <flux:button variant="ghost" size="sm" wire:click="openAddressForm({{ $address->getKey() }})">Edit</flux:button>
                                    <flux:button variant="ghost" size="sm" wire:click="deleteAddress({{ $address->getKey() }})">Delete</flux:button>
                                    @if (! $address->is_default)
                                        <flux:button variant="ghost" size="sm" wire:click="setDefaultAddress({{ $address->getKey() }})">Set default</flux:button>
                                    @endif
                                </div>
                            @endcan
                        </div>
                    @empty
                        <flux:text variant="subtle">No addresses on file.</flux:text>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <flux:modal wire:model="showAddressModal" class="max-w-md">
        <flux:heading size="lg">{{ $this->editingAddressId ? 'Edit address' : 'Add address' }}</flux:heading>
        <div class="mt-4 flex flex-col gap-3">
            <flux:input wire:model="addressLabel" label="Label" placeholder="Home, Office..." />
            <flux:input wire:model="addressForm.first_name" label="First name" />
            <flux:input wire:model="addressForm.last_name" label="Last name" />
            <flux:input wire:model="addressForm.address1" label="Address line 1" />
            <flux:input wire:model="addressForm.address2" label="Address line 2" />
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="addressForm.city" label="City" />
                <flux:input wire:model="addressForm.province" label="State / Province" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="addressForm.zip" label="ZIP / Postal code" />
                <flux:input wire:model="addressForm.country_code" label="Country code" placeholder="DE" />
            </div>
            <flux:input wire:model="addressForm.phone" label="Phone" />
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showAddressModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveAddress">Save</flux:button>
        </div>
    </flux:modal>
</div>
