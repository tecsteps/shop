<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Products</flux:heading>

        <flux:button variant="primary" icon="plus" :href="route('admin.products.create')" wire:navigate>
            Add product
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Search products..."
            class="sm:max-w-sm"
        />

        <div class="flex gap-3">
            <flux:select wire:model.live="statusFilter" class="sm:w-44">
                <option value="all">All statuses</option>
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="archived">Archived</option>
            </flux:select>

            <flux:select wire:model.live="typeFilter" class="sm:w-44">
                <option value="all">All types</option>
                @foreach ($this->productTypes as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Bulk action bar --}}
    @if (count($selectedIds) > 0)
        <div class="mt-4 flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text>{{ count($selectedIds) }} product(s) selected</flux:text>
            <flux:spacer />
            <flux:button variant="ghost" size="sm" wire:click="bulkSetActive">Set Active</flux:button>
            <flux:button variant="ghost" size="sm" wire:click="bulkArchive">Archive</flux:button>
            <flux:button variant="danger" size="sm" wire:click="confirmBulkDelete">Delete</flux:button>
        </div>
    @endif

    {{-- Table --}}
    <div wire:loading.delay.class="opacity-50" class="mt-4">
        @if ($this->products->total() === 0 && $search === '' && $statusFilter === 'all' && $typeFilter === 'all')
            {{-- Genuine empty state --}}
            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 px-6 py-16 text-center dark:border-zinc-700">
                <div class="flex size-14 items-center justify-center rounded-full bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
                    <flux:icon.cube class="size-7" />
                </div>
                <flux:heading size="lg" class="mt-4">Add your first product</flux:heading>
                <flux:text class="mt-1">Start building your catalog by adding products.</flux:text>
                <flux:button variant="primary" icon="plus" :href="route('admin.products.create')" wire:navigate class="mt-6">
                    Add product
                </flux:button>
            </div>
        @else
            <flux:card class="overflow-hidden">
                <flux:table :paginate="$this->products">
                    <flux:table.columns>
                        <flux:table.column class="w-10">
                            <flux:checkbox wire:model.live="selectAll" aria-label="Select all products" />
                        </flux:table.column>
                        <flux:table.column class="w-12"></flux:table.column>
                        <flux:table.column
                            sortable
                            :sorted="$sortField === 'title'"
                            :direction="$sortField === 'title' ? $sortDirection : null"
                            wire:click="sortBy('title')"
                        >
                            Title
                        </flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column
                            sortable
                            :sorted="$sortField === 'inventory'"
                            :direction="$sortField === 'inventory' ? $sortDirection : null"
                            wire:click="sortBy('inventory')"
                        >
                            Inventory
                        </flux:table.column>
                        <flux:table.column>Type</flux:table.column>
                        <flux:table.column>Vendor</flux:table.column>
                        <flux:table.column
                            sortable
                            :sorted="$sortField === 'updated_at'"
                            :direction="$sortField === 'updated_at' ? $sortDirection : null"
                            wire:click="sortBy('updated_at')"
                        >
                            Updated
                        </flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->products as $product)
                            <flux:table.row :key="$product->id">
                                <flux:table.cell>
                                    <flux:checkbox
                                        wire:model.live="selectedIds"
                                        value="{{ $product->id }}"
                                        aria-label="Select {{ $product->title }}"
                                    />
                                </flux:table.cell>

                                <flux:table.cell>
                                    @php
                                        $thumb = $product->media->first();
                                        $inventory = $product->variants->sum(fn ($variant) => $variant->inventoryItem?->quantity_on_hand ?? 0);
                                    @endphp
                                    @if ($thumb)
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($thumb->storage_key) }}"
                                            alt="{{ $thumb->alt_text ?? $product->title }}"
                                            class="size-10 rounded-lg object-cover"
                                        />
                                    @else
                                        <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
                                            <flux:icon.photo class="size-5" />
                                        </div>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell variant="strong">
                                    <a
                                        href="{{ route('admin.products.edit', $product) }}"
                                        wire:navigate
                                        class="hover:underline"
                                    >
                                        {{ $product->title }}
                                    </a>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @php
                                        $colors = ['draft' => 'zinc', 'active' => 'green', 'archived' => 'red'];
                                    @endphp
                                    <flux:badge :color="$colors[$product->status] ?? 'zinc'" :size="'sm'">
                                        {{ ucfirst($product->status) }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>{{ $inventory }}</flux:table.cell>

                                <flux:table.cell>{{ $product->product_type ?: '-' }}</flux:table.cell>

                                <flux:table.cell>{{ $product->vendor ?: '-' }}</flux:table.cell>

                                <flux:table.cell>{{ $product->updated_at?->diffForHumans() }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="8" align="center" class="py-10 text-zinc-400">
                                    No products match your filters.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        @endif
    </div>

    {{-- Bulk delete confirmation --}}
    <flux:modal wire:model="confirmingBulkDelete" variant="default" class="max-w-md">
        <flux:heading size="lg">Delete products?</flux:heading>
        <flux:text class="mt-2">
            This will archive {{ count($selectedIds) }} product(s). Products with orders cannot be permanently deleted.
        </flux:text>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="$set('confirmingBulkDelete', false)">Cancel</flux:button>
            <flux:button variant="danger" wire:click="bulkDelete">Delete</flux:button>
        </div>
    </flux:modal>
</div>
