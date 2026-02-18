<div>
    <x-slot:title>My Addresses</x-slot:title>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold">My Addresses</h1>
            <div class="flex items-center gap-4">
                <a href="{{ route('customer.dashboard') }}" class="text-sm text-blue-600 hover:text-blue-500">&larr; Back to Account</a>
                @if (! $showForm)
                    <button wire:click="openCreateForm" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Add Address
                    </button>
                @endif
            </div>
        </div>

        @if ($showForm)
            <div class="mt-8 rounded-lg border border-gray-200 p-6 dark:border-gray-700">
                <h2 class="text-lg font-semibold">{{ $editingAddressId ? 'Edit Address' : 'New Address' }}</h2>
                <form wire:submit="save" class="mt-4 space-y-4">
                    <div>
                        <label for="label" class="block text-sm font-medium dark:text-gray-200">Label (optional)</label>
                        <input wire:model="label" type="text" id="label" placeholder="e.g. Home, Work" class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('label') <span class="mt-1 text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="firstName" class="block text-sm font-medium dark:text-gray-200">First Name</label>
                            <input wire:model="firstName" type="text" id="firstName" class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                            @error('firstName') <span class="mt-1 text-sm text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="lastName" class="block text-sm font-medium dark:text-gray-200">Last Name</label>
                            <input wire:model="lastName" type="text" id="lastName" class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                            @error('lastName') <span class="mt-1 text-sm text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="address1" class="block text-sm font-medium dark:text-gray-200">Address Line 1</label>
                        <input wire:model="address1" type="text" id="address1" class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                        @error('address1') <span class="mt-1 text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="address2" class="block text-sm font-medium dark:text-gray-200">Address Line 2 (optional)</label>
                        <input wire:model="address2" type="text" id="address2" class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="city" class="block text-sm font-medium dark:text-gray-200">City</label>
                            <input wire:model="city" type="text" id="city" class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                            @error('city') <span class="mt-1 text-sm text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="zip" class="block text-sm font-medium dark:text-gray-200">Postal Code</label>
                            <input wire:model="zip" type="text" id="zip" class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                            @error('zip') <span class="mt-1 text-sm text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="countryCode" class="block text-sm font-medium dark:text-gray-200">Country Code</label>
                            <input wire:model="countryCode" type="text" id="countryCode" maxlength="2" class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                            @error('countryCode') <span class="mt-1 text-sm text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium dark:text-gray-200">Phone (optional)</label>
                        <input wire:model="phone" type="tel" id="phone" class="mt-1 w-full rounded border px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input wire:model="isDefault" type="checkbox" class="mr-2 rounded">
                            <span class="text-sm dark:text-gray-200">Set as default address</span>
                        </label>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            {{ $editingAddressId ? 'Update Address' : 'Save Address' }}
                        </button>
                        <button type="button" wire:click="cancel" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        @endif

        @if ($addresses->isEmpty() && ! $showForm)
            <div class="mt-8 rounded-lg border border-gray-200 p-8 text-center dark:border-gray-700">
                <p class="text-gray-500 dark:text-gray-400">No addresses saved yet.</p>
                <button wire:click="openCreateForm" class="mt-4 text-sm font-medium text-blue-600 hover:text-blue-500">
                    Add your first address
                </button>
            </div>
        @else
            <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($addresses as $address)
                    <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-700">
                        <div class="flex items-start justify-between">
                            <div>
                                @if ($address->label)
                                    <span class="text-sm font-semibold">{{ $address->label }}</span>
                                @endif
                                @if ($address->is_default)
                                    <span class="ml-1 inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200">Default</span>
                                @endif
                            </div>
                        </div>
                        <address class="mt-2 text-sm not-italic text-gray-600 dark:text-gray-400">
                            {{ $address->address_json['first_name'] ?? '' }} {{ $address->address_json['last_name'] ?? '' }}<br>
                            {{ $address->address_json['address1'] ?? '' }}<br>
                            @if (! empty($address->address_json['address2']))
                                {{ $address->address_json['address2'] }}<br>
                            @endif
                            {{ $address->address_json['zip'] ?? '' }} {{ $address->address_json['city'] ?? '' }}<br>
                            {{ $address->address_json['country_code'] ?? '' }}
                        </address>
                        <div class="mt-4 flex gap-3">
                            <button wire:click="openEditForm({{ $address->id }})" class="text-sm font-medium text-blue-600 hover:text-blue-500">Edit</button>
                            <button wire:click="deleteAddress({{ $address->id }})" wire:confirm="Are you sure you want to delete this address?" class="text-sm font-medium text-red-600 hover:text-red-500">Delete</button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
