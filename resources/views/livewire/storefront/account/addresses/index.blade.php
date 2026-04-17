<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Address Book</flux:heading>
        <div class="flex items-center gap-3">
            <a href="{{ route('storefront.account.dashboard') }}">
                <flux:button variant="ghost" size="sm">Back to Account</flux:button>
            </a>
            <flux:button wire:click="openForm" variant="primary" size="sm">Add Address</flux:button>
        </div>
    </div>

    {{-- Address Form --}}
    @if ($showForm)
        <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg" class="mb-4">{{ $editingAddressId ? 'Edit Address' : 'New Address' }}</flux:heading>

            <form wire:submit="saveAddress" class="space-y-4">
                <flux:input wire:model="label" label="Label" placeholder="Home, Work, etc." required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="firstName" label="First Name" required />
                    <flux:input wire:model="lastName" label="Last Name" required />
                </div>

                <flux:input wire:model="address1" label="Address" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="city" label="City" required />
                    <flux:input wire:model="provinceCode" label="State / Province" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="countryCode" label="Country Code" required />
                    <flux:input wire:model="postalCode" label="Postal Code" required />
                </div>

                <flux:checkbox wire:model="isDefault" label="Set as default address" />

                <div class="flex items-center gap-3">
                    <flux:button type="submit" variant="primary">
                        {{ $editingAddressId ? 'Update Address' : 'Save Address' }}
                    </flux:button>
                    <flux:button wire:click="$set('showForm', false)" variant="ghost">Cancel</flux:button>
                </div>
            </form>
        </div>
    @endif

    {{-- Address List --}}
    @if ($addresses->isEmpty() && ! $showForm)
        <div class="rounded-lg border border-zinc-200 p-8 text-center dark:border-zinc-700">
            <flux:text>No addresses saved yet.</flux:text>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach ($addresses as $address)
                <div wire:key="address-{{ $address->id }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="mb-2 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ $address->label }}</span>
                            @if ($address->is_default)
                                <flux:badge size="sm" variant="solid">Default</flux:badge>
                            @endif
                        </div>
                    </div>

                    <div class="text-sm text-zinc-600 dark:text-zinc-300">
                        <p>{{ $address->address_json['first_name'] ?? '' }} {{ $address->address_json['last_name'] ?? '' }}</p>
                        <p>{{ $address->address_json['address1'] ?? '' }}</p>
                        <p>{{ $address->address_json['city'] ?? '' }}, {{ $address->address_json['province_code'] ?? '' }} {{ $address->address_json['postal_code'] ?? '' }}</p>
                        <p>{{ $address->address_json['country_code'] ?? '' }}</p>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <flux:button wire:click="openForm({{ $address->id }})" variant="ghost" size="sm">Edit</flux:button>
                        <flux:button wire:click="deleteAddress({{ $address->id }})" variant="ghost" size="sm">Delete</flux:button>
                        @if (! $address->is_default)
                            <flux:button wire:click="setDefault({{ $address->id }})" variant="ghost" size="sm">Set Default</flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
