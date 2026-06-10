<div class="space-y-6">
    <x-admin.breadcrumbs :items="[['label' => __('Products')]]" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" level="1">{{ __('Products') }}</flux:heading>

        @can('create', \App\Models\Product::class)
            <flux:button variant="primary" icon="plus" :href="route('admin.products.create')" wire:navigate data-test="add-product-button">
                {{ __('Add product') }}
            </flux:button>
        @endcan
    </div>

    @if (! $this->hasAnyProducts)
        {{-- Empty state: no products at all --}}
        <x-admin.card class="flex flex-col items-center gap-3 py-16 text-center">
            <flux:icon name="cube" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg">{{ __('Add your first product') }}</flux:heading>
            <flux:text>{{ __('Start building your catalog by adding products.') }}</flux:text>
            @can('create', \App\Models\Product::class)
                <flux:button variant="primary" :href="route('admin.products.create')" wire:navigate class="mt-2">
                    {{ __('Add product') }}
                </flux:button>
            @endcan
        </x-admin.card>
    @else
        {{-- Status filter tabs --}}
        <div class="flex gap-1 overflow-x-auto border-b border-zinc-200 dark:border-zinc-700" role="tablist">
            @foreach (['all' => __('All'), 'draft' => __('Draft'), 'active' => __('Active'), 'archived' => __('Archived')] as $value => $label)
                <button
                    type="button"
                    wire:click="setStatusFilter('{{ $value }}')"
                    role="tab"
                    aria-selected="{{ $statusFilter === $value ? 'true' : 'false' }}"
                    class="-mb-px cursor-pointer border-b-2 px-4 py-2 text-sm whitespace-nowrap transition {{ $statusFilter === $value
                        ? 'border-blue-600 font-semibold text-zinc-900 dark:border-blue-400 dark:text-white'
                        : 'border-transparent font-medium text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                :placeholder="__('Search products...')"
                class="max-w-xs"
                data-test="product-search"
            />

            <flux:select wire:model.live="typeFilter" size="sm" class="max-w-44">
                <flux:select.option value="all">{{ __('Type: All') }}</flux:select.option>
                @foreach ($this->productTypes as $type)
                    <flux:select.option value="{{ $type }}">{{ $type }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- Bulk action bar --}}
        @if (count($selectedIds) > 0)
            <div class="flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 bg-white px-4 py-2 dark:border-zinc-700 dark:bg-zinc-900" data-test="bulk-action-bar">
                <flux:text>{{ trans_choice(':count product selected|:count products selected', count($selectedIds), ['count' => count($selectedIds)]) }}</flux:text>
                <flux:spacer />
                @can('create', \App\Models\Product::class)
                    <flux:button variant="ghost" size="sm" wire:click="bulkSetActive" data-test="bulk-set-active">{{ __('Set Active') }}</flux:button>
                @endcan
                <flux:button variant="ghost" size="sm" wire:click="bulkArchive" data-test="bulk-archive">{{ __('Archive') }}</flux:button>
                <flux:modal.trigger name="confirm-bulk-delete">
                    <flux:button variant="danger" size="sm" data-test="bulk-delete">{{ __('Delete') }}</flux:button>
                </flux:modal.trigger>
            </div>
        @endif

        {{-- Products table --}}
        <x-admin.card class="!p-0">
            <div class="overflow-x-auto" wire:loading.class="opacity-50">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                            <th class="w-10 px-4 py-2.5">
                                <flux:checkbox wire:model.live="selectAll" aria-label="{{ __('Select all') }}" />
                            </th>
                            <th class="w-14 px-2 py-2.5">{{ __('Image') }}</th>
                            <th class="px-4 py-2.5">
                                <button type="button" wire:click="sortBy('title')" class="flex cursor-pointer items-center gap-1 uppercase">
                                    {{ __('Title') }}
                                    @if ($sortField === 'title')
                                        <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" variant="micro" />
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-2.5">{{ __('Status') }}</th>
                            <th class="px-4 py-2.5">
                                <button type="button" wire:click="sortBy('inventory_quantity')" class="flex cursor-pointer items-center gap-1 uppercase">
                                    {{ __('Inventory') }}
                                    @if ($sortField === 'inventory_quantity')
                                        <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" variant="micro" />
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-2.5">{{ __('Type') }}</th>
                            <th class="px-4 py-2.5">{{ __('Vendor') }}</th>
                            <th class="px-4 py-2.5">
                                <button type="button" wire:click="sortBy('updated_at')" class="flex cursor-pointer items-center gap-1 uppercase">
                                    {{ __('Updated') }}
                                    @if ($sortField === 'updated_at')
                                        <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" variant="micro" />
                                    @endif
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($this->products as $product)
                            <tr wire:key="product-{{ $product->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3">
                                    <flux:checkbox wire:model.live="selectedIds" value="{{ $product->id }}" aria-label="{{ __('Select :title', ['title' => $product->title]) }}" />
                                </td>
                                <td class="px-2 py-3">
                                    @if ($product->media->isNotEmpty())
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->media->first()->storage_key) }}"
                                            alt="{{ $product->media->first()->alt_text ?? $product->title }}"
                                            class="size-10 rounded-md object-cover"
                                        />
                                    @else
                                        <div class="flex size-10 items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-800">
                                            <flux:icon name="photo" variant="micro" class="text-zinc-400" />
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.products.edit', $product) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-white">
                                        {{ $product->title }}
                                    </a>
                                </td>
                                <td class="px-4 py-3"><x-admin.status-badge :status="$product->status" /></td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ number_format((int) $product->inventory_quantity) }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $product->product_type ?: '-' }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $product->vendor ?: '-' }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $product->updated_at?->diffForHumans(short: true) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center">
                                    <flux:text>{{ __('No products match your filters.') }}</flux:text>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($this->products->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    {{ $this->products->links() }}
                </div>
            @endif
        </x-admin.card>

        {{-- Bulk delete confirmation --}}
        <flux:modal name="confirm-bulk-delete" class="md:max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Delete products?') }}</flux:heading>
                <flux:text>
                    {{ __('This will archive :count product(s). Products with orders cannot be permanently deleted.', ['count' => count($selectedIds)]) }}
                </flux:text>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="bulkDelete" data-test="confirm-bulk-delete-button">
                        {{ __('Delete') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
