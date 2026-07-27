{{-- Filter groups shared between the desktop sidebar and the mobile drawer. --}}
<div class="flex flex-col gap-6">
    @if (count($activeFilters) > 0)
        <div>
            <button type="button" wire:click="clearFilters" class="text-sm font-medium text-blue-600 underline-offset-2 hover:underline focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-blue-400">
                Clear all filters
            </button>
        </div>
    @endif

    {{-- Availability --}}
    <fieldset>
        <legend class="text-sm font-semibold text-gray-900 dark:text-white">Availability</legend>
        <div class="mt-3">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" wire:model.live="inStock"
                       class="size-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800">
                In stock
            </label>
        </div>
    </fieldset>

    {{-- Price range --}}
    <fieldset>
        <legend class="text-sm font-semibold text-gray-900 dark:text-white">Price</legend>
        <div class="mt-3 flex items-center gap-2">
            <label for="filter-min-price" class="sr-only">Minimum price</label>
            <input id="filter-min-price" type="number" min="0" step="0.01" placeholder="Min"
                   wire:model.live.debounce.500ms="minPrice"
                   class="w-full min-w-0 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            <span class="text-gray-400" aria-hidden="true">-</span>
            <label for="filter-max-price" class="sr-only">Maximum price</label>
            <input id="filter-max-price" type="number" min="0" step="0.01" placeholder="Max"
                   wire:model.live.debounce.500ms="maxPrice"
                   class="w-full min-w-0 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        </div>
    </fieldset>

    {{-- Product type --}}
    @if (count($availableTypes) > 0)
        <fieldset>
            <legend class="text-sm font-semibold text-gray-900 dark:text-white">Product type</legend>
            <div class="mt-3 flex flex-col gap-2">
                @foreach ($availableTypes as $type)
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" value="{{ $type }}" wire:model.live="types"
                               class="size-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800">
                        {{ $type }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif

    {{-- Vendor --}}
    @if (count($availableVendors) > 0)
        <fieldset>
            <legend class="text-sm font-semibold text-gray-900 dark:text-white">Vendor</legend>
            <div class="mt-3 flex flex-col gap-2">
                @foreach ($availableVendors as $vendor)
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" value="{{ $vendor }}" wire:model.live="vendors"
                               class="size-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800">
                        {{ $vendor }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif
</div>
