<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Products</flux:heading>
        <flux:button variant="primary" href="{{ route('admin.products.create') }}" wire:navigate>
            <flux:icon name="plus" variant="mini" class="mr-1 h-4 w-4" />
            Add product
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap items-center gap-4">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search products..."
                icon="magnifying-glass"
            />
        </div>
        <flux:select wire:model.live="statusFilter" class="w-40">
            <option value="all">All statuses</option>
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="archived">Archived</option>
        </flux:select>
        <flux:select wire:model.live="typeFilter" class="w-40">
            <option value="all">All types</option>
            @foreach ($this->productTypes as $type)
                <option value="{{ $type }}">{{ $type }}</option>
            @endforeach
        </flux:select>
    </div>

    {{-- Bulk Actions --}}
    @if (count($selectedIds) > 0)
        <div class="mb-4 flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-900 dark:bg-blue-950">
            <flux:text class="text-sm font-medium">{{ count($selectedIds) }} products selected</flux:text>
            <flux:button variant="ghost" size="sm" wire:click="bulkSetActive">Set Active</flux:button>
            <flux:button variant="ghost" size="sm" wire:click="bulkArchive">Archive</flux:button>
            <flux:button variant="danger" size="sm" wire:click="confirmBulkDelete">Delete</flux:button>
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <table class="w-full text-left text-sm" wire:loading.class="opacity-50" wire:target="search,statusFilter,typeFilter">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-4 py-3">
                        <flux:checkbox wire:click="toggleSelectAll" :checked="$selectAll" />
                    </th>
                    <th class="px-4 py-3"></th>
                    <th class="cursor-pointer px-4 py-3 font-medium text-gray-500 dark:text-gray-400" wire:click="sortBy('title')">
                        Title
                        @if ($sortField === 'title')
                            <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" variant="mini" class="ml-1 inline h-3 w-3" />
                        @endif
                    </th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Inventory</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Type</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Vendor</th>
                    <th class="cursor-pointer px-4 py-3 font-medium text-gray-500 dark:text-gray-400" wire:click="sortBy('updated_at')">
                        Updated
                        @if ($sortField === 'updated_at')
                            <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" variant="mini" class="ml-1 inline h-3 w-3" />
                        @endif
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr class="border-b border-gray-100 dark:border-gray-800" wire:key="product-{{ $product->id }}">
                        <td class="px-4 py-3">
                            <flux:checkbox wire:model.live="selectedIds" value="{{ $product->id }}" />
                        </td>
                        <td class="px-4 py-3">
                            @if ($product->media->first())
                                <img src="{{ $product->media->first()->url }}" alt="" class="h-10 w-10 rounded object-cover">
                            @else
                                <div class="flex h-10 w-10 items-center justify-center rounded bg-gray-100 dark:bg-gray-800">
                                    <flux:icon name="cube" variant="outline" class="h-5 w-5 text-gray-400" />
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.products.edit', $product) }}" wire:navigate class="font-medium text-gray-900 hover:text-blue-600 dark:text-white dark:hover:text-blue-400">
                                {{ $product->title }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge :color="match($product->status->value) {
                                'active' => 'green',
                                'archived' => 'red',
                                default => 'zinc',
                            }" size="sm">
                                {{ ucfirst($product->status->value) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">{{ $product->variants_count }}</td>
                        <td class="px-4 py-3">{{ $product->product_type ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $product->vendor ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                            {{ $product->updated_at->diffForHumans() }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <flux:icon name="cube" variant="outline" class="h-12 w-12 text-gray-300 dark:text-gray-600" />
                                <flux:heading size="lg">Add your first product</flux:heading>
                                <flux:text class="text-gray-500">Start building your catalog by adding products.</flux:text>
                                <flux:button variant="primary" href="{{ route('admin.products.create') }}" wire:navigate>
                                    Add product
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="confirm-bulk-delete" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Delete products?</flux:heading>
            <flux:text>This will archive {{ count($selectedIds) }} product(s). Products with orders cannot be permanently deleted.</flux:text>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('confirm-bulk-delete').close()">Cancel</flux:button>
                <flux:button variant="danger" wire:click="bulkDelete">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
