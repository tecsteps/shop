<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Addresses') }}</h1>
        @if(!$showForm)
            <flux:button wire:click="openCreateForm" variant="primary" size="sm">
                {{ __('Add Address') }}
            </flux:button>
        @endif
    </div>

    {{-- Navigation --}}
    <nav class="mt-4 flex gap-4 border-b border-gray-200 dark:border-gray-700 pb-4">
        <a href="{{ route('customer.account') }}"
           class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            {{ __('Dashboard') }}
        </a>
        <a href="{{ route('customer.orders') }}"
           class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            {{ __('Orders') }}
        </a>
        <a href="{{ route('customer.addresses') }}"
           class="text-sm font-medium text-blue-600 dark:text-blue-400">
            {{ __('Addresses') }}
        </a>
    </nav>

    {{-- Address form --}}
    @if($showForm)
        <div class="mt-8 rounded-lg border border-gray-200 p-6 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ $editingAddressId ? __('Edit Address') : __('New Address') }}
            </h2>

            <form wire:submit="saveAddress" class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:input wire:model="label" :label="__('Label')" placeholder="Home, Work, etc." required />
                </div>

                <flux:input wire:model="first_name" :label="__('First name')" required />
                <flux:input wire:model="last_name" :label="__('Last name')" required />

                <flux:input wire:model="company" :label="__('Company')" />
                <flux:input wire:model="phone" :label="__('Phone')" />

                <div class="sm:col-span-2">
                    <flux:input wire:model="address1" :label="__('Address line 1')" required />
                </div>

                <div class="sm:col-span-2">
                    <flux:input wire:model="address2" :label="__('Address line 2')" />
                </div>

                <flux:input wire:model="city" :label="__('City')" required />
                <flux:input wire:model="province" :label="__('State / Province')" />

                <flux:input wire:model="zip" :label="__('ZIP / Postal code')" required />
                <flux:input wire:model="country" :label="__('Country code')" placeholder="US" required />

                <div class="sm:col-span-2">
                    <flux:checkbox wire:model="is_default" :label="__('Set as default address')" />
                </div>

                <div class="sm:col-span-2 flex gap-3">
                    <flux:button type="submit" variant="primary">
                        {{ $editingAddressId ? __('Update Address') : __('Save Address') }}
                    </flux:button>
                    <flux:button wire:click="cancelForm" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    {{-- Address list --}}
    @if($addresses->isEmpty() && !$showForm)
        <p class="mt-8 text-sm text-gray-500 dark:text-gray-400">{{ __('You have no saved addresses.') }}</p>
    @else
        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            @foreach($addresses as $address)
                <div wire:key="address-{{ $address->id }}" class="relative rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $address->label }}
                        </span>
                        @if($address->is_default)
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ __('Default') }}
                            </span>
                        @endif
                    </div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <p>{{ $address->address_json['first_name'] ?? '' }} {{ $address->address_json['last_name'] ?? '' }}</p>
                        <p>{{ $address->address_json['address1'] ?? '' }}</p>
                        @if($address->address_json['address2'] ?? null)
                            <p>{{ $address->address_json['address2'] }}</p>
                        @endif
                        <p>{{ $address->address_json['city'] ?? '' }}, {{ $address->address_json['province'] ?? '' }} {{ $address->address_json['zip'] ?? '' }}</p>
                    </div>
                    <div class="mt-3 flex gap-3">
                        <button wire:click="editAddress({{ $address->id }})"
                                class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                            {{ __('Edit') }}
                        </button>
                        @if(!$address->is_default)
                            <button wire:click="setDefault({{ $address->id }})"
                                    class="text-xs font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                                {{ __('Set as default') }}
                            </button>
                        @endif
                        <button wire:click="deleteAddress({{ $address->id }})"
                                wire:confirm="{{ __('Are you sure you want to delete this address?') }}"
                                class="text-xs font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                            {{ __('Delete') }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
