@props([
    // Pre-filled address data (array) or null.
    'address' => null,
    // Livewire model prefix, e.g. "shipping" -> wire:model="shipping.first_name".
    'prefix' => '',
])

@php
    $name = fn (string $field): string => $prefix !== '' ? $prefix.'.'.$field : $field;
    $value = fn (string $field) => is_array($address) ? ($address[$field] ?? '') : '';
    $countries = ['US' => 'United States', 'CA' => 'Canada', 'GB' => 'United Kingdom', 'DE' => 'Germany', 'FR' => 'France', 'AU' => 'Australia'];
@endphp

<div {{ $attributes->merge(['class' => 'grid grid-cols-1 gap-4 sm:grid-cols-2']) }}>
    <div>
        <flux:input :label="__('First name')" wire:model="{{ $name('first_name') }}" value="{{ $value('first_name') }}" autocomplete="given-name" required />
    </div>
    <div>
        <flux:input :label="__('Last name')" wire:model="{{ $name('last_name') }}" value="{{ $value('last_name') }}" autocomplete="family-name" required />
    </div>
    <div class="sm:col-span-2">
        <flux:input :label="__('Address')" wire:model="{{ $name('address_line1') }}" value="{{ $value('address_line1') }}" autocomplete="address-line1" required />
    </div>
    <div class="sm:col-span-2">
        <flux:input :label="__('Apartment, suite, etc. (optional)')" wire:model="{{ $name('address_line2') }}" value="{{ $value('address_line2') }}" autocomplete="address-line2" />
    </div>
    <div>
        <flux:input :label="__('City')" wire:model="{{ $name('city') }}" value="{{ $value('city') }}" autocomplete="address-level2" required />
    </div>
    <div>
        <flux:input :label="__('State / Province')" wire:model="{{ $name('province') }}" value="{{ $value('province') }}" autocomplete="address-level1" required />
    </div>
    <div>
        <flux:input :label="__('Postal code')" wire:model="{{ $name('postal_code') }}" value="{{ $value('postal_code') }}" autocomplete="postal-code" required />
    </div>
    <div>
        <flux:select :label="__('Country')" wire:model="{{ $name('country') }}" required>
            @foreach ($countries as $code => $label)
                <flux:select.option value="{{ $code }}" :selected="$value('country') === $code">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>
    <div class="sm:col-span-2">
        <flux:input :label="__('Phone (optional)')" type="tel" wire:model="{{ $name('phone') }}" value="{{ $value('phone') }}" autocomplete="tel" />
    </div>
</div>
