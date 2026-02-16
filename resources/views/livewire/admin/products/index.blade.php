<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products..." icon="magnifying-glass" class="w-64" />
        </div>
        <flux:button variant="primary" href="{{ route('admin.products.create') }}">Add product</flux:button>
    </div>

    {{-- Status tabs --}}
    <div class="mb-4 flex gap-2">
        @foreach(['' => 'All', 'active' => 'Active', 'draft' => 'Draft', 'archived' => 'Archived'] as $value => $label)
            <flux:button
                wire:click="$set('status', '{{ $value }}')"
                :variant="$status === $value ? 'primary' : 'ghost'"
                size="sm"
            >
                {{ $label }}
            </flux:button>
        @endforeach
    </div>

    {{-- Bulk actions --}}
    @if(count($selected) > 0)
        <div class="mb-4 flex items-center gap-3 rounded-lg bg-blue-50 px-4 py-2 dark:bg-blue-900/20">
            <span class="text-sm text-blue-700 dark:text-blue-300">{{ count($selected) }} selected</span>
            <flux:button wire:click="bulkArchive" variant="ghost" size="sm">Archive</flux:button>
            <flux:button wire:click="bulkDelete" variant="danger" size="sm">Delete</flux:button>
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-6 py-3"><input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 dark:border-zinc-600"></th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Vendor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Inventory</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Price</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50">
                            <td class="px-6 py-4"><input type="checkbox" wire:model.live="selected" value="{{ $product->id }}" class="rounded border-gray-300 dark:border-zinc-600"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($product->media->first())
                                        <img src="{{ Storage::url($product->media->first()->url) }}" alt="" class="h-10 w-10 rounded object-cover">
                                    @else
                                        <div class="flex h-10 w-10 items-center justify-center rounded bg-gray-100 dark:bg-zinc-700">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 0 0 1.5-1.5V5.25a1.5 1.5 0 0 0-1.5-1.5H3.75a1.5 1.5 0 0 0-1.5 1.5v14.25c0 .828.672 1.5 1.5 1.5Z"/></svg>
                                        </div>
                                    @endif
                                    <a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-gray-900 hover:underline dark:text-white">{{ $product->title }}</a>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <flux:badge size="sm" :variant="match($product->status->value) { 'active' => 'primary', 'draft' => 'warning', 'archived' => 'outline', default => 'outline' }">
                                    {{ ucfirst($product->status->value) }}
                                </flux:badge>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $product->vendor ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                @php $inv = $product->variants->first()?->inventoryItem; @endphp
                                {{ $inv ? $inv->quantity_on_hand . ' in stock' : '-' }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-white">
                                @php $defaultVariant = $product->variants->first(); @endphp
                                ${{ $defaultVariant ? number_format($defaultVariant->price_amount / 100, 2) : '0.00' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
</div>
