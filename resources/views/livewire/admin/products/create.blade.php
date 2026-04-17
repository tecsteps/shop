<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Add product</flux:heading>
        <flux:button variant="ghost" href="{{ url('/admin/products') }}">Cancel</flux:button>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="space-y-4 rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="title" placeholder="Short Sleeve T-Shirt" />
                    <flux:error name="title" />
                </flux:field>
                <flux:field>
                    <flux:label>Handle</flux:label>
                    <flux:input wire:model="handle" placeholder="auto-generated from title" />
                    <flux:error name="handle" />
                </flux:field>
                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="descriptionHtml" rows="6" />
                    <flux:error name="descriptionHtml" />
                </flux:field>
            </div>

            <div class="space-y-4 rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                <flux:heading size="md">Default variant</flux:heading>
                <div class="grid grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Price (cents)</flux:label>
                        <flux:input type="number" wire:model="priceAmount" min="0" />
                        <flux:error name="priceAmount" />
                    </flux:field>
                    <flux:field>
                        <flux:label>On hand</flux:label>
                        <flux:input type="number" wire:model="quantityOnHand" min="0" />
                        <flux:error name="quantityOnHand" />
                    </flux:field>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="space-y-3 rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model="status">
                        <flux:select.option value="draft">Draft</flux:select.option>
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="archived">Archived</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>Vendor</flux:label>
                    <flux:input wire:model="vendor" />
                </flux:field>
                <flux:field>
                    <flux:label>Product type</flux:label>
                    <flux:input wire:model="productType" />
                </flux:field>
                <flux:field>
                    <flux:label>Tags</flux:label>
                    <flux:input wire:model="tags" placeholder="summer, cotton" />
                    <flux:description>Separate tags with commas.</flux:description>
                </flux:field>
            </div>
            <flux:button type="submit" variant="primary" class="w-full">Save product</flux:button>
        </div>
    </form>
</div>
