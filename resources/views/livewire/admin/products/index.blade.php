<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Products</flux:heading>
        <flux:button variant="primary" :href="route('admin.products.create')" wire:navigate icon="plus">
            Add product
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search products..."
                clearable
            />
        </div>
        <flux:select wire:model.live="statusFilter" class="w-auto sm:w-40">
            <flux:select.option value="all">All status</flux:select.option>
            <flux:select.option value="draft">Draft</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="archived">Archived</flux:select.option>
        </flux:select>
    </div>

    {{-- Bulk actions --}}
    @if (count($selectedIds) > 0)
        <div class="flex items-center gap-3 mb-4 p-3 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
            <flux:text class="text-sm font-medium">{{ count($selectedIds) }} product(s) selected</flux:text>
            <flux:button variant="ghost" size="sm" wire:click="bulkSetActive">Set Active</flux:button>
            <flux:button variant="ghost" size="sm" wire:click="bulkArchive">Archive</flux:button>
            <flux:button variant="danger" size="sm" wire:click="confirmBulkDelete">Delete</flux:button>
        </div>
    @endif

    {{-- Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
        @if ($this->products->count() > 0)
            <div class="overflow-x-auto" wire:loading.class="opacity-50">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="px-4 py-3 w-10">
                                <flux:checkbox wire:model.live="selectAll" wire:click="toggleSelectAll" />
                            </th>
                            <th class="px-4 py-3 w-12"></th>
                            <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">
                                <button wire:click="sortBy('title')" class="flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white">
                                    Title
                                    @if ($sortField === 'title')
                                        <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-3" />
                                    @endif
                                </button>
                            </th>
                            <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Inventory</th>
                            <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Type</th>
                            <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Vendor</th>
                            <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">
                                <button wire:click="sortBy('updated_at')" class="flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white">
                                    Updated
                                    @if ($sortField === 'updated_at')
                                        <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-3" />
                                    @endif
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($this->products as $product)
                            <tr wire:key="product-{{ $product->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-3">
                                    <flux:checkbox wire:model.live="selectedIds" value="{{ $product->id }}" />
                                </td>
                                <td class="px-4 py-3">
                                    @if ($product->media->first())
                                        <img
                                            src="{{ $product->media->first()->url }}"
                                            alt="{{ $product->media->first()->alt_text ?? $product->title }}"
                                            class="size-10 rounded object-cover"
                                        >
                                    @else
                                        <div class="size-10 rounded bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                                            <flux:icon name="photo" class="size-5 text-zinc-400" />
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <a
                                        href="{{ route('admin.products.edit', $product) }}"
                                        wire:navigate
                                        class="font-medium text-zinc-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400"
                                    >
                                        {{ $product->title }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <flux:badge size="sm" :color="match($product->status) {
                                        \App\Enums\ProductStatus::Active => 'green',
                                        \App\Enums\ProductStatus::Draft => 'zinc',
                                        \App\Enums\ProductStatus::Archived => 'red',
                                    }">
                                        {{ ucfirst($product->status->value) }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $product->variants->sum(fn ($v) => $v->inventoryItem?->quantity_on_hand ?? 0) }}
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $product->product_type ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $product->vendor ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">
                                    {{ $product->updated_at->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->products->links() }}
            </div>
        @elseif ($search || $statusFilter !== 'all')
            <div class="p-12 text-center">
                <flux:icon name="magnifying-glass" class="size-12 mx-auto text-zinc-300 dark:text-zinc-600" />
                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">No products match your filters.</flux:text>
            </div>
        @else
            <div class="p-12 text-center">
                <flux:icon name="cube" class="size-12 mx-auto text-zinc-300 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">Add your first product</flux:heading>
                <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Start building your catalog by adding products.</flux:text>
                <div class="mt-6">
                    <flux:button variant="primary" :href="route('admin.products.create')" wire:navigate>
                        Add product
                    </flux:button>
                </div>
            </div>
        @endif
    </div>

    {{-- Delete confirmation modal --}}
    <flux:modal name="confirm-bulk-delete" :show="$showDeleteModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Delete products?</flux:heading>
            <flux:text>This will archive {{ count($selectedIds) }} product(s). Products with orders cannot be permanently deleted.</flux:text>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" @click="$wire.showDeleteModal = false">Cancel</flux:button>
                <flux:button variant="danger" wire:click="bulkDelete">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
