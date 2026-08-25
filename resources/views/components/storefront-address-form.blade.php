@props([
    'address' => null,
    'prefix' => '',
    'showPhone' => true,
])

@php
    $prefix = $prefix !== '' ? rtrim($prefix, '.').'.' : '';
    $address = $address ?? [];

    $countries = [
        ['code' => 'DE', 'name' => 'Germany'],
        ['code' => 'AT', 'name' => 'Austria'],
        ['code' => 'BE', 'name' => 'Belgium'],
        ['code' => 'CH', 'name' => 'Switzerland'],
        ['code' => 'DK', 'name' => 'Denmark'],
        ['code' => 'ES', 'name' => 'Spain'],
        ['code' => 'FI', 'name' => 'Finland'],
        ['code' => 'FR', 'name' => 'France'],
        ['code' => 'GB', 'name' => 'United Kingdom'],
        ['code' => 'IE', 'name' => 'Ireland'],
        ['code' => 'IT', 'name' => 'Italy'],
        ['code' => 'LU', 'name' => 'Luxembourg'],
        ['code' => 'NL', 'name' => 'Netherlands'],
        ['code' => 'NO', 'name' => 'Norway'],
        ['code' => 'PL', 'name' => 'Poland'],
        ['code' => 'PT', 'name' => 'Portugal'],
        ['code' => 'SE', 'name' => 'Sweden'],
        ['code' => 'US', 'name' => 'United States'],
        ['code' => 'CA', 'name' => 'Canada'],
        ['code' => 'AU', 'name' => 'Australia'],
    ];

    $input = 'block w-full rounded-lg border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 transition focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder-zinc-500 dark:focus:border-white dark:focus:ring-white/20';
    $label = 'mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300';
    $error = 'mt-1.5 text-sm text-red-600 dark:text-red-400';
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label for="{{ $prefix }}first_name" class="{{ $label }}">
            First name <span class="text-red-500" aria-hidden="true">*</span>
        </label>
        <input
            type="text"
            id="{{ $prefix }}first_name"
            wire:model="{{ $prefix }}first_name"
            autocomplete="given-name"
            required
            class="{{ $input }}"
            aria-describedby="{{ $prefix }}first_name_error"
        />
        @error($prefix.'first_name')
            <p id="{{ $prefix }}first_name_error" class="{{ $error }}">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}last_name" class="{{ $label }}">
            Last name <span class="text-red-500" aria-hidden="true">*</span>
        </label>
        <input
            type="text"
            id="{{ $prefix }}last_name"
            wire:model="{{ $prefix }}last_name"
            autocomplete="family-name"
            required
            class="{{ $input }}"
            aria-describedby="{{ $prefix }}last_name_error"
        />
        @error($prefix.'last_name')
            <p id="{{ $prefix }}last_name_error" class="{{ $error }}">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="{{ $prefix }}company" class="{{ $label }}">Company <span class="text-zinc-400 dark:text-zinc-500">(optional)</span></label>
        <input
            type="text"
            id="{{ $prefix }}company"
            wire:model="{{ $prefix }}company"
            autocomplete="organization"
            class="{{ $input }}"
        />
        @error($prefix.'company')
            <p id="{{ $prefix }}company_error" class="{{ $error }}">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="{{ $prefix }}address1" class="{{ $label }}">
            Address <span class="text-red-500" aria-hidden="true">*</span>
        </label>
        <input
            type="text"
            id="{{ $prefix }}address1"
            wire:model="{{ $prefix }}address1"
            autocomplete="address-line1"
            placeholder="Street address"
            required
            class="{{ $input }}"
            aria-describedby="{{ $prefix }}address1_error"
        />
        @error($prefix.'address1')
            <p id="{{ $prefix }}address1_error" class="{{ $error }}">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="{{ $prefix }}address2" class="{{ $label }}">Apartment, suite, etc. <span class="text-zinc-400 dark:text-zinc-500">(optional)</span></label>
        <input
            type="text"
            id="{{ $prefix }}address2"
            wire:model="{{ $prefix }}address2"
            autocomplete="address-line2"
            class="{{ $input }}"
        />
        @error($prefix.'address2')
            <p id="{{ $prefix }}address2_error" class="{{ $error }}">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}city" class="{{ $label }}">
            City <span class="text-red-500" aria-hidden="true">*</span>
        </label>
        <input
            type="text"
            id="{{ $prefix }}city"
            wire:model="{{ $prefix }}city"
            autocomplete="address-level2"
            required
            class="{{ $input }}"
            aria-describedby="{{ $prefix }}city_error"
        />
        @error($prefix.'city')
            <p id="{{ $prefix }}city_error" class="{{ $error }}">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}province" class="{{ $label }}">State / Province</label>
        <input
            type="text"
            id="{{ $prefix }}province"
            wire:model="{{ $prefix }}province"
            autocomplete="address-level1"
            class="{{ $input }}"
        />
        @error($prefix.'province')
            <p id="{{ $prefix }}province_error" class="{{ $error }}">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}country_code" class="{{ $label }}">
            Country <span class="text-red-500" aria-hidden="true">*</span>
        </label>
        <select
            id="{{ $prefix }}country_code"
            wire:model.live="{{ $prefix }}country_code"
            required
            class="{{ $input }}"
            aria-describedby="{{ $prefix }}country_code_error"
        >
            <option value="">Select a country</option>
            @foreach ($countries as $country)
                <option value="{{ $country['code'] }}">{{ $country['name'] }}</option>
            @endforeach
        </select>
        @error($prefix.'country_code')
            <p id="{{ $prefix }}country_code_error" class="{{ $error }}">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}postal_code" class="{{ $label }}">
            Postal code <span class="text-red-500" aria-hidden="true">*</span>
        </label>
        <input
            type="text"
            id="{{ $prefix }}postal_code"
            wire:model="{{ $prefix }}postal_code"
            autocomplete="postal-code"
            required
            class="{{ $input }}"
            aria-describedby="{{ $prefix }}postal_code_error"
        />
        @error($prefix.'postal_code')
            <p id="{{ $prefix }}postal_code_error" class="{{ $error }}">{{ $message }}</p>
        @enderror
    </div>

    @if ($showPhone)
        <div class="sm:col-span-2">
            <label for="{{ $prefix }}phone" class="{{ $label }}">Phone <span class="text-zinc-400 dark:text-zinc-500">(optional)</span></label>
            <input
                type="tel"
                id="{{ $prefix }}phone"
                wire:model="{{ $prefix }}phone"
                autocomplete="tel"
                class="{{ $input }}"
            />
            @error($prefix.'phone')
                <p id="{{ $prefix }}phone_error" class="{{ $error }}">{{ $message }}</p>
            @enderror
        </div>
    @endif
</div>
