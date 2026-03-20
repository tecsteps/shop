<div>
    <div class="mb-6">
        <flux:heading size="xl" level="1">{{ $customer->name }}</flux:heading>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Info --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="font-medium">{{ $customer->name }}</flux:text>
                        <flux:text class="text-sm text-zinc-500">{{ $customer->email }}</flux:text>
                        <flux:text class="mt-1 text-xs text-zinc-400">Since {{ $customer->created_at->format('M d, Y') }}</flux:text>
                    </div>
                    <div class="text-right">
                        <flux:badge size="sm" :color="$customer->marketing_opt_in ? 'green' : 'zinc'">
                            {{ $customer->marketing_opt_in ? 'Opted in' : 'Not opted in' }}
                        </flux:badge>
                        <flux:text class="mt-1 text-sm text-zinc-500">Total spent: {{ $this->formatCurrency($this->totalSpent) }}</flux:text>
                    </div>
                </div>
            </div>

            {{-- Orders --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md" class="mb-4">Order history</flux:heading>
                @if($customer->orders->count() > 0)
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Order #</flux:table.column>
                            <flux:table.column>Date</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column>Total</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($customer->orders as $order)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:underline dark:text-blue-400" wire:navigate>{{ $order->order_number }}</a>
                                    </flux:table.cell>
                                    <flux:table.cell class="whitespace-nowrap">{{ $order->placed_at ? \Carbon\Carbon::parse($order->placed_at)->format('M d, Y') : '-' }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge size="sm" :color="match($order->financial_status->value) { 'paid' => 'green', 'pending' => 'zinc', default => 'yellow' }">
                                            {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ $this->formatCurrency($order->total_amount) }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <flux:text class="text-zinc-500">No orders yet.</flux:text>
                @endif
            </div>
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading size="md">Addresses</flux:heading>
                    <flux:button size="sm" variant="ghost" icon="plus" wire:click="openAddAddress">Add</flux:button>
                </div>
                @forelse($customer->addresses as $address)
                    <div class="mb-3 rounded border border-zinc-200 p-3 dark:border-zinc-700">
                        <div class="mb-1 flex items-center gap-2">
                            <span class="text-sm font-medium">{{ $address->label }}</span>
                            @if($address->is_default)
                                <flux:badge size="sm" color="green">Default</flux:badge>
                            @endif
                        </div>
                        @php $a = $address->address_json ?? []; @endphp
                        <div class="text-xs text-zinc-500">
                            {{ data_get($a, 'address1') }}, {{ data_get($a, 'city') }} {{ data_get($a, 'zip') }}, {{ data_get($a, 'country') }}
                        </div>
                        <div class="mt-2 flex gap-2">
                            <flux:button size="sm" variant="ghost" wire:click="editAddress({{ $address->id }})">Edit</flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="deleteAddress({{ $address->id }})">Delete</flux:button>
                            @if(!$address->is_default)
                                <flux:button size="sm" variant="ghost" wire:click="setDefaultAddress({{ $address->id }})">Set as default</flux:button>
                            @endif
                        </div>
                    </div>
                @empty
                    <flux:text class="text-sm text-zinc-500">No addresses.</flux:text>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Address Modal --}}
    <flux:modal name="address-form" class="md:w-96">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editAddressId ? 'Edit address' : 'Add address' }}</flux:heading>
            <flux:input wire:model="addressLabel" label="Label" placeholder="Home" />
            <flux:input wire:model="address1" label="Address" placeholder="123 Main St" />
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="city" label="City" />
                <flux:input wire:model="zip" label="ZIP" />
            </div>
            <flux:input wire:model="country" label="Country" placeholder="US" />
            <flux:checkbox wire:model="isDefault" label="Set as default" />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="saveAddress">Save</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
