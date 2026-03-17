<div>
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront.breadcrumbs :items="[
            ['label' => 'Account', 'url' => route('storefront.account.dashboard')],
            ['label' => 'Addresses'],
        ]" />

        <div class="mt-4 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Your Addresses</h1>
            <button wire:click="openAddForm"
                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500">
                + Add new address
            </button>
        </div>

        {{-- Address Form Modal --}}
        @if($showForm)
            <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $editingAddressId ? 'Edit Address' : 'Add New Address' }}
                </h2>
                <form wire:submit="saveAddress" class="mt-4 space-y-4">
                    <div>
                        <label for="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Label</label>
                        <input type="text" wire:model="label" id="label" placeholder="e.g. Home, Work"
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="firstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">First name *</label>
                            <input type="text" wire:model="firstName" id="firstName" required
                                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            @error('firstName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="lastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last name *</label>
                            <input type="text" wire:model="lastName" id="lastName" required
                                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            @error('lastName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="company" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Company</label>
                        <input type="text" wire:model="company" id="company"
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>

                    <div>
                        <label for="address1" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address *</label>
                        <input type="text" wire:model="address1" id="address1" required
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        @error('address1') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="address2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Apartment, suite, etc.</label>
                        <input type="text" wire:model="address2" id="address2"
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300">City *</label>
                            <input type="text" wire:model="city" id="city" required
                                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="zip" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Postal code *</label>
                            <input type="text" wire:model="zip" id="zip" required
                                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            @error('zip') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="province" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Province/State</label>
                            <input type="text" wire:model="province" id="province"
                                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        </div>
                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Country *</label>
                            <input type="text" wire:model="country" id="country" required
                                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            @error('country') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                        <input type="text" wire:model="phone" id="phone"
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>

                    <div>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model="isDefault" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Set as default address</span>
                        </label>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500">
                            {{ $editingAddressId ? 'Update Address' : 'Add Address' }}
                        </button>
                        <button type="button" wire:click="cancelForm" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Address Cards --}}
        @if($addresses->isNotEmpty())
            <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($addresses as $address)
                    <div wire:key="address-{{ $address->id }}"
                         @class([
                             'rounded-lg border p-4',
                             'border-blue-500 dark:border-blue-400' => $address->is_default,
                             'border-gray-200 dark:border-gray-800' => ! $address->is_default,
                         ])>
                        @if($address->is_default)
                            <x-storefront.badge variant="info">Default</x-storefront.badge>
                        @endif
                        @if($address->label)
                            <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ $address->label }}</p>
                        @endif
                        <div class="mt-2 space-y-0.5 text-sm text-gray-600 dark:text-gray-400">
                            <p>{{ ($address->address_json['first_name'] ?? '').' '.($address->address_json['last_name'] ?? '') }}</p>
                            <p>{{ $address->address_json['address1'] ?? '' }}</p>
                            @if(! empty($address->address_json['address2']))
                                <p>{{ $address->address_json['address2'] }}</p>
                            @endif
                            <p>{{ ($address->address_json['city'] ?? '').', '.($address->address_json['zip'] ?? '') }}</p>
                            <p>{{ $address->address_json['country'] ?? '' }}</p>
                        </div>
                        <div class="mt-3 flex gap-3">
                            <button wire:click="editAddress({{ $address->id }})" class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400">Edit</button>
                            <button wire:click="deleteAddress({{ $address->id }})"
                                    wire:confirm="Are you sure you want to delete this address?"
                                    class="text-sm text-red-600 hover:text-red-500 dark:text-red-400">Delete</button>
                            @if(! $address->is_default)
                                <button wire:click="setDefault({{ $address->id }})" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Set as default</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif(! $showForm)
            <div class="mt-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No addresses yet</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Add an address to speed up your checkout.</p>
            </div>
        @endif
    </div>
</div>
