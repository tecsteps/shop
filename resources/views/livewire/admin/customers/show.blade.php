<section class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ $customer->name ?: 'Unnamed customer' }}</flux:heading>
            <flux:text class="mt-1">{{ $customer->email }}</flux:text>
        </div>

        <flux:button :href="route('admin.customers.index')" wire:navigate variant="filled" icon="arrow-left">
            Customers
        </flux:button>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Customer info</flux:heading>

                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Name</dt>
                        <dd class="mt-1 font-medium text-zinc-950 dark:text-white">{{ $customer->name ?: 'Unnamed customer' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Email</dt>
                        <dd class="mt-1 font-medium text-zinc-950 dark:text-white">{{ $customer->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Created</dt>
                        <dd class="mt-1 font-medium text-zinc-950 dark:text-white">{{ $customer->created_at?->format('M j, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Marketing</dt>
                        <dd class="mt-1">
                            <flux:badge :color="$customer->marketing_opt_in ? 'green' : 'zinc'">
                                {{ $customer->marketing_opt_in ? 'Opted in' : 'Not opted in' }}
                            </flux:badge>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 p-5 dark:border-zinc-800">
                    <flux:heading size="lg">Order history</flux:heading>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3">Order</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse ($orders as $order)
                                @php
                                    $statusColor = match ($order->financial_status->value) {
                                        'paid' => 'green',
                                        'partially_refunded' => 'amber',
                                        'refunded', 'voided' => 'red',
                                        default => 'zinc',
                                    };
                                @endphp

                                <tr wire:key="admin-customer-order-{{ $order->getKey() }}">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-medium text-zinc-950 hover:underline dark:text-white">
                                            {{ $order->order_number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-500">{{ $order->placed_at?->format('M j, Y') }}</td>
                                    <td class="px-4 py-3">
                                        <flux:badge :color="$statusColor">{{ Str::headline($order->financial_status->value) }}</flux:badge>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" class="justify-end" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-12 text-center text-sm text-zinc-500">No orders for this customer.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $orders->links() }}
        </div>

        <aside class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-4">
                <flux:heading size="lg">Addresses</flux:heading>
                <flux:button wire:click="openAddressForm" variant="primary" icon="plus" size="sm">Add</flux:button>
            </div>

            <div class="mt-5 space-y-4">
                @forelse ($customer->addresses as $address)
                    @php($addressData = $address->address_json ?? [])

                    <div wire:key="admin-customer-address-{{ $address->getKey() }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="font-medium text-zinc-950 dark:text-white">{{ $address->label ?: 'Address' }}</div>
                                    @if ($address->is_default)
                                        <flux:badge color="green">Default</flux:badge>
                                    @endif
                                </div>
                                <div class="mt-2 space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    <div>{{ data_get($addressData, 'address1') }}</div>
                                    @if (data_get($addressData, 'address2'))
                                        <div>{{ data_get($addressData, 'address2') }}</div>
                                    @endif
                                    <div>{{ trim(data_get($addressData, 'postal_code').' '.data_get($addressData, 'city')) }}</div>
                                    <div>{{ data_get($addressData, 'country') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <flux:button wire:click="openAddressForm({{ $address->getKey() }})" size="sm" variant="filled">Edit</flux:button>
                            @unless ($address->is_default)
                                <flux:button wire:click="setDefaultAddress({{ $address->getKey() }})" size="sm" variant="ghost">Set default</flux:button>
                            @endunless
                            <flux:button wire:click="deleteAddress({{ $address->getKey() }})" wire:confirm="Delete this address?" size="sm" variant="danger">Delete</flux:button>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-zinc-200 px-4 py-10 text-center dark:border-zinc-700">
                        <flux:icon name="map-pin" class="mx-auto size-8 text-zinc-400" />
                        <flux:text class="mt-3">No addresses saved.</flux:text>
                    </div>
                @endforelse
            </div>
        </aside>
    </div>

    <flux:modal name="address-form" class="md:w-[32rem]">
        <form wire:submit="saveAddress" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingAddressId ? 'Edit address' : 'Add address' }}</flux:heading>
            </div>

            <flux:input wire:model="addressLabel" label="Label" placeholder="Home, Office..." />

            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input wire:model="addressJson.first_name" label="First name" />
                <flux:input wire:model="addressJson.last_name" label="Last name" />
                <div class="sm:col-span-2">
                    <flux:input wire:model="addressJson.address1" label="Address line 1" />
                    <flux:error name="addressJson.address1" />
                </div>
                <div class="sm:col-span-2">
                    <flux:input wire:model="addressJson.address2" label="Address line 2" />
                </div>
                <flux:input wire:model="addressJson.city" label="City" />
                <flux:input wire:model="addressJson.province_code" label="State / Province" />
                <flux:input wire:model="addressJson.postal_code" label="Postal code" />
                <flux:select wire:model="addressJson.country" label="Country">
                    <flux:select.option value="DE">Germany</flux:select.option>
                    <flux:select.option value="US">United States</flux:select.option>
                    <flux:select.option value="GB">United Kingdom</flux:select.option>
                    <flux:select.option value="FR">France</flux:select.option>
                    <flux:select.option value="NL">Netherlands</flux:select.option>
                </flux:select>
            </div>
            <flux:error name="addressJson.city" />
            <flux:error name="addressJson.postal_code" />
            <flux:error name="addressJson.country" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
