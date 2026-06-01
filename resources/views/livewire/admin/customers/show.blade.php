@php
    use App\Support\Storefront\PriceFormatter;

    $currency = $currentStore->default_currency ?? 'USD';
    $financialColors = ['pending' => 'zinc', 'paid' => 'green', 'refunded' => 'yellow', 'partially_refunded' => 'yellow'];
@endphp

<div>
    <x-admin.breadcrumbs :items="[
        ['label' => __('Customers'), 'href' => route('admin.customers.index')],
        ['label' => $customer->name ?: $customer->email],
    ]" />

    <flux:heading size="xl" level="1" class="mb-6">{{ $customer->name ?: __('Customer') }}</flux:heading>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="{{ __('Customer info') }}">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Name') }}</dt><dd>{{ $customer->name ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Email') }}</dt><dd>{{ $customer->email }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Created') }}</dt><dd>{{ $customer->created_at?->format('M j, Y') }}</dd></div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">{{ __('Marketing') }}</dt>
                        <dd><flux:badge size="sm" :color="$customer->marketing_opt_in ? 'green' : 'zinc'">{{ $customer->marketing_opt_in ? __('Opted In') : __('Opted Out') }}</flux:badge></dd>
                    </div>
                </dl>
            </x-admin.card>

            <x-admin.card title="{{ __('Order history') }}">
                @if ($this->orders->isEmpty())
                    <flux:text>{{ __('No orders yet.') }}</flux:text>
                @else
                    <flux:table :paginate="$this->orders">
                        <flux:table.columns>
                            <flux:table.column>{{ __('Order #') }}</flux:table.column>
                            <flux:table.column>{{ __('Date') }}</flux:table.column>
                            <flux:table.column>{{ __('Status') }}</flux:table.column>
                            <flux:table.column class="text-right">{{ __('Total') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($this->orders as $order)
                                <flux:table.row :key="'o-'.$order->id">
                                    <flux:table.cell variant="strong"><flux:link :href="route('admin.orders.show', $order)" wire:navigate>{{ $order->order_number }}</flux:link></flux:table.cell>
                                    <flux:table.cell>{{ $order->placed_at?->format('M j, Y') }}</flux:table.cell>
                                    <flux:table.cell><flux:badge size="sm" :color="$financialColors[$order->financial_status->value] ?? 'zinc'">{{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}</flux:badge></flux:table.cell>
                                    <flux:table.cell class="text-right">{{ PriceFormatter::format($order->total_amount, $order->currency) }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card>
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="md">{{ __('Addresses') }}</flux:heading>
                    <flux:button size="sm" variant="ghost" icon="plus" wire:click="openAddressForm" data-test="add-address">{{ __('Add') }}</flux:button>
                </div>

                @forelse ($customer->addresses as $address)
                    <div class="mb-3 rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700" wire:key="addr-{{ $address->id }}">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">{{ $address->label }}</span>
                            @if ($address->is_default)<flux:badge size="sm" color="green">{{ __('Default') }}</flux:badge>@endif
                        </div>
                        <address class="mt-1 not-italic text-zinc-600 dark:text-zinc-400">
                            {{ $address->address_json['address1'] ?? '' }}<br>
                            {{ $address->address_json['city'] ?? '' }} {{ $address->address_json['postal_code'] ?? '' }}<br>
                            {{ $address->address_json['country'] ?? '' }}
                        </address>
                        <div class="mt-2 flex gap-2">
                            <flux:button size="xs" variant="ghost" wire:click="openAddressForm({{ $address->id }})">{{ __('Edit') }}</flux:button>
                            @unless ($address->is_default)
                                <flux:button size="xs" variant="ghost" wire:click="setDefaultAddress({{ $address->id }})">{{ __('Set default') }}</flux:button>
                            @endunless
                            <flux:button size="xs" variant="ghost" wire:click="deleteAddress({{ $address->id }})" wire:confirm="{{ __('Delete this address?') }}">{{ __('Delete') }}</flux:button>
                        </div>
                    </div>
                @empty
                    <flux:text class="text-sm">{{ __('No saved addresses.') }}</flux:text>
                @endforelse
            </x-admin.card>
        </div>
    </div>

    <flux:modal wire:model.self="showAddressModal" name="address-form" class="md:w-[28rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingAddressId ? __('Edit address') : __('Add address') }}</flux:heading>
            <flux:field>
                <flux:label>{{ __('Label') }}</flux:label>
                <flux:input wire:model="addressLabel" placeholder="Home, Office..." />
                <flux:error name="addressLabel" />
            </flux:field>
            <div class="grid grid-cols-2 gap-3">
                <flux:field><flux:label>{{ __('First name') }}</flux:label><flux:input wire:model="addressJson.first_name" /></flux:field>
                <flux:field><flux:label>{{ __('Last name') }}</flux:label><flux:input wire:model="addressJson.last_name" /></flux:field>
            </div>
            <flux:field><flux:label>{{ __('Address line 1') }}</flux:label><flux:input wire:model="addressJson.address1" /><flux:error name="addressJson.address1" /></flux:field>
            <flux:field><flux:label>{{ __('Address line 2') }}</flux:label><flux:input wire:model="addressJson.address2" /></flux:field>
            <div class="grid grid-cols-2 gap-3">
                <flux:field><flux:label>{{ __('City') }}</flux:label><flux:input wire:model="addressJson.city" /><flux:error name="addressJson.city" /></flux:field>
                <flux:field><flux:label>{{ __('State/Province') }}</flux:label><flux:input wire:model="addressJson.province_code" /></flux:field>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <flux:field><flux:label>{{ __('ZIP/Postal') }}</flux:label><flux:input wire:model="addressJson.postal_code" /></flux:field>
                <flux:field><flux:label>{{ __('Country') }}</flux:label><flux:input wire:model="addressJson.country" placeholder="US" /><flux:error name="addressJson.country" /></flux:field>
            </div>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showAddressModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="saveAddress" data-test="save-address">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
