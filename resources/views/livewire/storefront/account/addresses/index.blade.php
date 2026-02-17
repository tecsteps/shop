<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <a href="{{ route('storefront.account.dashboard') }}" class="text-sm text-blue-600 hover:underline dark:text-blue-400">&larr; Back to Account</a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Address Book</h1>
        </div>
        @if(!$showForm)
            <button wire:click="openNewForm"
                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Add Address
            </button>
        @endif
    </div>

    {{-- Address form --}}
    @if($showForm)
        <div class="mb-8 rounded-lg border border-gray-200 p-6 dark:border-gray-700">
            <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                {{ $editingAddressId ? 'Edit Address' : 'New Address' }}
            </h2>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label for="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Label</label>
                    <input type="text" wire:model="label" id="label" placeholder="e.g. Home, Work"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="firstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">First Name *</label>
                        <input type="text" wire:model="firstName" id="firstName" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">
                        @error('firstName') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="lastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Name *</label>
                        <input type="text" wire:model="lastName" id="lastName" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">
                        @error('lastName') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div>
                    <label for="company" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Company</label>
                    <input type="text" wire:model="company" id="company"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">
                </div>
                <div>
                    <label for="address1" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address *</label>
                    <input type="text" wire:model="address1" id="address1" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">
                    @error('address1') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="address2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address Line 2</label>
                    <input type="text" wire:model="address2" id="address2"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300">City *</label>
                        <input type="text" wire:model="city" id="city" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">
                        @error('city') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="province" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Province / State</label>
                        <input type="text" wire:model="province" id="province"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="countryCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Country Code *</label>
                        <input type="text" wire:model="countryCode" id="countryCode" required placeholder="e.g. US, DE"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">
                        @error('countryCode') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="zip" class="block text-sm font-medium text-gray-700 dark:text-gray-300">ZIP / Postal Code *</label>
                        <input type="text" wire:model="zip" id="zip" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">
                        @error('zip') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                    <input type="text" wire:model="phone" id="phone"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="isDefault" id="isDefault"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800">
                    <label for="isDefault" class="text-sm text-gray-700 dark:text-gray-300">Set as default address</label>
                </div>
                <div class="flex gap-3">
                    <button type="submit"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        {{ $editingAddressId ? 'Update Address' : 'Save Address' }}
                    </button>
                    <button type="button" wire:click="cancel"
                            class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Address list --}}
    @if($addresses->isEmpty() && !$showForm)
        <div class="rounded-lg border border-gray-200 p-12 text-center dark:border-gray-700">
            <p class="text-gray-600 dark:text-gray-400">You have not saved any addresses yet.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach($addresses as $address)
                @php $data = $address->address_json ?? []; @endphp
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700 {{ $address->is_default ? 'ring-2 ring-blue-500' : '' }}">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $address->label ?? 'Address' }}
                            @if($address->is_default)
                                <span class="ml-2 inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">Default</span>
                            @endif
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $data['first_name'] ?? '' }} {{ $data['last_name'] ?? '' }}<br>
                        {{ $data['address1'] ?? '' }}<br>
                        @if(!empty($data['address2'])){{ $data['address2'] }}<br>@endif
                        {{ $data['city'] ?? '' }}, {{ $data['zip'] ?? '' }}<br>
                        {{ $data['country'] ?? $data['country_code'] ?? '' }}
                    </p>
                    <div class="mt-3 flex gap-3">
                        <button wire:click="editAddress({{ $address->id }})" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Edit</button>
                        @if(!$address->is_default)
                            <button wire:click="setDefault({{ $address->id }})" class="text-sm text-gray-600 hover:underline dark:text-gray-400">Set as Default</button>
                        @endif
                        <button wire:click="deleteAddress({{ $address->id }})"
                                wire:confirm="Are you sure you want to delete this address?"
                                class="text-sm text-red-600 hover:underline dark:text-red-400">Delete</button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
