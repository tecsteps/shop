{{-- Shared filter groups for desktop sidebar and mobile drawer. Rendered inside the collection/search Livewire component, so $this is available. --}}
<div class="space-y-6">
    @if ($this->activeFilterCount > 0)
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-zinc-900 dark:text-white">Filters</span>
            <button
                type="button"
                wire:click="clearFilters"
                class="text-sm font-medium text-blue-600 transition hover:text-blue-700 hover:underline dark:text-blue-400 dark:hover:text-blue-300"
            >
                Clear all filters
            </button>
        </div>
    @endif

    {{-- Availability --}}
    <fieldset>
        <legend class="text-sm font-semibold text-zinc-900 dark:text-white">Availability</legend>
        <div class="mt-3 space-y-2.5">
            <label class="flex items-center gap-2.5 text-sm text-zinc-700 dark:text-zinc-300">
                <input
                    type="checkbox"
                    wire:model.live="inStockOnly"
                    value="1"
                    class="size-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-900 dark:ring-offset-zinc-950"
                />
                In stock
            </label>
        </div>
    </fieldset>

    {{-- Price range --}}
    <fieldset>
        <legend class="text-sm font-semibold text-zinc-900 dark:text-white">Price</legend>
        <div class="mt-3 grid grid-cols-2 gap-2">
            <div>
                <label for="price-min" class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Min</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-zinc-400 dark:text-zinc-500" aria-hidden="true">{{ app()->bound('current_store') ? app('current_store')->default_currency : 'EUR' }}</span>
                    <input
                        id="price-min"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        wire:model.live.debounce.500ms="priceMin"
                        placeholder="0"
                        class="w-full rounded-lg border border-zinc-300 bg-white py-2 pl-14 pr-3 text-sm text-zinc-900 placeholder-zinc-400 transition focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder-zinc-500 dark:focus:border-white dark:focus:ring-white/20"
                    />
                </div>
            </div>
            <div>
                <label for="price-max" class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Max</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-zinc-400 dark:text-zinc-500" aria-hidden="true">{{ app()->bound('current_store') ? app('current_store')->default_currency : 'EUR' }}</span>
                    <input
                        id="price-max"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        wire:model.live.debounce.500ms="priceMax"
                        placeholder="Any"
                        class="w-full rounded-lg border border-zinc-300 bg-white py-2 pl-14 pr-3 text-sm text-zinc-900 placeholder-zinc-400 transition focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder-zinc-500 dark:focus:border-white dark:focus:ring-white/20"
                    />
                </div>
            </div>
        </div>
    </fieldset>

    {{-- Product type --}}
    @if (count($this->types) > 0)
        <fieldset>
            <legend class="text-sm font-semibold text-zinc-900 dark:text-white">Product type</legend>
            <div class="mt-3 space-y-2.5">
                @foreach ($this->types as $type)
                    <label class="flex items-center gap-2.5 text-sm text-zinc-700 dark:text-zinc-300">
                        <input
                            type="checkbox"
                            wire:model.live="types"
                            value="{{ $type }}"
                            class="size-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-900 dark:ring-offset-zinc-950"
                        />
                        {{ $type }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif

    {{-- Vendor --}}
    @if (count($this->vendors) > 0)
        <fieldset>
            <legend class="text-sm font-semibold text-zinc-900 dark:text-white">Vendor</legend>
            <div class="mt-3 space-y-2.5">
                @foreach ($this->vendors as $vendor)
                    <label class="flex items-center gap-2.5 text-sm text-zinc-700 dark:text-zinc-300">
                        <input
                            type="checkbox"
                            wire:model.live="vendors"
                            value="{{ $vendor }}"
                            class="size-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-900 dark:ring-offset-zinc-950"
                        />
                        {{ $vendor }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif
</div>
