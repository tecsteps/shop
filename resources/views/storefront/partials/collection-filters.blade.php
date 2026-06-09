{{-- Filter groups shared by the desktop sidebar and the mobile filter drawer. --}}
<div class="space-y-6">
    @if ($hasActiveFilters)
        <button
            type="button"
            wire:click="clearFilters"
            class="text-sm font-medium text-blue-700 transition hover:text-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-blue-400 dark:hover:text-blue-300"
        >
            {{ __('Clear all filters') }}
        </button>
    @endif

    {{-- Availability --}}
    <fieldset x-data="{ open: true }" class="border-b border-zinc-200 pb-6 dark:border-zinc-800">
        <legend class="w-full">
            <button
                type="button"
                x-on:click="open = ! open"
                x-bind:aria-expanded="open"
                class="flex w-full items-center justify-between py-1 text-sm font-semibold text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-white"
            >
                {{ __('Availability') }}
                <svg class="size-4 text-zinc-400 transition" x-bind:class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
        </legend>
        <div x-show="open" x-cloak class="mt-3">
            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-zinc-700 dark:text-zinc-300">
                <input
                    type="checkbox"
                    wire:model.live="inStock"
                    class="size-4 rounded border-zinc-300 text-blue-700 focus:ring-blue-600 dark:border-zinc-600 dark:bg-zinc-800"
                />
                {{ __('In stock') }}
            </label>
        </div>
    </fieldset>

    {{-- Price range --}}
    <fieldset x-data="{ open: true }" class="border-b border-zinc-200 pb-6 dark:border-zinc-800">
        <legend class="w-full">
            <button
                type="button"
                x-on:click="open = ! open"
                x-bind:aria-expanded="open"
                class="flex w-full items-center justify-between py-1 text-sm font-semibold text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-white"
            >
                {{ __('Price') }}
                <svg class="size-4 text-zinc-400 transition" x-bind:class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
        </legend>
        <div x-show="open" x-cloak class="mt-3 flex items-center gap-2">
            <label class="relative block w-full">
                <span class="sr-only">{{ __('Minimum price') }}</span>
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-xs text-zinc-400" aria-hidden="true">&euro;</span>
                <input
                    type="number"
                    min="0"
                    placeholder="{{ __('Min') }}"
                    wire:model.live.debounce.500ms="priceMin"
                    class="block w-full rounded-lg border border-zinc-300 bg-white py-2 pr-2 pl-7 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                />
            </label>
            <span class="text-zinc-400" aria-hidden="true">&ndash;</span>
            <label class="relative block w-full">
                <span class="sr-only">{{ __('Maximum price') }}</span>
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-xs text-zinc-400" aria-hidden="true">&euro;</span>
                <input
                    type="number"
                    min="0"
                    placeholder="{{ __('Max') }}"
                    wire:model.live.debounce.500ms="priceMax"
                    class="block w-full rounded-lg border border-zinc-300 bg-white py-2 pr-2 pl-7 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                />
            </label>
        </div>
    </fieldset>

    {{-- Product type --}}
    @if ($availableProductTypes !== [])
        <fieldset x-data="{ open: true }" class="border-b border-zinc-200 pb-6 dark:border-zinc-800">
            <legend class="w-full">
                <button
                    type="button"
                    x-on:click="open = ! open"
                    x-bind:aria-expanded="open"
                    class="flex w-full items-center justify-between py-1 text-sm font-semibold text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-white"
                >
                    {{ __('Product type') }}
                    <svg class="size-4 text-zinc-400 transition" x-bind:class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
            </legend>
            <div x-show="open" x-cloak class="mt-3 space-y-2.5">
                @foreach ($availableProductTypes as $productType)
                    <label class="flex cursor-pointer items-center gap-2.5 text-sm text-zinc-700 dark:text-zinc-300">
                        <input
                            type="checkbox"
                            value="{{ $productType }}"
                            wire:model.live="productTypes"
                            class="size-4 rounded border-zinc-300 text-blue-700 focus:ring-blue-600 dark:border-zinc-600 dark:bg-zinc-800"
                        />
                        {{ $productType }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif

    {{-- Vendor --}}
    @if ($availableVendors !== [])
        <fieldset x-data="{ open: true }" class="pb-2">
            <legend class="w-full">
                <button
                    type="button"
                    x-on:click="open = ! open"
                    x-bind:aria-expanded="open"
                    class="flex w-full items-center justify-between py-1 text-sm font-semibold text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-white"
                >
                    {{ __('Vendor') }}
                    <svg class="size-4 text-zinc-400 transition" x-bind:class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
            </legend>
            <div x-show="open" x-cloak class="mt-3 space-y-2.5">
                @foreach ($availableVendors as $vendor)
                    <label class="flex cursor-pointer items-center gap-2.5 text-sm text-zinc-700 dark:text-zinc-300">
                        <input
                            type="checkbox"
                            value="{{ $vendor }}"
                            wire:model.live="vendors"
                            class="size-4 rounded border-zinc-300 text-blue-700 focus:ring-blue-600 dark:border-zinc-600 dark:bg-zinc-800"
                        />
                        {{ $vendor }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif
</div>
