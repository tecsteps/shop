<div>
    {{-- Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl">Edit product</flux:heading>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                Update the details for <strong>{{ $product->title }}</strong>.
            </p>
        </div>
        <flux:button variant="danger" wire:click="confirmDelete" size="sm">
            Delete product
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            {{-- Main content --}}
            <div class="space-y-8 lg:col-span-2">
                {{-- Basic details --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="lg" class="mb-4">Details</flux:heading>

                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>Title</flux:label>
                            <flux:input wire:model="title" />
                            <flux:error name="title" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Handle</flux:label>
                            <flux:input wire:model="handle" />
                            <flux:error name="handle" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Description</flux:label>
                            <flux:textarea wire:model="descriptionHtml" rows="5" />
                            <flux:error name="descriptionHtml" />
                        </flux:field>
                    </div>
                </div>

                {{-- Pricing --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="lg" class="mb-4">Pricing</flux:heading>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Price (EUR)</flux:label>
                            <flux:input type="number" wire:model="price" step="0.01" min="0" placeholder="0.00" />
                            <flux:error name="price" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Compare at price (EUR)</flux:label>
                            <flux:input type="number" wire:model="compareAtPrice" step="0.01" min="0" placeholder="0.00" />
                            <flux:error name="compareAtPrice" />
                        </flux:field>
                    </div>
                </div>

                {{-- Inventory --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="lg" class="mb-4">Inventory</flux:heading>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:field>
                                <flux:label>SKU</flux:label>
                                <flux:input wire:model="sku" />
                                <flux:error name="sku" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Barcode</flux:label>
                                <flux:input wire:model="barcode" />
                                <flux:error name="barcode" />
                            </flux:field>
                        </div>

                        <flux:checkbox wire:model.live="trackQuantity" label="Track quantity" />

                        @if ($trackQuantity)
                            <flux:field>
                                <flux:label>Quantity</flux:label>
                                <flux:input type="number" wire:model="quantity" min="0" />
                                <flux:error name="quantity" />
                            </flux:field>
                        @endif
                    </div>
                </div>

                {{-- Variants --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="lg" class="mb-4">Variants</flux:heading>

                    @if ($variants->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">SKU</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Price</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Inventory</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Default</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach ($variants as $variant)
                                        <tr wire:key="variant-{{ $variant->id }}">
                                            <td class="whitespace-nowrap px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300">
                                                {{ $variant->sku ?? '--' }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300">
                                                {{ \App\Support\MoneyFormatter::format($variant->price_amount) }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300">
                                                {{ $variant->inventoryItem?->quantity_on_hand ?? 0 }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-2">
                                                @php
                                                    $variantBadgeColor = match($variant->status) {
                                                        \App\Enums\VariantStatus::Active => 'green',
                                                        \App\Enums\VariantStatus::Archived => 'zinc',
                                                        default => 'zinc',
                                                    };
                                                @endphp
                                                <flux:badge size="sm" color="{{ $variantBadgeColor }}">
                                                    {{ ucfirst($variant->status->value) }}
                                                </flux:badge>
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300">
                                                @if ($variant->is_default)
                                                    <flux:badge size="sm" color="blue">Default</flux:badge>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No variants yet.</p>
                    @endif
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-8">
                {{-- Status --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="lg" class="mb-4">Status</flux:heading>

                    <flux:field>
                        <flux:select wire:model="status">
                            @foreach ($statuses as $statusOption)
                                <flux:select.option value="{{ $statusOption->value }}">
                                    {{ ucfirst($statusOption->value) }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="status" />
                    </flux:field>
                </div>

                {{-- Organization --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="lg" class="mb-4">Organization</flux:heading>

                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>Product type</flux:label>
                            <flux:input wire:model="productType" />
                            <flux:error name="productType" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Vendor</flux:label>
                            <flux:input wire:model="vendor" />
                            <flux:error name="vendor" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Tags</flux:label>
                            <flux:input wire:model="tags" placeholder="tag1, tag2, tag3" />
                            <flux:error name="tags" />
                        </flux:field>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <flux:button variant="ghost" href="{{ route('admin.products.index') }}" wire:navigate>
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary">
                Update product
            </flux:button>
        </div>
    </form>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete product</flux:heading>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Are you sure you want to delete <strong>{{ $product->title }}</strong>?
                    This action cannot be undone. Only draft products can be deleted.
                </p>
            </div>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="deleteProduct">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
