<section class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Products</flux:heading>
            <flux:text class="mt-1">Manage catalog items, variants, status, and inventory.</flux:text>
        </div>

        <flux:button :href="route('admin.products.create')" wire:navigate variant="primary" icon="plus">
            Add product
        </flux:button>
    </div>

    <div class="grid gap-3 lg:grid-cols-[1fr_180px_220px]">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search products..." aria-label="Search products" />

        <flux:select wire:model.live="statusFilter" aria-label="Status filter">
            <flux:select.option value="all">All statuses</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="draft">Draft</flux:select.option>
            <flux:select.option value="archived">Archived</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="typeFilter" aria-label="Product type filter">
            <flux:select.option value="all">All types</flux:select.option>
            @foreach ($productTypes as $type)
                <flux:select.option value="{{ $type }}">{{ $type }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if (count($selectedIds) > 0)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text>{{ count($selectedIds) }} products selected</flux:text>

            <div class="flex flex-wrap gap-2">
                <flux:button wire:click="bulkSetActive" variant="ghost">Set active</flux:button>
                <flux:button wire:click="bulkArchive" variant="ghost">Archive</flux:button>
                <flux:button wire:click="bulkDelete" variant="danger">Delete</flux:button>
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" wire:click="toggleSelectAll" @checked($selectAll) aria-label="Select all visible products" class="rounded border-zinc-300 dark:border-zinc-600">
                        </th>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Inventory</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3">
                            <button wire:click="sortBy('updated_at')" class="font-semibold">Updated</button>
                        </th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50" class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($products as $product)
                        @php
                            $inventory = $product->variants->sum(fn ($variant) => $variant->inventoryItem?->quantity_on_hand ?? 0);
                            $statusColor = match ($product->status->value) {
                                'active' => 'green',
                                'archived' => 'red',
                                default => 'zinc',
                            };
                        @endphp

                        <tr wire:key="admin-product-{{ $product->getKey() }}">
                            <td class="px-4 py-3">
                                <input type="checkbox" wire:model.live="selectedIds" value="{{ $product->getKey() }}" aria-label="Select {{ $product->title }}" class="rounded border-zinc-300 dark:border-zinc-600">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-md bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
                                        <flux:icon name="cube" class="size-5" />
                                    </div>

                                    <div>
                                        <a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-zinc-950 hover:underline dark:text-white" wire:navigate>
                                            {{ $product->title }}
                                        </a>
                                        <div class="text-xs text-zinc-500">{{ $product->variants_count }} variants</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$statusColor">{{ Str::title($product->status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">{{ $inventory }}</td>
                            <td class="px-4 py-3">{{ $product->product_type ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $product->vendor ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-500">{{ $product->updated_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                                    <div class="flex size-12 items-center justify-center rounded-lg bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                        <flux:icon name="cube" class="size-6" />
                                    </div>
                                    <flux:heading size="lg">No products found</flux:heading>
                                    <flux:text>Adjust your filters or add a product.</flux:text>
                                    <flux:button :href="route('admin.products.create')" wire:navigate variant="primary">Add product</flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $products->links() }}
</section>
