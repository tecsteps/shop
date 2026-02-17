<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ $customer->name }}</flux:heading>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Customer info --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="md" class="mb-4">Customer info</flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-zinc-500 dark:text-zinc-400">Name</span><span class="text-zinc-900 dark:text-zinc-100">{{ $customer->name }}</span></div>
                    <div class="flex justify-between"><span class="text-zinc-500 dark:text-zinc-400">Email</span><span class="text-zinc-900 dark:text-zinc-100">{{ $customer->email }}</span></div>
                    <div class="flex justify-between"><span class="text-zinc-500 dark:text-zinc-400">Created</span><span class="text-zinc-900 dark:text-zinc-100">{{ $customer->created_at->format('M j, Y') }}</span></div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Marketing</span>
                        <flux:badge :color="$customer->marketing_opt_in ? 'green' : 'zinc'" size="sm">{{ $customer->marketing_opt_in ? 'Opted In' : 'Opted Out' }}</flux:badge>
                    </div>
                </div>
            </div>

            {{-- Order history --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="md" class="mb-4">Order history</flux:heading>
                @if ($customer->orders->isEmpty())
                    <flux:text class="text-zinc-400">No orders yet.</flux:text>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                                <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400">Order #</th>
                                <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400">Date</th>
                                <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                                <th class="pb-2 text-right font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($customer->orders as $order)
                                <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                                    <td class="py-2"><a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:underline dark:text-blue-400" wire:navigate>{{ $order->order_number }}</a></td>
                                    <td class="py-2 text-zinc-600 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y') }}</td>
                                    <td class="py-2"><flux:badge size="sm">{{ ucfirst($order->financial_status->value) }}</flux:badge></td>
                                    <td class="py-2 text-right text-zinc-900 dark:text-zinc-100">${{ number_format($order->total_amount / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Right column - Addresses --}}
        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="md" class="mb-4">Addresses</flux:heading>
                @foreach ($customer->addresses as $address)
                    <div class="mb-3 rounded border border-zinc-200 p-3 dark:border-zinc-700">
                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $address->label ?? 'Address' }}</p>
                        @if ($address->is_default)
                            <flux:badge color="green" size="sm" class="mb-1">Default</flux:badge>
                        @endif
                        @php $a = $address->address_json ?? []; @endphp
                        <div class="space-y-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            <p>{{ $a['line1'] ?? '' }}</p>
                            <p>{{ $a['city'] ?? '' }}, {{ $a['state'] ?? '' }} {{ $a['zip'] ?? '' }}</p>
                            <p>{{ $a['country'] ?? '' }}</p>
                        </div>
                        <div class="mt-2 flex gap-2">
                            <flux:button size="sm" variant="ghost" wire:click="openAddressForm({{ $address->id }})">Edit</flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="deleteAddress({{ $address->id }})" wire:confirm="Delete this address?">Delete</flux:button>
                        </div>
                    </div>
                @endforeach
                <flux:button size="sm" variant="ghost" icon="plus" wire:click="openAddressForm()">Add address</flux:button>
            </div>
        </div>
    </div>

    {{-- Address modal --}}
    <flux:modal name="address-form" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingAddressId ? 'Edit address' : 'Add address' }}</flux:heading>
            <flux:input wire:model="addressForm.label" label="Label" placeholder="Home, Office..." />
            <flux:input wire:model="addressForm.line1" label="Address line 1" />
            <flux:input wire:model="addressForm.line2" label="Address line 2" />
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="addressForm.city" label="City" />
                <flux:input wire:model="addressForm.state" label="State/Province" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="addressForm.zip" label="ZIP/Postal code" />
                <flux:input wire:model="addressForm.country" label="Country" />
            </div>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('address-form').close()">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveAddress">Save</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
