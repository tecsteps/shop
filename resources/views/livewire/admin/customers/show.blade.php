@php
    use App\Support\Storefront\Countries;
    use App\Support\Storefront\PriceFormatter;

    $customer = $this->customer;
    $currency = $currentStore->default_currency ?? 'EUR';
@endphp

<div class="space-y-6">
    <x-admin.breadcrumbs :items="[
        ['label' => __('Customers'), 'href' => route('admin.customers.index')],
        ['label' => $customer->name ?: $customer->email],
    ]" />

    <flux:heading size="xl" level="1">{{ $customer->name ?: $customer->email }}</flux:heading>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- LEFT COLUMN (2/3) --}}
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card :heading="__('Customer info')">
                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</dt>
                        <dd class="font-medium text-zinc-800 dark:text-zinc-200">{{ $customer->name ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</dt>
                        <dd class="font-medium text-zinc-800 dark:text-zinc-200">{{ $customer->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Created') }}</dt>
                        <dd class="font-medium text-zinc-800 dark:text-zinc-200">{{ $customer->created_at?->format('M j, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Marketing') }}</dt>
                        <dd>
                            <flux:badge size="sm" :color="$customer->marketing_opt_in ? 'green' : 'zinc'">
                                {{ $customer->marketing_opt_in ? __('Opted in') : __('Opted out') }}
                            </flux:badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Orders') }}</dt>
                        <dd class="font-medium text-zinc-800 dark:text-zinc-200">{{ $customer->orders_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Total spent') }}</dt>
                        <dd class="font-medium text-zinc-800 dark:text-zinc-200">
                            {{ PriceFormatter::format((int) ($customer->orders_sum_total_amount ?? 0), $currency) }}
                        </dd>
                    </div>
                </dl>
            </x-admin.card>

            <x-admin.card class="!p-0">
                <div class="p-6 pb-4">
                    <flux:heading>{{ __('Order history') }}</flux:heading>
                </div>

                @if ($this->orders->isEmpty())
                    <div class="px-6 pb-6">
                        <flux:text>{{ __('No orders yet.') }}</flux:text>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-y border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                                    <th class="px-6 py-2.5">{{ __('Order') }}</th>
                                    <th class="px-4 py-2.5">{{ __('Date') }}</th>
                                    <th class="px-4 py-2.5">{{ __('Status') }}</th>
                                    <th class="px-6 py-2.5 text-right">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($this->orders as $order)
                                    <tr wire:key="customer-order-{{ $order->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                        <td class="px-6 py-3">
                                            <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                                                {{ $order->order_number }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y') }}</td>
                                        <td class="px-4 py-3"><x-admin.status-badge :status="$order->financial_status" /></td>
                                        <td class="px-6 py-3 text-right font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ PriceFormatter::format($order->total_amount, $order->currency) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($this->orders->hasPages())
                        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                            {{ $this->orders->links() }}
                        </div>
                    @endif
                @endif
            </x-admin.card>
        </div>

        {{-- RIGHT COLUMN (1/3) --}}
        <div class="space-y-6">
            <x-admin.card :heading="__('Addresses')">
                <div class="space-y-4">
                    @forelse ($customer->addresses as $address)
                        @php($json = $address->address_json ?? [])
                        <div wire:key="address-{{ $address->id }}" class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                                    {{ $address->label ?: __('Address') }}
                                </p>
                                @if ($address->is_default)
                                    <flux:badge size="sm" color="blue">{{ __('Default') }}</flux:badge>
                                @endif
                            </div>

                            <div class="mt-1 space-y-0.5 text-sm text-zinc-600 dark:text-zinc-400">
                                @if (filled(trim(($json['first_name'] ?? '').' '.($json['last_name'] ?? ''))))
                                    <p>{{ trim(($json['first_name'] ?? '').' '.($json['last_name'] ?? '')) }}</p>
                                @endif
                                @if (filled($json['address1'] ?? null))<p>{{ $json['address1'] }}</p>@endif
                                @if (filled($json['address2'] ?? null))<p>{{ $json['address2'] }}</p>@endif
                                <p>{{ trim(collect([$json['zip'] ?? null, $json['city'] ?? null])->filter()->implode(' ')) }}</p>
                                @if (filled($json['country_code'] ?? null))
                                    <p>{{ Countries::name($json['country_code']) }}</p>
                                @endif
                            </div>

                            @can('update', $customer)
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <flux:button variant="ghost" size="xs" wire:click="openAddressForm({{ $address->id }})" data-test="edit-address-{{ $address->id }}">
                                        {{ __('Edit') }}
                                    </flux:button>
                                    @unless ($address->is_default)
                                        <flux:button variant="ghost" size="xs" wire:click="setDefaultAddress({{ $address->id }})">
                                            {{ __('Set default') }}
                                        </flux:button>
                                    @endunless
                                    <flux:button variant="ghost" size="xs" class="!text-red-600 dark:!text-red-400" wire:click="deleteAddress({{ $address->id }})" wire:confirm="{{ __('Delete this address?') }}">
                                        {{ __('Delete') }}
                                    </flux:button>
                                </div>
                            @endcan
                        </div>
                    @empty
                        <flux:text>{{ __('No addresses on file.') }}</flux:text>
                    @endforelse

                    @can('update', $customer)
                        <flux:button variant="ghost" size="sm" icon="plus" wire:click="openAddressForm" data-test="add-address-button">
                            {{ __('Add address') }}
                        </flux:button>
                    @endcan
                </div>
            </x-admin.card>
        </div>
    </div>

    {{-- Address form modal --}}
    <flux:modal name="address-form" class="md:max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">
                {{ $editingAddressId !== null ? __('Edit address') : __('Add address') }}
            </flux:heading>

            <flux:field>
                <flux:label>{{ __('Label') }}</flux:label>
                <flux:input wire:model="addressLabel" :placeholder="__('Home, Office...')" />
            </flux:field>

            <div class="grid grid-cols-2 gap-3">
                <flux:field>
                    <flux:label>{{ __('First name') }}</flux:label>
                    <flux:input wire:model="addressFields.first_name" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('Last name') }}</flux:label>
                    <flux:input wire:model="addressFields.last_name" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('Address line 1') }}</flux:label>
                <flux:input wire:model="addressFields.address1" data-test="address-line1-input" />
                <flux:error name="addressFields.address1" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Address line 2') }}</flux:label>
                <flux:input wire:model="addressFields.address2" />
            </flux:field>

            <div class="grid grid-cols-2 gap-3">
                <flux:field>
                    <flux:label>{{ __('City') }}</flux:label>
                    <flux:input wire:model="addressFields.city" />
                    <flux:error name="addressFields.city" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('State / Province') }}</flux:label>
                    <flux:input wire:model="addressFields.province" />
                </flux:field>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <flux:field>
                    <flux:label>{{ __('ZIP / Postal code') }}</flux:label>
                    <flux:input wire:model="addressFields.zip" />
                    <flux:error name="addressFields.zip" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('Country') }}</flux:label>
                    <flux:select wire:model="addressFields.country_code">
                        @foreach (Countries::OPTIONS as $code => $name)
                            <flux:select.option value="{{ $code }}">{{ $name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="addressFields.country_code" />
                </flux:field>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="saveAddress" data-test="save-address-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
