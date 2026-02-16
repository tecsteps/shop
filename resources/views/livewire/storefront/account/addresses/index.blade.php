<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Addresses</h1>
        <div class="flex gap-3">
            <a href="{{ url('/account') }}" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Back to account</a>
            <flux:button wire:click="openForm" variant="primary" size="sm">Add address</flux:button>
        </div>
    </div>

    @if($showForm)
        <x-admin.card class="mb-6">
            <h3 class="mb-4 font-semibold text-gray-900 dark:text-white">{{ $editingId ? 'Edit' : 'New' }} Address</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="first_name" label="First name" required />
                    <flux:input wire:model="last_name" label="Last name" required />
                    <flux:input wire:model="company" label="Company" />
                    <flux:input wire:model="phone" label="Phone" />
                </div>
                <flux:input wire:model="address1" label="Address" required />
                <flux:input wire:model="address2" label="Apartment, suite, etc." />
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <flux:input wire:model="city" label="City" required />
                    <flux:input wire:model="province" label="State / Province" />
                    <flux:input wire:model="zip" label="ZIP / Postal code" required />
                </div>
                <flux:input wire:model="country" label="Country" required />
                <flux:checkbox wire:model="is_default" label="Set as default address" />
                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">Save address</flux:button>
                    <flux:button wire:click="$set('showForm', false)" variant="ghost">Cancel</flux:button>
                </div>
            </form>
        </x-admin.card>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @forelse($addresses as $address)
            <x-admin.card>
                @if($address->is_default)
                    <flux:badge size="sm" variant="primary" class="mb-2">Default</flux:badge>
                @endif
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    <p class="font-medium">{{ $address->first_name }} {{ $address->last_name }}</p>
                    <p>{{ $address->address1 }}</p>
                    @if($address->address2) <p>{{ $address->address2 }}</p> @endif
                    <p>{{ $address->city }}, {{ $address->province }} {{ $address->zip }}</p>
                    <p>{{ $address->country }}</p>
                </div>
                <div class="mt-3 flex gap-2">
                    <flux:button wire:click="openForm({{ $address->id }})" variant="ghost" size="sm">Edit</flux:button>
                    @unless($address->is_default)
                        <flux:button wire:click="setDefault({{ $address->id }})" variant="ghost" size="sm">Set default</flux:button>
                    @endunless
                    <flux:button wire:click="delete({{ $address->id }})" variant="danger" size="sm">Delete</flux:button>
                </div>
            </x-admin.card>
        @empty
            <p class="col-span-2 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No saved addresses.</p>
        @endforelse
    </div>
</div>
