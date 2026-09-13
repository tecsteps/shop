<div class="mx-auto max-w-7xl px-4 py-8">
    <x-storefront.breadcrumbs :items="[['label' => 'Account', 'url' => route('customer.account')], ['label' => 'Addresses']]" />
    <div class="mt-4 flex items-center justify-between">
        <h1 class="text-3xl font-bold">Your Addresses</h1>
        <flux:button wire:click="openCreate" variant="primary">Add new address</flux:button>
    </div>

    @if ($addresses->isEmpty())
        <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">You have no saved addresses yet.</p>
    @else
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($addresses as $record)
                @php $a = (array) $record->address_json; @endphp
                <div wire:key="address-{{ $record->getKey() }}" class="rounded-lg border p-4 {{ $record->is_default ? 'border-blue-600' : 'border-zinc-200 dark:border-zinc-800' }}">
                    @if ($record->is_default)
                        <x-storefront.badge text="Default" variant="new" />
                    @endif
                    <p class="mt-2 text-sm font-semibold">{{ $record->label ?? 'Address' }}</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $a['first_name'] ?? '' }} {{ $a['last_name'] ?? '' }}<br />
                        {{ $a['address1'] ?? '' }}<br />
                        {{ $a['city'] ?? '' }} {{ $a['postal_code'] ?? '' }}<br />
                        {{ $a['country'] ?? $a['country_code'] ?? '' }}
                    </p>
                    <div class="mt-3 flex flex-wrap gap-3 text-sm">
                        <button type="button" wire:click="openEdit({{ $record->getKey() }})" class="text-blue-600 hover:underline dark:text-blue-400">Edit</button>
                        @if ($confirmingDelete === $record->getKey())
                            <button type="button" wire:click="delete" class="text-red-600 hover:underline">Confirm delete</button>
                        @else
                            <button type="button" wire:click="confirmDelete({{ $record->getKey() }})" class="text-zinc-500 hover:underline dark:text-zinc-400">Delete</button>
                        @endif
                        @if (! $record->is_default)
                            <button type="button" wire:click="setDefault({{ $record->getKey() }})" class="text-zinc-500 hover:underline dark:text-zinc-400">Set as default</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal wire:model="showModal" class="max-w-2xl">
        <flux:heading>{{ $editingId ? 'Edit address' : 'Add new address' }}</flux:heading>
        <div class="mt-4 flex flex-col gap-4">
            <flux:input wire:model="label" :label="__('Label (e.g. Home, Work)')" />
            <x-storefront.address-form prefix="address" />
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="save" variant="primary">Save address</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
