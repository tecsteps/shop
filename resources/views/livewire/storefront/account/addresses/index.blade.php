<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('storefront.account') }}">Account</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Addresses</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Your Addresses</flux:heading>
        <flux:button wire:click="openAddForm" variant="primary" size="sm">Add new address</flux:button>
    </div>

    @if($addresses->isEmpty())
        <p class="mt-6 text-zinc-500 dark:text-zinc-400">You have no saved addresses yet.</p>
    @else
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach($addresses as $address)
                <div wire:key="address-{{ $address->id }}"
                     @class([
                         'rounded-lg border p-4',
                         'border-zinc-800 dark:border-zinc-200' => $address->is_default,
                         'border-zinc-200 dark:border-zinc-700' => ! $address->is_default,
                     ])>
                    <div class="flex items-start justify-between">
                        <div>
                            @if($address->label)
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $address->label }}</p>
                            @endif
                            @if($address->is_default)
                                <flux:badge color="zinc" size="sm">Default</flux:badge>
                            @endif
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>{{ data_get($address->address_json, 'first_name') }} {{ data_get($address->address_json, 'last_name') }}</p>
                        <p>{{ data_get($address->address_json, 'address1') }}</p>
                        @if(data_get($address->address_json, 'address2'))
                            <p>{{ data_get($address->address_json, 'address2') }}</p>
                        @endif
                        <p>{{ data_get($address->address_json, 'city') }}, {{ data_get($address->address_json, 'zip') }}</p>
                        <p>{{ data_get($address->address_json, 'country') }}</p>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <flux:button wire:click="editAddress({{ $address->id }})" size="sm" variant="ghost">Edit</flux:button>
                        <flux:button wire:click="deleteAddress({{ $address->id }})" wire:confirm="Are you sure you want to delete this address?" size="sm" variant="ghost">Delete</flux:button>
                        @if(! $address->is_default)
                            <flux:button wire:click="setDefault({{ $address->id }})" size="sm" variant="ghost">Set as default</flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Address Form Modal --}}
    <flux:modal wire:model="showModal">
        <flux:heading>{{ $editingAddressId ? 'Edit Address' : 'Add New Address' }}</flux:heading>

        <form wire:submit="saveAddress" class="mt-4 space-y-4">
            <flux:field>
                <flux:label>Label (optional)</flux:label>
                <flux:input wire:model="label" placeholder="e.g. Home, Work" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>First name</flux:label>
                    <flux:input wire:model="form.first_name" />
                    <flux:error name="form.first_name" />
                </flux:field>
                <flux:field>
                    <flux:label>Last name</flux:label>
                    <flux:input wire:model="form.last_name" />
                    <flux:error name="form.last_name" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Company (optional)</flux:label>
                <flux:input wire:model="form.company" />
            </flux:field>

            <flux:field>
                <flux:label>Address</flux:label>
                <flux:input wire:model="form.address1" />
                <flux:error name="form.address1" />
            </flux:field>

            <flux:field>
                <flux:label>Apartment, suite, etc. (optional)</flux:label>
                <flux:input wire:model="form.address2" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>City</flux:label>
                    <flux:input wire:model="form.city" />
                    <flux:error name="form.city" />
                </flux:field>
                <flux:field>
                    <flux:label>Province / State (optional)</flux:label>
                    <flux:input wire:model="form.province" />
                </flux:field>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Postal code</flux:label>
                    <flux:input wire:model="form.zip" />
                    <flux:error name="form.zip" />
                </flux:field>
                <flux:field>
                    <flux:label>Country</flux:label>
                    <flux:input wire:model="form.country" placeholder="e.g. US, DE" />
                    <flux:error name="form.country" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Phone (optional)</flux:label>
                <flux:input wire:model="form.phone" type="tel" />
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
