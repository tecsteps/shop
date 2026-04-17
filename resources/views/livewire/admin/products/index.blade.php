<div>
    <x-slot:title>Products</x-slot:title>

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Products</flux:heading>
        <flux:button href="{{ route('admin.products.create') }}" variant="primary" wire:navigate>
            Add product
        </flux:button>
    </div>

    <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products..." icon="magnifying-glass" class="sm:max-w-xs" />
        <flux:select wire:model.live="statusFilter" class="sm:w-40">
            <option value="all">All statuses</option>
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="archived">Archived</option>
        </flux:select>
    </div>

    @if (count($selectedIds) > 0)
        <div class="mt-4 flex items-center gap-4 rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800">
            <flux:text class="text-sm">{{ count($selectedIds) }} product(s) selected</flux:text>
            <flux:button wire:click="bulkArchive" size="sm" variant="ghost">Archive</flux:button>
        </div>
    @endif

    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="w-12 px-6 py-3">
                        <flux:checkbox wire:model.live="selectAll" />
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Variants</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Vendor</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse ($products as $product)
                    <tr>
                        <td class="px-6 py-4">
                            <flux:checkbox wire:model.live="selectedIds" value="{{ $product->id }}" />
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-blue-600 hover:underline" wire:navigate>
                                {{ $product->title }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <flux:badge :color="match($product->status->value) { 'active' => 'green', 'draft' => 'yellow', 'archived' => 'zinc', default => 'zinc' }">
                                {{ ucfirst($product->status->value) }}
                            </flux:badge>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $product->variants_count }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $product->type ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $product->vendor ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            No products found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $products->links() }}
    </div>
</div>
