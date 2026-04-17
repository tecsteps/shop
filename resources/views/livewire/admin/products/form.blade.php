<div>
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">&larr; Products</a>
    </div>

    <flux:heading size="xl" class="mt-4">{{ $isEdit ? 'Edit Product' : 'New Product' }}</flux:heading>

    <form wire:submit="save" class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Main content --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:input wire:model="title" label="Title" required />
                <div class="mt-4">
                    <flux:textarea wire:model="description" label="Description" rows="6" />
                </div>
            </div>

            @if($isEdit && $product)
                {{-- Variants summary --}}
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <flux:heading size="lg">Variants</flux:heading>
                    @if($product->variants->isEmpty())
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No variants.</p>
                    @else
                        <table class="mt-4 w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 dark:text-gray-400">
                                    <th class="pb-2 font-medium">Title</th>
                                    <th class="pb-2 font-medium">SKU</th>
                                    <th class="pb-2 text-right font-medium">Price</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($product->variants as $variant)
                                    <tr wire:key="variant-{{ $variant->id }}">
                                        <td class="py-2 text-gray-900 dark:text-white">{{ $variant->title }}</td>
                                        <td class="py-2 text-gray-500 dark:text-gray-400">{{ $variant->sku ?? '-' }}</td>
                                        <td class="py-2 text-right text-gray-900 dark:text-white">${{ number_format($variant->price / 100, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:select wire:model="status" label="Status">
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="archived">Archived</option>
                </flux:select>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:input wire:model="vendor" label="Vendor" />
                <div class="mt-4">
                    <flux:input wire:model="product_type" label="Product type" />
                </div>
                <div class="mt-4">
                    <flux:input wire:model="tags" label="Tags" placeholder="tag1, tag2, tag3" />
                </div>
            </div>

            <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">{{ $isEdit ? 'Save changes' : 'Create product' }}</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </form>
</div>
