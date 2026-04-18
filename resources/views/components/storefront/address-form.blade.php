@props([
    'prefix' => 'address',
    'values' => [],
])

@php
    $values = is_array($values) ? $values : [];
    $get = fn (string $key, mixed $default = '') => $values[$key] ?? $default;
@endphp

<div {{ $attributes->class(['grid gap-4 sm:grid-cols-2']) }}>
    <flux:field>
        <flux:label>First name</flux:label>
        <flux:input name="{{ $prefix }}[first_name]" value="{{ $get('first_name') }}" required />
    </flux:field>

    <flux:field>
        <flux:label>Last name</flux:label>
        <flux:input name="{{ $prefix }}[last_name]" value="{{ $get('last_name') }}" required />
    </flux:field>

    <flux:field class="sm:col-span-2">
        <flux:label>Company (optional)</flux:label>
        <flux:input name="{{ $prefix }}[company]" value="{{ $get('company') }}" />
    </flux:field>

    <flux:field class="sm:col-span-2">
        <flux:label>Address line 1</flux:label>
        <flux:input name="{{ $prefix }}[address1]" value="{{ $get('address1') }}" required />
    </flux:field>

    <flux:field class="sm:col-span-2">
        <flux:label>Address line 2 (optional)</flux:label>
        <flux:input name="{{ $prefix }}[address2]" value="{{ $get('address2') }}" />
    </flux:field>

    <flux:field>
        <flux:label>City</flux:label>
        <flux:input name="{{ $prefix }}[city]" value="{{ $get('city') }}" required />
    </flux:field>

    <flux:field>
        <flux:label>Postal code</flux:label>
        <flux:input name="{{ $prefix }}[postal_code]" value="{{ $get('postal_code') }}" required />
    </flux:field>

    <flux:field>
        <flux:label>Region / State</flux:label>
        <flux:input name="{{ $prefix }}[region]" value="{{ $get('region') }}" />
    </flux:field>

    <flux:field>
        <flux:label>Country</flux:label>
        <flux:input name="{{ $prefix }}[country_code]" value="{{ $get('country_code') }}" placeholder="DE" maxlength="2" required />
    </flux:field>

    <flux:field class="sm:col-span-2">
        <flux:label>Phone (optional)</flux:label>
        <flux:input type="tel" name="{{ $prefix }}[phone]" value="{{ $get('phone') }}" />
    </flux:field>
</div>
