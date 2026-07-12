<div class="space-y-7">
    <div class="flex items-center justify-between"><h2 class="font-semibold">Filters</h2>@if ($this->hasActiveFilters)<button wire:click="clearFilters" class="sf-text-link text-sm">Clear all</button>@endif</div>
    <fieldset class="border-t border-slate-200 pt-6 dark:border-slate-800">
        <legend class="font-semibold">Availability</legend>
        <label class="mt-4 flex min-h-11 cursor-pointer items-center gap-3 text-sm"><input type="checkbox" wire:model.live="inStock" class="sf-checkbox"> In stock</label>
    </fieldset>
    <fieldset class="border-t border-slate-200 pt-6 dark:border-slate-800">
        <legend class="font-semibold">Price range</legend>
        <div class="mt-4 grid grid-cols-2 gap-2">
            <label><span class="sr-only">Minimum price</span><input type="number" min="0" step="0.01" wire:model.live.debounce.500ms="minPrice" placeholder="Min" class="sf-input"></label>
            <label><span class="sr-only">Maximum price</span><input type="number" min="0" step="0.01" wire:model.live.debounce.500ms="maxPrice" placeholder="Max" class="sf-input"></label>
        </div>
    </fieldset>
    @if ($this->productTypes !== [])
        <fieldset class="border-t border-slate-200 pt-6 dark:border-slate-800"><legend class="font-semibold">Product type</legend><div class="mt-3 space-y-1">@foreach ($this->productTypes as $type)<label class="flex min-h-11 cursor-pointer items-center gap-3 text-sm"><input type="checkbox" value="{{ $type }}" wire:model.live="types" class="sf-checkbox"> {{ $type }}</label>@endforeach</div></fieldset>
    @endif
    @if ($this->availableVendors !== [])
        <fieldset class="border-t border-slate-200 pt-6 dark:border-slate-800"><legend class="font-semibold">Vendor</legend><div class="mt-3 space-y-1">@foreach ($this->availableVendors as $vendor)<label class="flex min-h-11 cursor-pointer items-center gap-3 text-sm"><input type="checkbox" value="{{ $vendor }}" wire:model.live="vendors" class="sf-checkbox"> {{ $vendor }}</label>@endforeach</div></fieldset>
    @endif
</div>
