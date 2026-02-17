<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Products</flux:heading>
        <flux:button variant="primary" href="{{ route('admin.products.create') }}" wire:navigate icon="plus">
            Add product
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="statusFilter" class="w-40">
            <flux:select.option value="all">All status</flux:select.option>
            <flux:select.option value="draft">Draft</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="archived">Archived</flux:select.option>
        </flux:select>
    </div>

    {{-- Bulk actions --}}
    @if (count($selectedIds) > 0)
        <div class="mb-4 flex items-center gap-3 rounded-lg bg-zinc-100 px-4 py-2 dark:bg-zinc-700">
            <flux:text class="text-sm font-medium">{{ count($selectedIds) }} products selected</flux:text>
            <flux:button variant="ghost" size="sm" wire:click="bulkSetActive">Set Active</flux:button>
            <flux:button variant="ghost" size="sm" wire:click="bulkArchive">Archive</flux:button>
            <flux:button variant="danger" size="sm" x-on:click="$flux.modal('confirm-bulk-delete').show()">Delete</flux:button>
        </div>
    @endif

    {{-- Products table --}}
    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800" wire:loading.class="opacity-50">
        @if ($this->products->isEmpty() && ! $search && $statusFilter === 'all')
            <div class="flex flex-col items-center justify-center py-16">
                <flux:icon name="cube" class="mb-4 h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                <flux:heading size="lg">Add your first product</flux:heading>
                <flux:text class="mb-4 mt-1">Start building your catalog by adding products.</flux:text>
                <flux:button variant="primary" href="{{ route('admin.products.create') }}" wire:navigate>Add product</flux:button>
            </div>
        @elseif ($this->products->isEmpty())
            <div class="py-12 text-center">
                <flux:text class="text-zinc-400">No products match your filters.</flux:text>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="px-4 py-3">
                            <flux:checkbox wire:model.live="selectAll" wire:change="toggleSelectAll" />
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Image</th>
                        <th class="cursor-pointer px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400" wire:click="sortBy('title')">
                            Title
                            @if ($sortField === 'title')
                                <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" variant="mini" class="inline h-3 w-3" />
                            @endif
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Variants</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Type</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Vendor</th>
                        <th class="cursor-pointer px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400" wire:click="sortBy('updated_at')">
                            Updated
                            @if ($sortField === 'updated_at')
                                <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" variant="mini" class="inline h-3 w-3" />
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->products as $product)
                        <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                            <td class="px-4 py-3">
                                <flux:checkbox wire:model.live="selectedIds" value="{{ $product->id }}" />
                            </td>
                            <td class="px-4 py-3">
                                @if ($product->media->first())
                                    <div class="h-10 w-10 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-700">
                                        <img src="{{ $product->media->first()->url }}" alt="" class="h-full w-full object-cover">
                                    </div>
                                @else
                                    <div class="flex h-10 w-10 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-700">
                                        <flux:icon name="photo" class="h-5 w-5 text-zinc-400" />
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400" wire:navigate>
                                    {{ $product->title }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColor = match($product->status) {
                                        \App\Enums\ProductStatus::Active => 'green',
                                        \App\Enums\ProductStatus::Archived => 'red',
                                        default => 'zinc',
                                    };
                                @endphp
                                <flux:badge :color="$statusColor" size="sm">{{ ucfirst($product->status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $product->variants_count }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $product->product_type ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $product->vendor ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $product->updated_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3">
                {{ $this->products->links() }}
            </div>
        @endif
    </div>

    {{-- Delete confirmation modal --}}
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
