<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $product && $product->exists ? 'Edit product' : 'New product' }}</flux:heading>
        <flux:button variant="ghost" href="{{ route('admin.products.index') }}" wire:navigate>Back</flux:button>
    </div>

    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <form wire:submit="save" class="space-y-4">
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="space-y-4 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
                    <flux:input wire:model="title" label="Title" required />
                    <flux:textarea wire:model="description_html" label="Description" rows="6" />
                </div>

                <div class="space-y-4 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
                    <flux:heading size="sm">Variants</flux:heading>
                    @if (count($variants) === 0)
                        <div class="grid gap-3 sm:grid-cols-2">
                            <flux:input wire:model="sku" label="SKU" />
                            <flux:input wire:model.number="price_amount" type="number" label="Price (cents)" />
                        </div>
                    @else
                        @foreach ($variants as $index => $variant)
                            <div wire:key="variant-{{ $variant['id'] }}" class="grid gap-3 sm:grid-cols-2">
                                <flux:input wire:model="variants.{{ $index }}.sku" label="SKU" />
                                <flux:input wire:model.number="variants.{{ $index }}.price_amount" type="number" label="Price (cents)" />
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="space-y-3 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
                    <flux:heading size="sm">Media</flux:heading>
                    <input type="file" wire:model="image" accept="image/*" class="text-sm" />
                    @if ($product && $product->exists)
                        <div class="flex flex-wrap gap-2">
                            @foreach ($product->media as $media)
                                <div wire:key="media-{{ $media->id }}" class="rounded border border-zinc-200 p-2 text-xs dark:border-zinc-800">
                                    {{ basename($media->storage_key) }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="space-y-4">
                <div class="space-y-3 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
                    <flux:select wire:model="status" label="Status">
                        @foreach ($statuses as $case)
                            <flux:select.option value="{{ $case->value }}">{{ ucfirst($case->value) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="vendor" label="Vendor" />
                    <flux:input wire:model="product_type" label="Type" />
                    <flux:input wire:model="tags_input" label="Tags (comma-separated)" />
                </div>

                <flux:button type="submit" variant="primary" class="w-full">Save</flux:button>
            </div>
        </div>
    </form>
</div>
