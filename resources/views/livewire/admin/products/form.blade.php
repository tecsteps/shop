<div>
    <flux:heading size="xl">{{ $product ? __('Edit Product') : __('New Product') }}</flux:heading>

    <form wire:submit="save" class="mt-6 space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <flux:field>
                    <flux:input wire:model="title" :label="__('Title')" required />
                </flux:field>

                <flux:field>
                    <flux:textarea wire:model="description_html" :label="__('Description')" rows="6" />
                </flux:field>

                <flux:separator />

                <flux:heading size="lg">{{ __('Pricing') }}</flux:heading>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:input wire:model="price_amount" :label="__('Price (cents)')" type="number" min="0" required />
                    </flux:field>
                    <flux:field>
                        <flux:input wire:model="compare_at_price" :label="__('Compare at price (cents)')" type="number" min="0" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:input wire:model="sku" :label="__('SKU')" />
                </flux:field>

                <flux:separator />

                <flux:heading size="lg">{{ __('Media') }}</flux:heading>

                <flux:field>
                    <flux:input wire:model="media" type="file" multiple accept="image/*" :label="__('Upload images')" />
                </flux:field>
            </div>

            <div class="space-y-6">
                <flux:field>
                    <flux:select wire:model="status" :label="__('Status')">
                        <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                        <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                        <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:input wire:model="vendor" :label="__('Vendor')" />
                </flux:field>

                <flux:field>
                    <flux:input wire:model="product_type" :label="__('Product type')" />
                </flux:field>

                <flux:field>
                    <flux:input wire:model="tags" :label="__('Tags')" placeholder="{{ __('Comma separated') }}" />
                </flux:field>
            </div>
        </div>

        <flux:separator />

        <div class="flex items-center justify-end gap-2">
            <flux:button :href="route('admin.products.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button variant="primary" type="submit">{{ $product ? __('Save') : __('Create product') }}</flux:button>
        </div>
    </form>
</div>
