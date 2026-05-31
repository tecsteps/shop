<div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront::breadcrumbs :items="[
        ['label' => __('Account'), 'url' => route('account.dashboard')],
        ['label' => __('Addresses')],
    ]" />

    <div class="mt-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">{{ __('Your addresses') }}</h1>
        <flux:button wire:click="addAddress" variant="primary" icon="plus">{{ __('Add new address') }}</flux:button>
    </div>

    {{-- Add/edit form (modal-ish inline panel). --}}
    @if ($showForm)
        <div class="mt-6 rounded-xl border border-zinc-200 p-6 dark:border-zinc-800">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                {{ $editingId ? __('Edit address') : __('Add address') }}
            </h2>
            <form wire:submit="save" class="mt-4 space-y-4">
                <flux:input wire:model="label" :label="__('Label (optional)')" placeholder="{{ __('Home, Work, ...') }}" />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="form.first_name" :label="__('First name')" required />
                    <flux:input wire:model="form.last_name" :label="__('Last name')" required />
                    <div class="sm:col-span-2">
                        <flux:input wire:model="form.address1" :label="__('Address')" required />
                    </div>
                    <div class="sm:col-span-2">
                        <flux:input wire:model="form.address2" :label="__('Apartment, suite, etc. (optional)')" />
                    </div>
                    <flux:input wire:model="form.city" :label="__('City')" required />
                    <flux:input wire:model="form.province" :label="__('State / Province')" />
                    <flux:input wire:model="form.postal_code" :label="__('Postal code')" required />
                    <flux:input wire:model="form.country" :label="__('Country (2-letter code)')" maxlength="2" required />
                    <div class="sm:col-span-2">
                        <flux:input wire:model="form.phone" type="tel" :label="__('Phone (optional)')" />
                    </div>
                </div>

                <flux:checkbox wire:model="isDefault" :label="__('Set as default address')" />

                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">{{ __('Save address') }}</flux:button>
                    <flux:button type="button" wire:click="cancel" variant="ghost">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    @endif

    {{-- Address cards. --}}
    @if ($addresses->isEmpty() && ! $showForm)
        <div class="mt-12 rounded-xl border border-dashed border-zinc-300 py-16 text-center dark:border-zinc-700">
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('You have no saved addresses.') }}</p>
        </div>
    @else
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($addresses as $address)
                @php $a = $address->address_json ?? []; @endphp
                <div class="rounded-xl border p-5 @if ($address->is_default) border-blue-500 @else border-zinc-200 dark:border-zinc-800 @endif">
                    @if ($address->is_default)
                        <x-storefront::badge :text="__('Default')" variant="new" />
                    @endif
                    <address class="mt-2 text-sm not-italic text-zinc-700 dark:text-zinc-300">
                        <span class="font-medium text-zinc-900 dark:text-white">{{ trim(($a['first_name'] ?? '').' '.($a['last_name'] ?? '')) }}</span><br>
                        {{ $a['address1'] ?? '' }}<br>
                        {{ trim(($a['city'] ?? '').', '.($a['postal_code'] ?? ''), ', ') }}<br>
                        {{ $a['country'] ?? '' }}
                    </address>
                    <div class="mt-4 flex flex-wrap gap-3 text-sm">
                        <button wire:click="edit({{ $address->id }})" class="font-medium text-blue-600 hover:underline dark:text-blue-400">{{ __('Edit') }}</button>
                        <button wire:click="delete({{ $address->id }})"
                                wire:confirm="{{ __('Delete this address?') }}"
                                class="font-medium text-red-600 hover:underline dark:text-red-400">{{ __('Delete') }}</button>
                        @unless ($address->is_default)
                            <button wire:click="setDefault({{ $address->id }})" class="font-medium text-zinc-600 hover:underline dark:text-zinc-300">{{ __('Set as default') }}</button>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
