<div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $mode === 'create' ? 'New product' : 'Edit product' }}</flux:heading>
        <flux:button :href="route('admin.products.index')" variant="ghost" wire:navigate>Back</flux:button>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="title" placeholder="Short sleeve t-shirt" />
                    <flux:error name="title" />
                </flux:field>

                <div class="mt-4">
                    <flux:field>
                        <flux:label>Handle</flux:label>
                        <flux:input wire:model="handle" placeholder="short-sleeve-t-shirt" />
                        <flux:error name="handle" />
                    </flux:field>
                </div>

                <div class="mt-4">
                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="description" rows="6" />
                        <flux:error name="description" />
                    </flux:field>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Pricing & Inventory</flux:heading>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <flux:field>
                        <flux:label>Price (cents)</flux:label>
                        <flux:input type="number" wire:model="priceAmount" min="0" />
                        <flux:error name="priceAmount" />
                    </flux:field>
                    <flux:field>
                        <flux:label>SKU</flux:label>
                        <flux:input wire:model="sku" />
                        <flux:error name="sku" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Inventory</flux:label>
                        <flux:input type="number" wire:model="inventoryQuantity" min="0" />
                        <flux:error name="inventoryQuantity" />
                    </flux:field>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Status</flux:heading>
                <div class="mt-4">
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            @foreach ($statuses as $statusOption)
                                <flux:select.option value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="status" />
                    </flux:field>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Organization</flux:heading>
                <div class="mt-4 space-y-4">
                    <flux:field>
                        <flux:label>Vendor</flux:label>
                        <flux:input wire:model="vendor" />
                        <flux:error name="vendor" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Product type</flux:label>
                        <flux:input wire:model="productType" />
                        <flux:error name="productType" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Tags (comma separated)</flux:label>
                        <flux:input wire:model="tagsInput" />
                        <flux:error name="tagsInput" />
                    </flux:field>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 lg:col-span-3">
            <flux:button variant="ghost" :href="route('admin.products.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save product</flux:button>
        </div>
    </form>
</div>
