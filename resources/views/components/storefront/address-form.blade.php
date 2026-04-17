@props(['prefix' => ''])

<div {{ $attributes->merge(['class' => 'grid grid-cols-1 gap-4 sm:grid-cols-2']) }}>
    <div>
        <label for="{{ $prefix }}first_name" class="block text-sm font-medium dark:text-gray-200">First Name</label>
        <input type="text" id="{{ $prefix }}first_name" name="{{ $prefix }}first_name"
               class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
    </div>
    <div>
        <label for="{{ $prefix }}last_name" class="block text-sm font-medium dark:text-gray-200">Last Name</label>
        <input type="text" id="{{ $prefix }}last_name" name="{{ $prefix }}last_name"
               class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
    </div>
    <div class="sm:col-span-2">
        <label for="{{ $prefix }}address1" class="block text-sm font-medium dark:text-gray-200">Address</label>
        <input type="text" id="{{ $prefix }}address1" name="{{ $prefix }}address1"
               class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
    </div>
    <div>
        <label for="{{ $prefix }}city" class="block text-sm font-medium dark:text-gray-200">City</label>
        <input type="text" id="{{ $prefix }}city" name="{{ $prefix }}city"
               class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
    </div>
    <div>
        <label for="{{ $prefix }}zip" class="block text-sm font-medium dark:text-gray-200">ZIP / Postal Code</label>
        <input type="text" id="{{ $prefix }}zip" name="{{ $prefix }}zip"
               class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
    </div>
    <div>
        <label for="{{ $prefix }}country" class="block text-sm font-medium dark:text-gray-200">Country</label>
        <input type="text" id="{{ $prefix }}country" name="{{ $prefix }}country"
               class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
    </div>
    <div>
        <label for="{{ $prefix }}phone" class="block text-sm font-medium dark:text-gray-200">Phone</label>
        <input type="tel" id="{{ $prefix }}phone" name="{{ $prefix }}phone"
               class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
    </div>
</div>
