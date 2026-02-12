<div>
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Products</flux:heading>
        <flux:button variant="primary" href="{{ route('admin.products.create') }}" wire:navigate>
            Create product
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search products..."
                icon="magnifying-glass"
            />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="status">
                <flux:select.option value="">All statuses</flux:select.option>
                @foreach ($statuses as $statusOption)
                    <flux:select.option value="{{ $statusOption->value }}">
                        {{ ucfirst($statusOption->value) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Products Table --}}
    <div class="mt-6 overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Image</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Title</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Price</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Inventory</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($products as $product)
                    <tr wire:key="product-{{ $product->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="whitespace-nowrap px-4 py-3">
                            @php
                                $firstMedia = $product->media->sortBy('position')->first();
                            @endphp
                            @if ($firstMedia && $firstMedia->storage_key)
                                <img
                                    src="{{ Storage::url($firstMedia->storage_key) }}"
                                    alt="{{ $firstMedia->alt_text ?? $product->title }}"
                                    class="size-10 rounded-md object-cover"
                                />
                            @else
                                <div class="flex size-10 items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon name="photo" class="size-5 text-zinc-400" />
                                </div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <a
                                href="{{ route('admin.products.edit', $product) }}"
                                wire:navigate
                                class="font-medium text-zinc-900 hover:text-zinc-600 dark:text-zinc-100 dark:hover:text-zinc-300"
                            >
                                {{ $product->title }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @php
                                $badgeColor = match($product->status) {
                                    \App\Enums\ProductStatus::Active => 'green',
                                    \App\Enums\ProductStatus::Draft => 'yellow',
                                    \App\Enums\ProductStatus::Archived => 'zinc',
                                };
                            @endphp
                            <flux:badge size="sm" color="{{ $badgeColor }}">
                                {{ ucfirst($product->status->value) }}
                            </flux:badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                            @php
                                $defaultVariant = $product->variants->firstWhere('is_default', true)
                                    ?? $product->variants->first();
                            @endphp
                            @if ($defaultVariant)
                                {{ \App\Support\MoneyFormatter::format($defaultVariant->price_amount) }}
                            @else
                                --
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                            @php
                                $totalInventory = $product->variants->sum(fn ($v) => $v->inventoryItem?->quantity_on_hand ?? 0);
                            @endphp
                            {{ $totalInventory }} in stock
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" href="{{ route('admin.products.edit', $product) }}" wire:navigate>
                                        Edit
                                    </flux:menu.item>
                                    <flux:menu.item
                                        icon="trash"
                                        variant="danger"
                                        wire:click="confirmDelete({{ $product->id }}, '{{ addslashes($product->title) }}')"
                                    >
                                        Delete
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            No products found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($products->hasPages())
        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete product</flux:heading>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Are you sure you want to delete <strong>{{ $deletingProductTitle }}</strong>?
                    This action cannot be undone. Only draft products can be deleted.
                </p>
            </div>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">Cancel</flux:button>
                <flux:button variant="danger" wire:click="deleteProduct">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
