<div>
    <x-slot:title>{{ $product ? 'Edit Product' : 'Create Product' }}</x-slot:title>

    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $product ? 'Edit Product' : 'Create Product' }}</flux:heading>
        <flux:button href="{{ route('admin.products.index') }}" variant="ghost" wire:navigate>
            Back to Products
        </flux:button>
    </div>

    <form wire:submit="save" class="mt-6 space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <flux:heading size="lg">Details</flux:heading>

            <div class="mt-4 space-y-4">
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="title" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="description" rows="4" />
                    <flux:error name="description" />
                </flux:field>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="archived">Archived</option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Vendor</flux:label>
                        <flux:input wire:model="vendor" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Type</flux:label>
                        <flux:input wire:model="type" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Tags</flux:label>
                    <flux:input wire:model="tags" placeholder="Comma-separated tags" />
                </flux:field>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <flux:heading size="lg">Pricing & Inventory</flux:heading>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <flux:field>
                    <flux:label>Price (cents)</flux:label>
                    <flux:input wire:model="price" type="number" min="0" />
                    <flux:error name="price" />
                </flux:field>

                <flux:field>
                    <flux:label>SKU</flux:label>
                    <flux:input wire:model="sku" />
                </flux:field>

                <flux:field>
                    <flux:label>Inventory Quantity</flux:label>
                    <flux:input wire:model="inventoryQuantity" type="number" min="0" />
                </flux:field>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <flux:button href="{{ route('admin.products.index') }}" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">
                {{ $product ? 'Update Product' : 'Create Product' }}
            </flux:button>
        </div>
    </form>
</div>
