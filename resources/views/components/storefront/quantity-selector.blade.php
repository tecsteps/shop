@props(['value' => 1, 'min' => 1, 'max' => 99])

<div {{ $attributes->merge(['class' => 'flex items-center space-x-3']) }}>
    <button type="button"
            class="rounded-lg border border-gray-300 px-3 py-1 text-sm hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700"
            aria-label="Decrease quantity">
        -
    </button>
    <span class="w-12 text-center text-sm">{{ $value }}</span>
    <button type="button"
            class="rounded-lg border border-gray-300 px-3 py-1 text-sm hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700"
            aria-label="Increase quantity">
        +
    </button>
</div>
