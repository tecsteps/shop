<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Products</flux:heading>

        @can('create', \App\Models\Product::class)
            <flux:button variant="primary" icon="plus" :href="route('admin.products.create')" wire:navigate>Add product</flux:button>
        @endcan
    </div>

    @if (! $hasProducts)
        {{-- Empty state (spec 03 §3) --}}
        <div class="flex flex-col items-center rounded-lg border border-zinc-200 bg-white px-6 py-16 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon name="cube" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-4">Add your first product</flux:heading>
            <flux:text class="mt-1">Start building your catalog by adding products.</flux:text>
            @can('create', \App\Models\Product::class)
                <flux:button variant="primary" class="mt-6" :href="route('admin.products.create')" wire:navigate>Add product</flux:button>
            @endcan
        </div>
    @else
        <div class="flex flex-wrap items-center gap-3">
            <flux:input icon="magnifying-glass" wire:model.live.debounce.300ms="search" placeholder="Search products..." class="w-full sm:w-72" aria-label="Search products" />

            <div class="flex overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700" role="tablist" aria-label="Status filter">
                @foreach (['all' => 'All', 'draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                    <button type="button"
                            wire:click="$set('statusFilter', '{{ $value }}')"
                            role="tab"
                            aria-selected="{{ $statusFilter === $value ? 'true' : 'false' }}"
                            class="px-3 py-1.5 text-sm {{ $statusFilter === $value ? 'bg-zinc-900 font-semibold text-white dark:bg-zinc-100 dark:text-zinc-900' : 'bg-white text-zinc-600 hover:bg-zinc-50 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <flux:select wire:model.live="typeFilter" class="w-40" aria-label="Product type filter">
                <flux:select.option value="all">All types</flux:select.option>
                @foreach ($productTypes as $type)
                    <flux:select.option :value="$type">{{ $type }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- Bulk action bar --}}
        @if (count($selectedIds) > 0)
            <div class="flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text>{{ count($selectedIds) }} {{ Str::plural('product', count($selectedIds)) }} selected</flux:text>
                <flux:button variant="ghost" wire:click="bulkSetActive">Set Active</flux:button>
                <flux:button variant="ghost" wire:click="bulkArchive">Archive</flux:button>
                <flux:button variant="danger" wire:click="confirmBulkDelete">Delete</flux:button>
            </div>
        @endif

        <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[720px] text-left text-sm" wire:loading.class="opacity-50">
                <thead>
                    <tr class="border-b border-zinc-200 text-xs tracking-wider text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                        <th class="w-10 px-4 py-3">
                            <flux:checkbox wire:click="toggleSelectAll" aria-label="Select all products" />
                        </th>
                        <th class="w-14 px-2 py-3"><span class="sr-only">Image</span></th>
                        <th class="px-4 py-3 font-medium">
                            <button type="button" wire:click="sortBy('title')" class="inline-flex items-center gap-1 uppercase">
                                Title
                                @if ($sortField === 'title')
                                    <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">
                            <button type="button" wire:click="sortBy('inventory')" class="inline-flex items-center gap-1 uppercase">
                                Inventory
                                @if ($sortField === 'inventory')
                                    <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 font-medium">Variants</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Vendor</th>
                        <th class="px-4 py-3 font-medium">
                            <button type="button" wire:click="sortBy('updated_at')" class="inline-flex items-center gap-1 uppercase">
                                Updated
                                @if ($sortField === 'updated_at')
                                    <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-3" />
                                @endif
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($products as $product)
                        <tr wire:key="product-{{ $product->id }}">
                            <td class="px-4 py-3">
                                <flux:checkbox wire:model.live="selectedIds" value="{{ $product->id }}" aria-label="Select {{ $product->title }}" />
                            </td>
                            <td class="px-2 py-3">
                                @if ($product->media->first() !== null && $product->media->first()->status === \App\Enums\MediaStatus::Ready)
                                    <img src="{{ $product->media->first()->urlFor('thumbnail') }}" alt="" class="size-10 rounded object-cover" loading="lazy">
                                @else
                                    <div class="flex size-10 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon name="photo" class="size-5 text-zinc-400" />
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.products.edit', $product) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-zinc-100">
                                    {{ $product->title }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" :color="match ($product->status) {
                                    \App\Enums\ProductStatus::Active => 'green',
                                    \App\Enums\ProductStatus::Archived => 'red',
                                    default => 'zinc',
                                }">{{ ucfirst($product->status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                {{ $product->variants->sum(fn ($variant) => $variant->inventoryItem?->quantity_on_hand ?? 0) }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $product->variants_count }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $product->product_type ?: '—' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $product->vendor ?: '—' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $product->updated_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No products match your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $products->links() }}
    @endif

    {{-- Delete confirmation modal (spec 03 §3) --}}
    <flux:modal wire:model="confirmingBulkDelete" name="confirm-bulk-delete" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Delete products?</flux:heading>
            <flux:text>This will archive {{ count($selectedIds) }} {{ Str::plural('product', count($selectedIds)) }}. Products with orders cannot be permanently deleted.</flux:text>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('confirmingBulkDelete', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="bulkDelete">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
