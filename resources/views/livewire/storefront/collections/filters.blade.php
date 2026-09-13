<div class="flex flex-col gap-6">
    @if ($hasActiveFilters)
        <button type="button" wire:click="clearFilters" class="self-start text-sm text-blue-600 hover:underline dark:text-blue-400">Clear all filters</button>
    @endif

    <details open>
        <summary class="cursor-pointer text-sm font-semibold">Availability</summary>
        <label class="mt-2 flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model.live="inStock" id="{{ $idPrefix }}-in-stock" class="h-4 w-4" />
            In stock
        </label>
    </details>

    <details open>
        <summary class="cursor-pointer text-sm font-semibold">Price Range</summary>
        <div class="mt-2 flex items-center gap-2">
            <flux:input wire:model.live.debounce.500ms="priceMin" type="number" min="0" placeholder="Min" aria-label="Minimum price" />
            <span class="text-sm">-</span>
            <flux:input wire:model.live.debounce.500ms="priceMax" type="number" min="0" placeholder="Max" aria-label="Maximum price" />
        </div>
    </details>

    @if ($availableTypes !== [])
        <details open>
            <summary class="cursor-pointer text-sm font-semibold">Product Type</summary>
            <div class="mt-2 flex flex-col gap-1">
                @foreach ($availableTypes as $type)
                    <label wire:key="{{ $idPrefix }}-type-{{ $loop->index }}" class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model.live="types" value="{{ $type }}" class="h-4 w-4" />
                        {{ $type }}
                    </label>
                @endforeach
            </div>
        </details>
    @endif

    @if ($availableVendors !== [])
        <details open>
            <summary class="cursor-pointer text-sm font-semibold">Vendor</summary>
            <div class="mt-2 flex flex-col gap-1">
                @foreach ($availableVendors as $vendor)
                    <label wire:key="{{ $idPrefix }}-vendor-{{ $loop->index }}" class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model.live="vendors" value="{{ $vendor }}" class="h-4 w-4" />
                        {{ $vendor }}
                    </label>
                @endforeach
            </div>
        </details>
    @endif
</div>
