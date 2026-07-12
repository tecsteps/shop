@props([
    'address' => null,
    'prefix' => '',
    'countries' => null,
])

@php
    $errors ??= new \Illuminate\Support\ViewErrorBag();
    $address = is_array($address) ? $address : (array) ($address ?? []);
    $prefix = trim((string) $prefix, '.');
    $fieldName = static fn (string $field): string => $prefix === '' ? $field : $prefix.'.'.$field;
    $idPrefix = trim(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $prefix), '-') ?: 'address';
    $fieldId = static fn (string $field): string => $idPrefix.'-'.str_replace('_', '-', $field);
    $autocompleteSection = in_array($prefix, ['shipping', 'billing'], true) ? $prefix.' ' : '';
    $valueFor = static function (string $field, mixed $default = '') use ($address): mixed {
        return match ($field) {
            'postal_code' => data_get($address, 'postal_code', data_get($address, 'zip', $default)),
            'country' => data_get($address, 'country_code', data_get($address, 'country', $default)),
            default => data_get($address, $field, $default),
        };
    };

    $countries ??= [
        'DE' => __('Germany'),
        'AT' => __('Austria'),
        'BE' => __('Belgium'),
        'DK' => __('Denmark'),
        'FR' => __('France'),
        'IT' => __('Italy'),
        'NL' => __('Netherlands'),
        'PL' => __('Poland'),
        'PT' => __('Portugal'),
        'ES' => __('Spain'),
        'SE' => __('Sweden'),
        'CH' => __('Switzerland'),
        'GB' => __('United Kingdom'),
        'US' => __('United States'),
        'CA' => __('Canada'),
        'AU' => __('Australia'),
    ];

    $inputClass = 'storefront-field-input';
    $labelClass = 'storefront-field-label';
@endphp

<fieldset {{ $attributes->class('grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2') }}>
    <legend class="sr-only">{{ __('Address') }}</legend>

    @foreach ([
        ['first_name', __('First name'), 'given-name', true],
        ['last_name', __('Last name'), 'family-name', true],
    ] as [$field, $label, $autocomplete, $required])
        @php($model = $fieldName($field))
        <div>
            <label for="{{ $fieldId($field) }}" class="{{ $labelClass }}">
                {{ $label }} <span aria-hidden="true" class="text-red-600">*</span>
            </label>
            <input
                id="{{ $fieldId($field) }}"
                name="{{ $model }}"
                type="text"
                value="{{ $valueFor($field) }}"
                autocomplete="{{ $autocompleteSection.$autocomplete }}"
                wire:model.blur="{{ $model }}"
                class="{{ $inputClass }}"
                aria-describedby="{{ $errors->has($model) ? $fieldId($field).'-error' : '' }}"
                @required($required)
                @if ($errors->has($model)) aria-invalid="true" @endif
            >
            @if ($errors->has($model))
                <p id="{{ $fieldId($field) }}-error" class="storefront-field-error">{{ $errors->first($model) }}</p>
            @endif
        </div>
    @endforeach

    @php($model = $fieldName('company'))
    <div class="sm:col-span-2">
        <label for="{{ $fieldId('company') }}" class="{{ $labelClass }}">{{ __('Company') }} <span class="font-normal text-zinc-500">({{ __('optional') }})</span></label>
        <input id="{{ $fieldId('company') }}" name="{{ $model }}" type="text" value="{{ $valueFor('company') }}" autocomplete="{{ $autocompleteSection }}organization" wire:model.blur="{{ $model }}" class="{{ $inputClass }}">
    </div>

    @foreach ([
        ['address1', __('Address'), 'address-line1', true],
        ['address2', __('Apartment, suite, etc.'), 'address-line2', false],
    ] as [$field, $label, $autocomplete, $required])
        @php($model = $fieldName($field))
        <div class="sm:col-span-2">
            <label for="{{ $fieldId($field) }}" class="{{ $labelClass }}">
                {{ $label }}
                @if ($required)
                    <span aria-hidden="true" class="text-red-600">*</span>
                @else
                    <span class="font-normal text-zinc-500">({{ __('optional') }})</span>
                @endif
            </label>
            <input
                id="{{ $fieldId($field) }}"
                name="{{ $model }}"
                type="text"
                value="{{ $valueFor($field) }}"
                autocomplete="{{ $autocompleteSection.$autocomplete }}"
                wire:model.blur="{{ $model }}"
                class="{{ $inputClass }}"
                aria-describedby="{{ $errors->has($model) ? $fieldId($field).'-error' : '' }}"
                @required($required)
                @if ($errors->has($model)) aria-invalid="true" @endif
            >
            @if ($errors->has($model))
                <p id="{{ $fieldId($field) }}-error" class="storefront-field-error">{{ $errors->first($model) }}</p>
            @endif
        </div>
    @endforeach

    @php($model = $fieldName('city'))
    <div>
        <label for="{{ $fieldId('city') }}" class="{{ $labelClass }}">{{ __('City') }} <span aria-hidden="true" class="text-red-600">*</span></label>
        <input id="{{ $fieldId('city') }}" name="{{ $model }}" type="text" value="{{ $valueFor('city') }}" autocomplete="{{ $autocompleteSection }}address-level2" wire:model.blur="{{ $model }}" class="{{ $inputClass }}" aria-describedby="{{ $errors->has($model) ? $fieldId('city').'-error' : '' }}" required @if ($errors->has($model)) aria-invalid="true" @endif>
        @if ($errors->has($model))
            <p id="{{ $fieldId('city') }}-error" class="storefront-field-error">{{ $errors->first($model) }}</p>
        @endif
    </div>

    @php($model = $fieldName('province'))
    <div>
        <label for="{{ $fieldId('province') }}" class="{{ $labelClass }}">{{ __('State / Province') }}</label>
        <input id="{{ $fieldId('province') }}" name="{{ $model }}" type="text" value="{{ $valueFor('province') }}" autocomplete="{{ $autocompleteSection }}address-level1" wire:model.blur="{{ $model }}" class="{{ $inputClass }}" aria-describedby="{{ $errors->has($model) ? $fieldId('province').'-error' : '' }}" @if ($errors->has($model)) aria-invalid="true" @endif>
        @if ($errors->has($model))
            <p id="{{ $fieldId('province') }}-error" class="storefront-field-error">{{ $errors->first($model) }}</p>
        @endif
    </div>

    @php($model = $fieldName('postal_code'))
    <div>
        <label for="{{ $fieldId('postal_code') }}" class="{{ $labelClass }}">{{ __('Postal code') }} <span aria-hidden="true" class="text-red-600">*</span></label>
        <input id="{{ $fieldId('postal_code') }}" name="{{ $model }}" type="text" value="{{ $valueFor('postal_code') }}" autocomplete="{{ $autocompleteSection }}postal-code" wire:model.blur="{{ $model }}" class="{{ $inputClass }}" aria-describedby="{{ $errors->has($model) ? $fieldId('postal_code').'-error' : '' }}" required @if ($errors->has($model)) aria-invalid="true" @endif>
        @if ($errors->has($model))
            <p id="{{ $fieldId('postal_code') }}-error" class="storefront-field-error">{{ $errors->first($model) }}</p>
        @endif
    </div>

    @php($model = $fieldName('country'))
    <div>
        <label for="{{ $fieldId('country') }}" class="{{ $labelClass }}">{{ __('Country') }} <span aria-hidden="true" class="text-red-600">*</span></label>
        <select id="{{ $fieldId('country') }}" name="{{ $model }}" autocomplete="{{ $autocompleteSection }}country" wire:model.live="{{ $model }}" class="{{ $inputClass }}" aria-describedby="{{ $errors->has($model) ? $fieldId('country').'-error' : '' }}" required @if ($errors->has($model)) aria-invalid="true" @endif>
            <option value="">{{ __('Select a country') }}</option>
            @foreach ($countries as $countryCode => $countryName)
                <option value="{{ $countryCode }}" @selected($valueFor('country') === $countryCode)>{{ $countryName }}</option>
            @endforeach
        </select>
        @if ($errors->has($model))
            <p id="{{ $fieldId('country') }}-error" class="storefront-field-error">{{ $errors->first($model) }}</p>
        @endif
    </div>

    @php($model = $fieldName('phone'))
    <div class="sm:col-span-2">
        <label for="{{ $fieldId('phone') }}" class="{{ $labelClass }}">{{ __('Phone') }} <span class="font-normal text-zinc-500">({{ __('optional') }})</span></label>
        <input id="{{ $fieldId('phone') }}" name="{{ $model }}" type="tel" value="{{ $valueFor('phone') }}" autocomplete="{{ $autocompleteSection }}tel" wire:model.blur="{{ $model }}" class="{{ $inputClass }}" aria-describedby="{{ $errors->has($model) ? $fieldId('phone').'-error' : '' }}" @if ($errors->has($model)) aria-invalid="true" @endif>
        @if ($errors->has($model))
            <p id="{{ $fieldId('phone') }}-error" class="storefront-field-error">{{ $errors->first($model) }}</p>
        @endif
    </div>
</fieldset>
