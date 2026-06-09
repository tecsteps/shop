{{--
    Renders a full address form. Inputs bind to Livewire via the given model
    prefix (e.g. prefix="shipping" binds shipping.first_name). Used by the
    Phase 4 checkout and account address book.
--}}
@props([
    'address' => null,
    'prefix' => '',
])

@php
    $field = fn (string $name): string => $prefix === '' ? $name : "{$prefix}.{$name}";

    $inputClasses = 'block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder-zinc-500';
    $labelClasses = 'mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300';

    $countries = [
        'DE' => 'Germany',
        'AT' => 'Austria',
        'BE' => 'Belgium',
        'FR' => 'France',
        'IT' => 'Italy',
        'NL' => 'Netherlands',
        'ES' => 'Spain',
        'GB' => 'United Kingdom',
        'US' => 'United States',
    ];
@endphp

<div {{ $attributes->class('grid grid-cols-1 gap-4 sm:grid-cols-2') }}>
    <div>
        <label for="{{ $field('first_name') }}" class="{{ $labelClasses }}">
            {{ __('First name') }} <span class="text-red-600" aria-hidden="true">*</span>
        </label>
        <input
            id="{{ $field('first_name') }}"
            type="text"
            wire:model.blur="{{ $field('first_name') }}"
            value="{{ $address['first_name'] ?? '' }}"
            required
            autocomplete="given-name"
            class="{{ $inputClasses }}"
            @error($field('first_name')) aria-invalid="true" aria-describedby="{{ $field('first_name') }}-error" @enderror
        />
        @error($field('first_name'))
            <p id="{{ $field('first_name') }}-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $field('last_name') }}" class="{{ $labelClasses }}">
            {{ __('Last name') }} <span class="text-red-600" aria-hidden="true">*</span>
        </label>
        <input
            id="{{ $field('last_name') }}"
            type="text"
            wire:model.blur="{{ $field('last_name') }}"
            value="{{ $address['last_name'] ?? '' }}"
            required
            autocomplete="family-name"
            class="{{ $inputClasses }}"
            @error($field('last_name')) aria-invalid="true" aria-describedby="{{ $field('last_name') }}-error" @enderror
        />
        @error($field('last_name'))
            <p id="{{ $field('last_name') }}-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="{{ $field('address1') }}" class="{{ $labelClasses }}">
            {{ __('Address line 1') }} <span class="text-red-600" aria-hidden="true">*</span>
        </label>
        <input
            id="{{ $field('address1') }}"
            type="text"
            wire:model.blur="{{ $field('address1') }}"
            value="{{ $address['address1'] ?? '' }}"
            required
            autocomplete="address-line1"
            class="{{ $inputClasses }}"
            @error($field('address1')) aria-invalid="true" aria-describedby="{{ $field('address1') }}-error" @enderror
        />
        @error($field('address1'))
            <p id="{{ $field('address1') }}-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="{{ $field('address2') }}" class="{{ $labelClasses }}">{{ __('Address line 2') }}</label>
        <input
            id="{{ $field('address2') }}"
            type="text"
            wire:model.blur="{{ $field('address2') }}"
            value="{{ $address['address2'] ?? '' }}"
            autocomplete="address-line2"
            class="{{ $inputClasses }}"
        />
    </div>

    <div>
        <label for="{{ $field('city') }}" class="{{ $labelClasses }}">
            {{ __('City') }} <span class="text-red-600" aria-hidden="true">*</span>
        </label>
        <input
            id="{{ $field('city') }}"
            type="text"
            wire:model.blur="{{ $field('city') }}"
            value="{{ $address['city'] ?? '' }}"
            required
            autocomplete="address-level2"
            class="{{ $inputClasses }}"
            @error($field('city')) aria-invalid="true" aria-describedby="{{ $field('city') }}-error" @enderror
        />
        @error($field('city'))
            <p id="{{ $field('city') }}-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $field('province') }}" class="{{ $labelClasses }}">{{ __('State / Province') }}</label>
        <input
            id="{{ $field('province') }}"
            type="text"
            wire:model.blur="{{ $field('province') }}"
            value="{{ $address['province'] ?? '' }}"
            autocomplete="address-level1"
            class="{{ $inputClasses }}"
        />
    </div>

    <div>
        <label for="{{ $field('postal_code') }}" class="{{ $labelClasses }}">
            {{ __('Postal code') }} <span class="text-red-600" aria-hidden="true">*</span>
        </label>
        <input
            id="{{ $field('postal_code') }}"
            type="text"
            wire:model.blur="{{ $field('postal_code') }}"
            value="{{ $address['postal_code'] ?? '' }}"
            required
            autocomplete="postal-code"
            class="{{ $inputClasses }}"
            @error($field('postal_code')) aria-invalid="true" aria-describedby="{{ $field('postal_code') }}-error" @enderror
        />
        @error($field('postal_code'))
            <p id="{{ $field('postal_code') }}-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $field('country_code') }}" class="{{ $labelClasses }}">
            {{ __('Country') }} <span class="text-red-600" aria-hidden="true">*</span>
        </label>
        <select
            id="{{ $field('country_code') }}"
            wire:model.blur="{{ $field('country_code') }}"
            required
            autocomplete="country"
            class="{{ $inputClasses }}"
        >
            <option value="">{{ __('Select a country') }}</option>
            @foreach ($countries as $code => $country)
                <option value="{{ $code }}" @selected(($address['country_code'] ?? null) === $code)>{{ $country }}</option>
            @endforeach
        </select>
        @error($field('country_code'))
            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="{{ $field('phone') }}" class="{{ $labelClasses }}">{{ __('Phone') }}</label>
        <input
            id="{{ $field('phone') }}"
            type="tel"
            wire:model.blur="{{ $field('phone') }}"
            value="{{ $address['phone'] ?? '' }}"
            autocomplete="tel"
            class="{{ $inputClasses }}"
        />
    </div>
</div>
