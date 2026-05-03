<div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-3xl font-semibold tracking-normal">Your addresses</h1>
        <button type="button" wire:click="startCreating" class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">
            Add new address
        </button>
    </div>

    @if($showForm)
        <form wire:submit="save" class="mt-8 grid gap-4 rounded-lg border border-zinc-200 p-5 dark:border-zinc-800 sm:grid-cols-2">
            <input wire:model="label" placeholder="Label" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 sm:col-span-2">
            <input wire:model="address.first_name" placeholder="First name" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            <input wire:model="address.last_name" placeholder="Last name" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            <input wire:model="address.company" placeholder="Company" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 sm:col-span-2">
            <input wire:model="address.address1" placeholder="Address" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 sm:col-span-2">
            <input wire:model="address.address2" placeholder="Apartment, suite, etc." class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 sm:col-span-2">
            <input wire:model="address.city" placeholder="City" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            <input wire:model="address.postal_code" placeholder="Postal code" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            <input wire:model="address.province" placeholder="Region" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            <input wire:model="address.country_code" placeholder="Country code" maxlength="2" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm uppercase dark:border-zinc-700 dark:bg-zinc-900">
            <label class="flex items-center gap-3 text-sm sm:col-span-2">
                <input wire:model="isDefault" type="checkbox" class="size-4 rounded border-zinc-300">
                <span>Set as default address</span>
            </label>
            <div class="flex gap-3 sm:col-span-2">
                <button type="submit" class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">Save address</button>
                <button type="button" wire:click="cancel" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold dark:border-zinc-700">Cancel</button>
            </div>
        </form>
    @endif

    <div class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse($addresses as $customerAddress)
            @php($addressData = $customerAddress->address_json ?? [])
            <article wire:key="address-{{ $customerAddress->id }}" class="rounded-lg border p-5 text-sm {{ $customerAddress->is_default ? 'border-zinc-950 dark:border-white' : 'border-zinc-200 dark:border-zinc-800' }}">
                <div class="flex items-start justify-between gap-4">
                    <h2 class="font-semibold">{{ $customerAddress->label ?? 'Address' }}</h2>
                    @if($customerAddress->is_default)
                        <span class="rounded-full bg-zinc-950 px-2 py-1 text-xs font-semibold text-white dark:bg-white dark:text-zinc-950">Default</span>
                    @endif
                </div>
                <div class="mt-4 text-zinc-700 dark:text-zinc-300">
                    @include('storefront.components.address', ['address' => $addressData])
                </div>
                <div class="mt-5 flex flex-wrap gap-3">
                    <button type="button" wire:click="startEditing({{ $customerAddress->id }})" class="font-semibold underline underline-offset-4">Edit</button>
                    <button type="button" wire:click="deleteAddress({{ $customerAddress->id }})" wire:confirm="Delete this address?" class="font-semibold underline underline-offset-4">Delete</button>
                    @if(! $customerAddress->is_default)
                        <button type="button" wire:click="setDefault({{ $customerAddress->id }})" class="font-semibold underline underline-offset-4">Set default</button>
                    @endif
                </div>
            </article>
        @empty
            <p class="text-sm text-zinc-600 dark:text-zinc-400">No saved addresses.</p>
        @endforelse
    </div>
</div>
