<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('customer.dashboard') }}" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300" wire:navigate>
                <flux:icon name="arrow-left" class="size-5" />
            </a>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Addresses</h1>
        </div>
        @unless ($showForm)
            <flux:button wire:click="showAddForm" variant="primary" size="sm">
                Add New Address
            </flux:button>
        @endunless
    </div>

    @if ($showForm)
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-8">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                {{ $editingAddressId ? 'Edit Address' : 'New Address' }}
            </h2>
            <form wire:submit="saveAddress" class="space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <flux:input wire:model="first_name" label="First name" required />
                    <flux:input wire:model="last_name" label="Last name" required />
                </div>
                <flux:input wire:model="address1" label="Address" required />
                <flux:input wire:model="address2" label="Apartment, suite, etc." />
                <div class="grid sm:grid-cols-2 gap-4">
                    <flux:input wire:model="city" label="City" required />
                    <flux:input wire:model="province" label="Province / State" />
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <flux:input wire:model="postal_code" label="Postal code" required />
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Country</label>
                        <select wire:model="country_code" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="DE">Germany</option>
                            <option value="AT">Austria</option>
                            <option value="CH">Switzerland</option>
                            <option value="FR">France</option>
                            <option value="NL">Netherlands</option>
                            <option value="BE">Belgium</option>
                            <option value="IT">Italy</option>
                            <option value="ES">Spain</option>
                            <option value="GB">United Kingdom</option>
                            <option value="US">United States</option>
                        </select>
                    </div>
                </div>
                <flux:input wire:model="phone" label="Phone" />
                <flux:checkbox wire:model="is_default" label="Set as default address" />

                <div class="flex items-center gap-3 pt-2">
                    <flux:button type="submit" variant="primary">
                        {{ $editingAddressId ? 'Update Address' : 'Save Address' }}
                    </flux:button>
                    <flux:button wire:click="cancelForm" type="button" variant="ghost">
                        Cancel
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    @if ($addresses->isEmpty() && !$showForm)
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <p class="text-zinc-500 dark:text-zinc-400">You have no saved addresses.</p>
        </div>
    @else
        <div class="grid sm:grid-cols-2 gap-4">
            @foreach ($addresses as $address)
                <div wire:key="address-{{ $address->id }}" class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    @if ($address->is_default)
                        <x-storefront.badge variant="new" class="mb-2">Default</x-storefront.badge>
                    @endif
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 space-y-1 mb-4">
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $address->first_name }} {{ $address->last_name }}</p>
                        <p>{{ $address->address1 }}</p>
                        @if ($address->address2)
                            <p>{{ $address->address2 }}</p>
                        @endif
                        <p>{{ $address->postal_code }} {{ $address->city }}, {{ $address->country_code }}</p>
                        @if ($address->phone)
                            <p>{{ $address->phone }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:button wire:click="editAddress({{ $address->id }})" size="sm" variant="ghost">Edit</flux:button>
                        @unless ($address->is_default)
                            <flux:button wire:click="setDefault({{ $address->id }})" size="sm" variant="ghost">Set as default</flux:button>
                        @endunless
                        <flux:button wire:click="deleteAddress({{ $address->id }})" size="sm" variant="ghost" class="text-red-600 dark:text-red-400" wire:confirm="Are you sure you want to delete this address?">Delete</flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
