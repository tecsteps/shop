<div class="space-y-6">
    <x-admin.breadcrumbs :items="[['label' => __('Inventory')]]" />

    <flux:heading size="xl" level="1">{{ __('Inventory') }}</flux:heading>

    <div class="flex flex-wrap items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            :placeholder="__('Search by product or SKU...')"
            class="max-w-xs"
            data-test="inventory-search"
        />

        <flux:select wire:model.live="stockFilter" size="sm" class="max-w-48">
            <flux:select.option value="all">{{ __('Stock: All') }}</flux:select.option>
            <flux:select.option value="in_stock">{{ __('In stock') }}</flux:select.option>
            <flux:select.option value="low_stock">{{ __('Low stock') }}</flux:select.option>
            <flux:select.option value="out_of_stock">{{ __('Out of stock') }}</flux:select.option>
        </flux:select>
    </div>

    <x-admin.card class="!p-0">
        <div class="overflow-x-auto" wire:loading.class="opacity-50">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                        <th class="px-4 py-2.5">{{ __('Product') }}</th>
                        <th class="px-4 py-2.5">{{ __('Variant') }}</th>
                        <th class="px-4 py-2.5">{{ __('SKU') }}</th>
                        <th class="px-4 py-2.5">{{ __('On hand') }}</th>
                        <th class="px-4 py-2.5">{{ __('Reserved') }}</th>
                        <th class="px-4 py-2.5">{{ __('Available') }}</th>
                        <th class="px-4 py-2.5">{{ __('Policy') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->inventoryItems as $item)
                        @php($available = $item->availableQuantity())
                        <tr wire:key="inventory-{{ $item->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.products.edit', $item->variant->product) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-white">
                                    {{ $item->variant->product->title }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $item->variant->optionValues->isEmpty() ? __('Default') : $item->variant->optionValues->pluck('value')->implode(' / ') }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $item->variant->sku ?: '-' }}</td>
                            <td class="px-4 py-3">
                                @can('update', $item->variant->product)
                                    <flux:input
                                        type="number"
                                        min="0"
                                        size="sm"
                                        class="max-w-24"
                                        value="{{ $item->quantity_on_hand }}"
                                        wire:change="updateQuantity({{ $item->id }}, $event.target.value)"
                                        aria-label="{{ __('On hand quantity for :sku', ['sku' => $item->variant->sku ?? $item->variant->product->title]) }}"
                                        data-test="quantity-input-{{ $item->id }}"
                                    />
                                @else
                                    <span class="text-zinc-600 dark:text-zinc-400">{{ number_format($item->quantity_on_hand) }}</span>
                                @endcan
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ number_format($item->quantity_reserved) }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'font-medium',
                                    'text-red-600 dark:text-red-400' => $available <= 0,
                                    'text-yellow-600 dark:text-yellow-400' => $available > 0 && $available <= \App\Livewire\Admin\Inventory\Index::LOW_STOCK_THRESHOLD,
                                    'text-zinc-700 dark:text-zinc-300' => $available > \App\Livewire\Admin\Inventory\Index::LOW_STOCK_THRESHOLD,
                                ])>
                                    {{ number_format($available) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" :color="$item->policy === \App\Enums\InventoryPolicy::Deny ? 'zinc' : 'blue'">
                                    {{ $item->policy->value }}
                                </flux:badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center">
                                <flux:text>{{ __('No inventory items match your filters.') }}</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->inventoryItems->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $this->inventoryItems->links() }}
            </div>
        @endif
    </x-admin.card>
</div>
