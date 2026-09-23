@php
    $address = $address ?? [];
    $prefix = $prefix ?? '';
    $addressField = static fn (string $field): string => $prefix !== '' ? $prefix.'.'.$field : $field;
    $addressId = static fn (string $field): string => str_replace(['.', '_'], '-', $addressField($field));
    $countries = $countries ?? ['DE' => 'Germany', 'AT' => 'Austria', 'CH' => 'Switzerland', 'FR' => 'France', 'GB' => 'United Kingdom', 'US' => 'United States'];
    $fields = [
        ['first_name', 'First name', 'text', true, 'given-name', false],
        ['last_name', 'Last name', 'text', true, 'family-name', false],
        ['address_line_1', 'Address line 1', 'text', true, 'address-line1', true],
        ['address_line_2', 'Address line 2', 'text', false, 'address-line2', true],
        ['city', 'City', 'text', true, 'address-level2', false],
        ['state', 'State / Province', 'text', true, 'address-level1', false],
        ['postal_code', 'Postal code', 'text', true, 'postal-code', false],
    ];
@endphp
<div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
    @foreach ($fields as [$field, $label, $type, $required, $autocomplete, $fullWidth])
        <div class="{{ $fullWidth ? 'sm:col-span-2' : '' }}">
            <label for="{{ $addressId($field) }}" class="mb-1.5 block text-sm font-medium text-stone-800 dark:text-stone-200">{{ $label }}@if ($required) <span class="text-rose-700" aria-hidden="true">*</span><span class="sr-only"> (required)</span>@endif</label>
            <input id="{{ $addressId($field) }}" name="{{ $addressField($field) }}" type="{{ $type }}" wire:model="{{ $addressField($field) }}" autocomplete="{{ $autocomplete }}" @required($required) @if (data_get($address, $field)) value="{{ data_get($address, $field) }}" @endif aria-describedby="{{ $addressId($field) }}-error" class="storefront-input">
            @error($addressField($field)) <p id="{{ $addressId($field) }}-error" class="mt-1.5 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p> @enderror
        </div>
    @endforeach
    <div>
        <label for="{{ $addressId('country') }}" class="mb-1.5 block text-sm font-medium text-stone-800 dark:text-stone-200">Country <span class="text-rose-700" aria-hidden="true">*</span><span class="sr-only"> (required)</span></label>
        <select id="{{ $addressId('country') }}" name="{{ $addressField('country') }}" wire:model.live="{{ $addressField('country') }}" autocomplete="country" required class="storefront-input">
            <option value="">Choose a country</option>
            @foreach ($countries as $code => $countryName)
                <option value="{{ is_int($code) ? $countryName : $code }}">{{ is_int($code) ? $countryName : $countryName }}</option>
            @endforeach
        </select>
        @error($addressField('country')) <p id="{{ $addressId('country') }}-error" class="mt-1.5 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="{{ $addressId('phone') }}" class="mb-1.5 block text-sm font-medium text-stone-800 dark:text-stone-200">Phone <span class="text-stone-500 dark:text-stone-400">(optional)</span></label>
        <input id="{{ $addressId('phone') }}" name="{{ $addressField('phone') }}" type="tel" wire:model="{{ $addressField('phone') }}" autocomplete="tel" class="storefront-input" @if (data_get($address, 'phone')) value="{{ data_get($address, 'phone') }}" @endif>
        @error($addressField('phone')) <p id="{{ $addressId('phone') }}-error" class="mt-1.5 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p> @enderror
    </div>
</div>
