<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl">Products</flux:heading>
            <flux:text>Manage catalog items, status, pricing, and stock.</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('admin.products.create')" wire:navigate>
            Add product
        </flux:button>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-3 border-b border-zinc-200 p-4 dark:border-zinc-800 md:grid-cols-[1fr_14rem_auto]">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products" icon="magnifying-glass" />
            <flux:select wire:model.live="status">
                <option value="all">All statuses</option>
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</option>
                @endforeach
            </flux:select>
            <flux:button wire:click="bulkArchive" :disabled="count($selected) === 0">Archive selected</flux:button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-950">
                    <tr>
                        <th class="w-10 px-5 py-3"></th>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Variants</th>
                        <th class="px-5 py-3">Stock</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($products as $product)
                        @php($stock = $product->variants->sum(fn ($variant) => (int) ($variant->inventoryItem?->availableQuantity() ?? 0)))
                        <tr wire:key="admin-product-{{ $product->id }}">
                            <td class="px-5 py-4">
                                <flux:checkbox wire:model.live="selected" value="{{ $product->id }}" />
                            </td>
                            <td class="px-5 py-4">
                                <a href="{{ route('admin.products.edit', $product) }}" wire:navigate class="font-medium hover:underline">{{ $product->title }}</a>
                                <div class="text-xs text-zinc-500">{{ $product->handle }}</div>
                            </td>
                            <td class="px-5 py-4"><flux:badge>{{ $product->status->value }}</flux:badge></td>
                            <td class="px-5 py-4">{{ $product->variants_count }}</td>
                            <td class="px-5 py-4">{{ $stock }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" :href="route('admin.products.edit', $product)" wire:navigate>Edit</flux:button>
                                    <flux:button size="sm" wire:click="archive({{ $product->id }})">Archive</flux:button>
                                    @if ($product->status->value === 'draft')
                                        <flux:button size="sm" variant="danger" wire:click="delete({{ $product->id }})" wire:confirm="Delete this product?">Delete</flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center text-sm text-zinc-500">No products match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
            {{ $products->links() }}
        </div>
    </div>
</div>
