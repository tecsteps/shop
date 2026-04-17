@props(['address' => null, 'prefix' => ''])

@php
    $p = $prefix ? $prefix . '.' : '';
@endphp

<div {{ $attributes->class(['grid grid-cols-1 gap-4 sm:grid-cols-2']) }}>
    <div>
        <label for="{{ $p }}first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">First name</label>
        <input type="text"
               id="{{ $p }}first_name"
               wire:model="{{ $p }}first_name"
               value="{{ $address['first_name'] ?? '' }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
               required>
    </div>
    <div>
        <label for="{{ $p }}last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last name</label>
        <input type="text"
               id="{{ $p }}last_name"
               wire:model="{{ $p }}last_name"
               value="{{ $address['last_name'] ?? '' }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
               required>
    </div>
    <div class="sm:col-span-2">
        <label for="{{ $p }}address1" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
        <input type="text"
               id="{{ $p }}address1"
               wire:model="{{ $p }}address1"
               value="{{ $address['address1'] ?? '' }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
               required>
    </div>
    <div class="sm:col-span-2">
        <label for="{{ $p }}address2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Apartment, suite, etc. (optional)</label>
        <input type="text"
               id="{{ $p }}address2"
               wire:model="{{ $p }}address2"
               value="{{ $address['address2'] ?? '' }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
    </div>
    <div>
        <label for="{{ $p }}city" class="block text-sm font-medium text-gray-700 dark:text-gray-300">City</label>
        <input type="text"
               id="{{ $p }}city"
               wire:model="{{ $p }}city"
               value="{{ $address['city'] ?? '' }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
               required>
    </div>
    <div>
        <label for="{{ $p }}postal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Postal code</label>
        <input type="text"
               id="{{ $p }}postal_code"
               wire:model="{{ $p }}postal_code"
               value="{{ $address['postal_code'] ?? '' }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
               required>
    </div>
    <div>
        <label for="{{ $p }}province" class="block text-sm font-medium text-gray-700 dark:text-gray-300">State / Province</label>
        <input type="text"
               id="{{ $p }}province"
               wire:model="{{ $p }}province"
               value="{{ $address['province'] ?? '' }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
    </div>
    <div>
        <label for="{{ $p }}country_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Country</label>
        <input type="text"
               id="{{ $p }}country_code"
               wire:model="{{ $p }}country_code"
               value="{{ $address['country_code'] ?? '' }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
               required>
    </div>
    <div class="sm:col-span-2">
        <label for="{{ $p }}phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone (optional)</label>
        <input type="tel"
               id="{{ $p }}phone"
               wire:model="{{ $p }}phone"
               value="{{ $address['phone'] ?? '' }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
    </div>
</div>
