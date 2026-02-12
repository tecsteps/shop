<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Addresses</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage your shipping and billing addresses.</p>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('storefront.account.dashboard') }}" wire:navigate
               class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                &larr; Back to account
            </a>
            <flux:button wire:click="openCreateModal" variant="primary" size="sm">
                Add address
            </flux:button>
        </div>
    </div>

    @if($addresses->isEmpty())
        <div class="text-center py-16 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800">
            <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="mt-4 text-gray-500 dark:text-gray-400">You have not added any addresses yet.</p>
            <flux:button wire:click="openCreateModal" variant="primary" size="sm" class="mt-4">
                Add your first address
            </flux:button>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($addresses as $address)
                <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-4 relative">
                    @if($address->is_default)
                        <span class="absolute top-3 right-3 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                            Default
                        </span>
                    @endif

                    <div class="text-sm text-gray-700 dark:text-gray-300 space-y-0.5 pr-16">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $address->first_name }} {{ $address->last_name }}</p>
                        @if($address->company)
                            <p>{{ $address->company }}</p>
                        @endif
                        <p>{{ $address->address1 }}</p>
                        @if($address->address2)
                            <p>{{ $address->address2 }}</p>
                        @endif
                        <p>{{ $address->zip }} {{ $address->city }}</p>
                        @if($address->province)
                            <p>{{ $address->province }}</p>
                        @endif
                        <p>{{ $address->country }}</p>
                        @if($address->phone)
                            <p class="text-gray-500 dark:text-gray-400">{{ $address->phone }}</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <button wire:click="openEditModal({{ $address->id }})"
                                class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            Edit
                        </button>
                        @if(!$address->is_default)
                            <button wire:click="setDefault({{ $address->id }})"
                                    class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                                Set as default
                            </button>
                        @endif
                        <button wire:click="delete({{ $address->id }})"
                                wire:confirm="Are you sure you want to delete this address?"
                                class="text-sm text-red-600 dark:text-red-400 hover:underline">
                            Delete
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Address modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center"
             x-data
             x-trap.noscroll="true"
             @keydown.escape.window="$wire.set('showModal', false)">
            <div class="fixed inset-0 bg-black/50" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $editingAddressId ? 'Edit Address' : 'Add Address' }}
                    </h2>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="save" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="first_name" label="First name" required />
                        <flux:input wire:model="last_name" label="Last name" required />
                    </div>

                    <flux:input wire:model="company" label="Company (optional)" />
                    <flux:input wire:model="address1" label="Address" required />
                    <flux:input wire:model="address2" label="Apartment, suite, etc. (optional)" />

                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="city" label="City" required />
                        <flux:input wire:model="province" label="Province / State" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="country" label="Country" required />
                        <flux:input wire:model="country_code" label="Country code" required placeholder="DE" maxlength="2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="zip" label="Postal code" required />
                        <flux:input wire:model="phone" label="Phone (optional)" />
                    </div>

                    <flux:checkbox wire:model="is_default" label="Set as default address" />

                    <div class="flex justify-end gap-3 pt-4">
                        <flux:button wire:click="$set('showModal', false)" variant="ghost">
                            Cancel
                        </flux:button>
                        <flux:button type="submit" variant="primary">
                            {{ $editingAddressId ? 'Update address' : 'Add address' }}
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
