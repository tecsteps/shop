<div>
    {{-- Header --}}
    <div class="mb-8">
        <flux:heading size="xl">Create product</flux:heading>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            Add a new product to your store.
        </p>
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
                            <flux:input wire:model.live.debounce.300ms="title" placeholder="Short sleeve t-shirt" />
                            <flux:error name="title" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Handle</flux:label>
                            <flux:input wire:model.blur="handle" placeholder="short-sleeve-t-shirt" />
                            <flux:error name="handle" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Description</flux:label>
                            <flux:textarea wire:model="descriptionHtml" rows="5" placeholder="Product description..." />
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
                                <flux:input wire:model="sku" placeholder="SKU-0001" />
                                <flux:error name="sku" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Barcode</flux:label>
                                <flux:input wire:model="barcode" placeholder="1234567890123" />
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
                            <flux:input wire:model="productType" placeholder="e.g. Shirts" />
                            <flux:error name="productType" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Vendor</flux:label>
                            <flux:input wire:model="vendor" placeholder="e.g. Nike" />
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
                Save product
            </flux:button>
        </div>
    </form>
</div>
