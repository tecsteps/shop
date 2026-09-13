<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Products</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Products</flux:heading>
        @can('create', App\Models\Product::class)
            <flux:button variant="primary" href="{{ route('admin.products.create') }}" wire:navigate>Add product</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search products..." aria-label="Search products" class="max-w-xs flex-1" />
        <flux:select wire:model.live="statusFilter" aria-label="Filter by status" class="w-40">
            <flux:select.option value="all">All statuses</flux:select.option>
            <flux:select.option value="draft">Draft</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="archived">Archived</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="typeFilter" aria-label="Filter by type" class="w-40">
            <flux:select.option value="all">All types</flux:select.option>
            @foreach ($productTypes as $type)
                <flux:select.option value="{{ $type }}">{{ $type }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if (count($this->selectedIds) > 0)
        <div class="mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text>{{ count($this->selectedIds) }} products selected</flux:text>
            <flux:button variant="ghost" size="sm" wire:click="bulkSetActive">Set Active</flux:button>
            <flux:button variant="ghost" size="sm" wire:click="bulkArchive">Archive</flux:button>
            <flux:button variant="danger" size="sm" wire:click="confirmBulkDelete">Delete</flux:button>
        </div>
    @endif

    @if ($products->total() === 0 && $this->search === '' && $this->statusFilter === 'all' && $this->typeFilter === 'all')
        <div class="flex flex-col items-center rounded-lg border border-zinc-200 bg-white px-6 py-16 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.cube class="size-12 text-zinc-400" variant="outline" />
            <flux:heading size="lg" class="mt-4">Add your first product</flux:heading>
            <flux:text variant="subtle" class="mt-1">Start building your catalog by adding products.</flux:text>
            @can('create', App\Models\Product::class)
                <flux:button variant="primary" href="{{ route('admin.products.create') }}" wire:navigate class="mt-4">Add product</flux:button>
            @endcan
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900" wire:loading.class="opacity-50">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="px-4 py-3"><flux:checkbox wire:model.live="selectAll" wire:change="toggleSelectAll" aria-label="Select all" /></th>
                        <th class="px-4 py-3 font-medium">Image</th>
                        <th class="px-4 py-3 font-medium"><button type="button" wire:click="sortBy('title')" class="font-medium">Title</button></th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Inventory</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Vendor</th>
                        <th class="px-4 py-3 font-medium"><button type="button" wire:click="sortBy('updated_at')" class="font-medium">Updated</button></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="product-{{ $product->getKey() }}">
                            <td class="px-4 py-3"><flux:checkbox wire:model.live="selectedIds" value="{{ $product->getKey() }}" aria-label="Select {{ $product->title }}" /></td>
                            <td class="px-4 py-3">
                                @if ($product->productMedia->first()?->storage_key)
                                    <img src="{{ Storage::disk('public')->url($product->productMedia->first()->storage_key) }}" alt="" class="size-10 rounded object-cover" />
                                @else
                                    <div class="flex size-10 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon.photo class="size-5 text-zinc-400" variant="outline" />
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3"><a href="{{ route('admin.products.edit', $product) }}" class="font-medium underline" wire:navigate>{{ $product->title }}</a></td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$product->status->value === 'active' ? 'green' : ($product->status->value === 'archived' ? 'red' : 'zinc')">{{ ucfirst($product->status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">{{ $product->productVariants->sum(fn ($v) => $v->inventoryItem?->quantity_on_hand ?? 0) }}</td>
                            <td class="px-4 py-3">{{ $product->product_type ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $product->vendor ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $product->updated_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center"><flux:text variant="subtle">No products match your filters.</flux:text></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $products->links() }}
        </div>
    @endif

    <flux:modal wire:model="showBulkDeleteModal" class="max-w-md">
        <flux:heading size="lg">Delete products?</flux:heading>
        <flux:text class="mt-2">This will archive {{ count($this->selectedIds) }} product(s). Products with orders cannot be permanently deleted.</flux:text>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showBulkDeleteModal', false)">Cancel</flux:button>
            <flux:button variant="danger" wire:click="bulkDelete">Delete</flux:button>
        </div>
    </flux:modal>
</div>
