<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl" level="1">Products</flux:heading>
        <flux:button variant="primary" href="{{ route('admin.products.create') }}" wire:navigate icon="plus">
            Add product
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products..." icon="magnifying-glass" class="flex-1" />

        <flux:select wire:model.live="statusFilter" class="w-40">
            <flux:select.option value="all">All statuses</flux:select.option>
            <flux:select.option value="draft">Draft</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="archived">Archived</flux:select.option>
        </flux:select>

        @if(count($this->productTypes) > 0)
            <flux:select wire:model.live="typeFilter" class="w-40">
                <flux:select.option value="">All types</flux:select.option>
                @foreach($this->productTypes as $type)
                    <flux:select.option :value="$type">{{ $type }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif
    </div>

    {{-- Bulk Actions --}}
    @if(count($selectedIds) > 0)
        <div class="mb-4 flex items-center gap-4 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm font-medium">{{ count($selectedIds) }} products selected</flux:text>
            <flux:button size="sm" variant="ghost" wire:click="bulkSetActive">Set Active</flux:button>
            <flux:button size="sm" variant="ghost" wire:click="bulkArchive">Archive</flux:button>
            <flux:modal.trigger name="confirm-bulk-delete">
                <flux:button size="sm" variant="danger">Delete</flux:button>
            </flux:modal.trigger>
        </div>
    @endif

    {{-- Product Table --}}
    @if($this->products->total() > 0 || $search !== '' || $statusFilter !== 'all' || $typeFilter !== '')
        <flux:table :paginate="$this->products">
            <flux:table.columns>
                <flux:table.column>
                    <flux:checkbox wire:model.live="selectAll" wire:click="toggleSelectAll" />
                </flux:table.column>
                <flux:table.column>Image</flux:table.column>
                <flux:table.column sortable :sorted="$sortField === 'title'" :direction="$sortDirection" wire:click="sortBy('title')">Title</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Variants</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Vendor</flux:table.column>
                <flux:table.column sortable :sorted="$sortField === 'updated_at'" :direction="$sortDirection" wire:click="sortBy('updated_at')">Updated</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($this->products as $product)
                    <flux:table.row :key="$product->id">
                        <flux:table.cell>
                            <flux:checkbox wire:model.live="selectedIds" :value="$product->id" />
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($product->media->first())
                                <img src="{{ Storage::url($product->media->first()->storage_key) }}" alt="" class="h-10 w-10 rounded object-cover" />
                            @else
                                <div class="flex h-10 w-10 items-center rounded bg-zinc-100 dark:bg-zinc-700">
                                    <flux:icon name="photo" class="mx-auto h-5 w-5 text-zinc-400" />
                                </div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell variant="strong">
                            <a href="{{ route('admin.products.edit', $product) }}" class="hover:underline" wire:navigate>
                                {{ $product->title }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="match($product->status->value) { 'active' => 'green', 'draft' => 'zinc', 'archived' => 'red' }">
                                {{ ucfirst($product->status->value) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $product->variants_count }}</flux:table.cell>
                        <flux:table.cell>{{ $product->product_type ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ $product->vendor ?? '-' }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $product->updated_at->diffForHumans() }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center">
                            <flux:text class="text-zinc-500">No products match your filters</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    @else
        {{-- Empty State --}}
        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 bg-white p-12 dark:border-zinc-600 dark:bg-zinc-900">
            <flux:icon name="cube" class="mb-4 h-12 w-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg">Add your first product</flux:heading>
            <flux:text class="mt-2 text-zinc-500">Start building your catalog by adding products.</flux:text>
            <flux:button variant="primary" href="{{ route('admin.products.create') }}" wire:navigate class="mt-4">
                Add product
            </flux:button>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="confirm-bulk-delete" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete products?</flux:heading>
                <flux:text class="mt-2">This will archive {{ count($selectedIds) }} product(s). Products with orders cannot be permanently deleted.</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="bulkDelete">Confirm</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
