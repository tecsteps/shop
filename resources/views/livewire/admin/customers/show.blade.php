@php
    $countries = ['US' => 'United States', 'CA' => 'Canada', 'GB' => 'United Kingdom', 'DE' => 'Germany', 'FR' => 'France', 'NL' => 'Netherlands', 'BE' => 'Belgium', 'AT' => 'Austria', 'CH' => 'Switzerland', 'ES' => 'Spain', 'IT' => 'Italy', 'PT' => 'Portugal', 'IE' => 'Ireland', 'SE' => 'Sweden', 'NO' => 'Norway', 'DK' => 'Denmark', 'FI' => 'Finland', 'PL' => 'Poland', 'CZ' => 'Czech Republic', 'AU' => 'Australia', 'NZ' => 'New Zealand', 'JP' => 'Japan', 'SG' => 'Singapore'];
@endphp

<div>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left column --}}
        <div class="space-y-6 lg:col-span-2">
            <flux:card class="p-6">
                <flux:heading size="lg">{{ $customer->name }}</flux:heading>
                <flux:separator class="mt-3" />

                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <flux:text>Email</flux:text>
                        <span class="font-medium text-zinc-800 dark:text-white">{{ $customer->email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <flux:text>Created</flux:text>
                        <span class="font-medium text-zinc-800 dark:text-white">{{ $customer->created_at?->format('M j, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <flux:text>Marketing</flux:text>
                        <flux:badge :color="$customer->marketing_opt_in ? 'green' : 'zinc'" size="sm">
                            {{ $customer->marketing_opt_in ? 'Opted In' : 'Opted Out' }}
                        </flux:badge>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-6">
                <flux:heading size="md">Order history</flux:heading>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 text-start text-xs uppercase tracking-wider text-zinc-400 dark:border-zinc-700">
                                <th class="py-2 pe-3 text-start font-medium">Order #</th>
                                <th class="py-2 pe-3 text-start font-medium">Date</th>
                                <th class="py-2 pe-3 text-start font-medium">Status</th>
                                <th class="py-2 text-end font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->orders as $order)
                                <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                                    <td class="py-2.5 pe-3">
                                        <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-medium text-zinc-800 hover:underline dark:text-white">
                                            {{ $order->order_number }}
                                        </a>
                                    </td>
                                    <td class="py-2.5 pe-3 text-zinc-500 dark:text-zinc-300">{{ $order->placed_at?->format('M j, Y') }}</td>
                                    <td class="py-2.5 pe-3">
                                        <flux:badge :color="match ($order->status) { 'paid', 'fulfilled' => 'green', 'cancelled' => 'red', 'refunded' => 'yellow', default => 'zinc' }" size="sm">
                                            {{ ucfirst($order->status) }}
                                        </flux:badge>
                                    </td>
                                    <td class="py-2.5 text-end font-medium text-zinc-800 dark:text-white">{{ $this->formatMoney($order->total_amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-zinc-400">No orders yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <flux:pagination :paginator="$this->orders" />
                </div>
            </flux:card>
        </div>

        {{-- Right column: addresses --}}
        <div class="space-y-6">
            <flux:card class="p-6">
                <div class="flex items-center justify-between">
                    <flux:heading size="md">Addresses</flux:heading>
                    <flux:button variant="ghost" size="sm" icon="plus" wire:click="openAddressForm">
                        Add address
                    </flux:button>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($this->addresses as $address)
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-zinc-800 dark:text-white">
                                        {{ $address['label'] ?: 'Address' }}
                                    </span>
                                    @if ($address['is_default'])
                                        <flux:badge color="green" size="sm">Default</flux:badge>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1">
                                    <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="openAddressForm({{ $address['id'] }})" aria-label="Edit address" />
                                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="deleteAddress({{ $address['id'] }})" aria-label="Delete address" />
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-300">
                                {{ $address['address']['line1'] ?? '' }}
                                @if (! empty($address['address']['line2']))
                                    {{ $address['address']['line2'] }},
                                @endif
                                {{ $address['address']['city'] ?? '' }}, {{ $address['address']['state'] ?? '' }} {{ $address['address']['zip'] ?? '' }}
                                {{ $address['address']['country'] ?? '' }}
                            </p>
                            @if (! $address['is_default'])
                                <flux:button variant="subtle" size="sm" class="mt-2" wire:click="setDefaultAddress({{ $address['id'] }})">
                                    Set as default
                                </flux:button>
                            @endif
                        </div>
                    @empty
                        <flux:text>No addresses yet.</flux:text>
                    @endforelse
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Address modal --}}
    <flux:modal wire:model="showAddressForm" class="max-w-md">
        <flux:heading size="lg">{{ $editingAddressId ? 'Edit address' : 'Add address' }}</flux:heading>

        <div class="mt-4 space-y-4">
            <flux:field>
                <flux:label>Label</flux:label>
                <flux:input wire:model="addressLabel" placeholder="Home, Office..." />
            </flux:field>

            <flux:field>
                <flux:label>Address line 1</flux:label>
                <flux:input wire:model="addressLine1" placeholder="123 Main St" />
                <flux:error name="addressLine1" />
            </flux:field>

            <flux:field>
                <flux:label>Address line 2</flux:label>
                <flux:input wire:model="addressLine2" placeholder="Apt 4B" />
            </flux:field>

            <div class="grid grid-cols-2 gap-3">
                <flux:field>
                    <flux:label>City</flux:label>
                    <flux:input wire:model="addressCity" />
                    <flux:error name="addressCity" />
                </flux:field>
                <flux:field>
                    <flux:label>State / Province</flux:label>
                    <flux:input wire:model="addressState" />
                </flux:field>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <flux:field>
                    <flux:label>ZIP / Postal code</flux:label>
                    <flux:input wire:model="addressZip" />
                    <flux:error name="addressZip" />
                </flux:field>
                <flux:field>
                    <flux:label>Country</flux:label>
                    <flux:select wire:model="addressCountry">
                        @foreach ($countries as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            <flux:switch wire:model="addressDefault" label="Set as default address" />
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="$set('showAddressForm', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveAddress">Save</flux:button>
        </div>
    </flux:modal>
</div>
