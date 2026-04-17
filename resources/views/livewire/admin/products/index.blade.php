<div>
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Products</flux:heading>
        <flux:button href="{{ route('admin.products.create') }}" variant="primary" icon="plus">
            Add product
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="mt-6 flex flex-wrap gap-4">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="statusFilter" class="w-40">
            <option value="all">All status</option>
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="archived">Archived</option>
        </flux:select>
    </div>

    {{-- Bulk actions --}}
    @if(count($selectedIds) > 0)
        <div class="mt-4 flex items-center gap-3 rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20">
            <span class="text-sm font-medium">{{ count($selectedIds) }} selected</span>
            <flux:button wire:click="bulkSetActive" size="sm" variant="ghost">Set Active</flux:button>
            <flux:button wire:click="bulkArchive" size="sm" variant="ghost">Archive</flux:button>
        </div>
    @endif

    {{-- Products table --}}
    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="w-10 px-4 py-3">
                        <flux:checkbox wire:model.live="selectAll" />
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer" wire:click="sortBy('title')">
                        Title
                        @if($sortField === 'title')
                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Variants</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Vendor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer" wire:click="sortBy('updated_at')">
                        Updated
                        @if($sortField === 'updated_at')
                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse($products as $product)
                    <tr wire:key="product-{{ $product->id }}">
                        <td class="px-4 py-3">
                            <flux:checkbox wire:model.live="selectedIds" value="{{ $product->id }}" />
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.products.edit', $product) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                {{ $product->title }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" :color="match($product->status->value) { 'active' => 'green', 'draft' => 'yellow', 'archived' => 'zinc', default => 'zinc' }">
                                {{ ucfirst($product->status->value) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $product->variants_count }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $product->vendor ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $product->updated_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($product->status->value === 'draft')
                                <flux:button wire:click="deleteProduct({{ $product->id }})" wire:confirm="Delete this product?" size="sm" variant="ghost" icon="trash" />
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
</div>
