<div class="flex flex-col gap-8">
    <header>
        <h1 class="text-3xl font-semibold tracking-tight">Addresses</h1>
    </header>

    <section class="flex flex-col gap-4">
        <h2 class="text-lg font-semibold">Saved addresses</h2>
        @if ($addresses->isEmpty())
            <p class="text-sm text-neutral-500 dark:text-neutral-400">No addresses yet.</p>
        @else
            <ul class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ($addresses as $address)
                    @php $data = $address->address_json ?? []; @endphp
                    <li wire:key="address-{{ $address->id }}" class="flex flex-col gap-2 rounded-lg border border-neutral-200 bg-white p-5 text-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <div class="flex items-center justify-between">
                            <div class="font-medium">{{ $address->label ?: 'Address' }}</div>
                            @if ($address->is_default)
                                <span class="rounded bg-neutral-900 px-2 py-0.5 text-xs text-white dark:bg-white dark:text-neutral-900">Default</span>
                            @endif
                        </div>
                        <p class="text-neutral-700 dark:text-neutral-300">
                            {{ ($data['first_name'] ?? '').' '.($data['last_name'] ?? '') }}<br>
                            {{ $data['address1'] ?? '' }}<br>
                            {{ $data['city'] ?? '' }} {{ $data['postal_code'] ?? '' }}<br>
                            {{ $data['country_code'] ?? '' }}
                        </p>
                        <div class="flex gap-3 pt-2">
                            <button wire:click="edit({{ $address->id }})" class="text-sm font-medium underline">Edit</button>
                            <button wire:click="delete({{ $address->id }})" class="text-sm font-medium text-red-600 underline">Delete</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="flex flex-col gap-4 rounded-lg border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
        <h2 class="text-lg font-semibold">
            {{ $editingId ? 'Edit address' : 'Add a new address' }}
        </h2>
        <form wire:submit="save" class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <flux:input wire:model="label" label="Label" />
            <flux:input wire:model="first_name" label="First name" required />
            <flux:input wire:model="last_name" label="Last name" required />
            <flux:input wire:model="address1" label="Address" required class="md:col-span-2" />
            <flux:input wire:model="city" label="City" required />
            <flux:input wire:model="province_code" label="State/Province" />
            <flux:input wire:model="postal_code" label="Postal code" required />
            <flux:input wire:model="country_code" label="Country (2-letter)" required />
            <div class="md:col-span-2">
                <flux:checkbox wire:model="is_default" label="Set as default address" />
            </div>
            <div class="flex gap-3 md:col-span-2">
                <flux:button type="submit" variant="primary">{{ $editingId ? 'Save changes' : 'Save address' }}</flux:button>
                @if ($editingId)
                    <flux:button type="button" wire:click="cancel" variant="ghost">Cancel</flux:button>
                @endif
            </div>
        </form>
    </section>
</div>
