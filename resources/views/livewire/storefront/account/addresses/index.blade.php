<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Address Book</h1>

    <div class="mt-6 flex flex-col gap-8 lg:flex-row">
        @include('livewire.storefront.account.partials.account-nav')

        <div class="flex-1 space-y-6">
            @if(!$showForm)
                <div class="flex justify-end">
                    <button wire:click="openAddForm"
                            class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                        Add Address
                    </button>
                </div>
            @endif

            @if($showForm)
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                        {{ $editingAddressId ? 'Edit Address' : 'New Address' }}
                    </h2>

                    <form wire:submit="saveAddress" class="mt-4 space-y-4">
                        <div>
                            <label for="label" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Label</label>
                            <input wire:model="label" type="text" id="label" placeholder="e.g. Home, Work"
                                   class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            @error('label') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="firstName" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">First Name *</label>
                                <input wire:model="firstName" type="text" id="firstName" required
                                       class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                @error('firstName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="lastName" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Last Name *</label>
                                <input wire:model="lastName" type="text" id="lastName" required
                                       class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                @error('lastName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="address1" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Address Line 1 *</label>
                            <input wire:model="address1" type="text" id="address1" required
                                   class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            @error('address1') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="address2" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Address Line 2</label>
                            <input wire:model="address2" type="text" id="address2"
                                   class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            @error('address2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label for="city" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">City *</label>
                                <input wire:model="city" type="text" id="city" required
                                       class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                @error('city') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="province" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Province/State</label>
                                <input wire:model="province" type="text" id="province"
                                       class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                @error('province') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="postalCode" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Postal Code *</label>
                                <input wire:model="postalCode" type="text" id="postalCode" required
                                       class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                @error('postalCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="countryCode" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Country Code *</label>
                                <input wire:model="countryCode" type="text" id="countryCode" required maxlength="2"
                                       class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                @error('countryCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Phone</label>
                                <input wire:model="phone" type="text" id="phone"
                                       class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <input wire:model="isDefault" type="checkbox" id="isDefault"
                                   class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800">
                            <label for="isDefault" class="text-sm text-zinc-700 dark:text-zinc-300">Set as default address</label>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit"
                                    class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                                {{ $editingAddressId ? 'Update Address' : 'Save Address' }}
                            </button>
                            <button type="button" wire:click="cancelForm"
                                    class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            @if($this->addresses->isEmpty() && !$showForm)
                <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">You have no saved addresses.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach($this->addresses as $address)
                        @php $addr = $address->address_json; @endphp
                        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="flex items-start justify-between">
                                <div>
                                    @if($address->label)
                                        <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $address->label }}</span>
                                    @endif
                                    @if($address->is_default)
                                        <span class="ml-1 inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">Default</span>
                                    @endif
                                </div>
                            </div>
                            <address class="mt-2 text-sm not-italic text-zinc-600 dark:text-zinc-400">
                                {{ $addr['first_name'] ?? '' }} {{ $addr['last_name'] ?? '' }}<br>
                                {{ $addr['address1'] ?? '' }}<br>
                                @if(!empty($addr['address2'])){{ $addr['address2'] }}<br>@endif
                                {{ $addr['postal_code'] ?? '' }} {{ $addr['city'] ?? '' }}<br>
                                {{ $addr['country_code'] ?? '' }}
                                @if(!empty($addr['phone']))<br>{{ $addr['phone'] }}@endif
                            </address>
                            <div class="mt-3 flex gap-2">
                                <button wire:click="editAddress({{ $address->id }})"
                                        class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                    Edit
                                </button>
                                @if(!$address->is_default)
                                    <button wire:click="setDefault({{ $address->id }})"
                                            class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                        Set as default
                                    </button>
                                @endif
                                <button wire:click="deleteAddress({{ $address->id }})"
                                        wire:confirm="Are you sure you want to delete this address?"
                                        class="text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                    Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
