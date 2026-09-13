@props(['prefix' => 'address', 'showPhone' => true])

<div class="grid grid-cols-2 gap-4">
    <div class="col-span-1">
        <flux:input wire:model="{{ $prefix }}.first_name" :label="__('First name')" required autocomplete="given-name" />
        @error($prefix.'.first_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="col-span-1">
        <flux:input wire:model="{{ $prefix }}.last_name" :label="__('Last name')" required autocomplete="family-name" />
        @error($prefix.'.last_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="col-span-2">
        <flux:input wire:model="{{ $prefix }}.address1" :label="__('Address line 1')" required autocomplete="street-address" />
        @error($prefix.'.address1') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="col-span-2">
        <flux:input wire:model="{{ $prefix }}.address2" :label="__('Address line 2')" autocomplete="address-line2" />
        @error($prefix.'.address2') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="col-span-1">
        <flux:input wire:model="{{ $prefix }}.city" :label="__('City')" required autocomplete="address-level2" />
        @error($prefix.'.city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="col-span-1">
        <flux:input wire:model="{{ $prefix }}.province" :label="__('State / Province')" autocomplete="address-level1" />
        @error($prefix.'.province') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="col-span-1">
        <flux:input wire:model="{{ $prefix }}.postal_code" :label="__('Postal code')" required autocomplete="postal-code" />
        @error($prefix.'.postal_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="col-span-1">
        <label for="{{ $prefix }}-country" class="mb-1 block text-sm font-medium">Country *</label>
        <select id="{{ $prefix }}-country" wire:model="{{ $prefix }}.country_code" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            <option value="">Select a country...</option>
            @foreach (['DE' => 'Germany', 'AT' => 'Austria', 'FR' => 'France', 'NL' => 'Netherlands', 'BE' => 'Belgium', 'IT' => 'Italy', 'ES' => 'Spain', 'US' => 'United States', 'GB' => 'United Kingdom', 'CH' => 'Switzerland'] as $code => $name)
                <option value="{{ $code }}">{{ $name }}</option>
            @endforeach
        </select>
        @error($prefix.'.country_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    @if ($showPhone)
        <div class="col-span-2">
            <flux:input wire:model="{{ $prefix }}.phone" :label="__('Phone')" type="tel" autocomplete="tel" />
            @error($prefix.'.phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    @endif
</div>
