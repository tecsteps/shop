<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'My Account', 'url' => route('customer.dashboard')],
        ['label' => 'Addresses'],
    ]" class="mb-8" />

    <div class="lg:grid lg:grid-cols-12 lg:gap-x-8">
        {{-- Sidebar --}}
        <aside class="lg:col-span-3 mb-8 lg:mb-0">
            @include('livewire.storefront.account.partials.sidebar')
        </aside>

        {{-- Main Content --}}
        <div class="lg:col-span-9">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Addresses</h1>
                @if(!$showForm)
                    <flux:button wire:click="createAddress" variant="primary" size="sm">
                        Add Address
                    </flux:button>
                @endif
            </div>

            {{-- Address Form --}}
            @if($showForm)
                <div class="mt-6 rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                        {{ $editingAddressId ? 'Edit Address' : 'New Address' }}
                    </h2>

                    <form wire:submit="saveAddress" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input
                            wire:model="first_name"
                            :label="__('First name')"
                            type="text"
                            required
                        />

                        <flux:input
                            wire:model="last_name"
                            :label="__('Last name')"
                            type="text"
                            required
                        />

                        <div class="sm:col-span-2">
                            <flux:input
                                wire:model="company"
                                :label="__('Company')"
                                type="text"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <flux:input
                                wire:model="address1"
                                :label="__('Address')"
                                type="text"
                                required
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <flux:input
                                wire:model="address2"
                                :label="__('Apartment, suite, etc.')"
                                type="text"
                            />
                        </div>

                        <flux:input
                            wire:model="city"
                            :label="__('City')"
                            type="text"
                            required
                        />

                        <flux:input
                            wire:model="province_code"
                            :label="__('State / Province')"
                            type="text"
                        />

                        <flux:input
                            wire:model="country_code"
                            :label="__('Country code')"
                            type="text"
                            required
                            maxlength="2"
                        />

                        <flux:input
                            wire:model="postal_code"
                            :label="__('Postal code')"
                            type="text"
                            required
                        />

                        <div class="sm:col-span-2">
                            <flux:input
                                wire:model="phone"
                                :label="__('Phone')"
                                type="tel"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <flux:checkbox wire:model="is_default" :label="__('Set as default address')" />
                        </div>

                        <div class="sm:col-span-2 flex items-center gap-3">
                            <flux:button type="submit" variant="primary">
                                {{ $editingAddressId ? 'Update Address' : 'Save Address' }}
                            </flux:button>
                            <flux:button wire:click="cancelForm" variant="ghost">
                                Cancel
                            </flux:button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Address List --}}
            @if($addresses->isEmpty() && !$showForm)
                <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-800/50">
                    <svg class="mx-auto h-12 w-12 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                    <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">You haven't added any addresses yet.</p>
                </div>
            @elseif($addresses->isNotEmpty())
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach($addresses as $address)
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700 {{ $address->is_default ? 'ring-2 ring-zinc-900 dark:ring-white' : '' }}">
                            @if($address->is_default)
                                <span class="mb-2 inline-flex items-center rounded-full bg-zinc-900 px-2.5 py-0.5 text-xs font-medium text-white dark:bg-white dark:text-zinc-900">
                                    Default
                                </span>
                            @endif
                            <div class="text-sm text-zinc-900 dark:text-white">
                                <p class="font-medium">{{ $address->first_name }} {{ $address->last_name }}</p>
                                @if($address->company)
                                    <p class="text-zinc-600 dark:text-zinc-400">{{ $address->company }}</p>
                                @endif
                                <p class="mt-1">{{ $address->address1 }}</p>
                                @if($address->address2)
                                    <p>{{ $address->address2 }}</p>
                                @endif
                                <p>{{ $address->city }}{{ $address->province_code ? ', ' . $address->province_code : '' }} {{ $address->postal_code }}</p>
                                <p>{{ $address->country_code }}</p>
                                @if($address->phone)
                                    <p class="mt-1 text-zinc-600 dark:text-zinc-400">{{ $address->phone }}</p>
                                @endif
                            </div>
                            <div class="mt-4 flex items-center gap-3">
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
                                        class="text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
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
