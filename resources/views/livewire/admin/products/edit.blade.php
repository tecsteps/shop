<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $product->title }}</flux:heading>
        <flux:button variant="ghost" href="{{ url('/admin/products') }}">Back</flux:button>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="space-y-4 rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
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
                    <flux:textarea wire:model="descriptionHtml" rows="6" />
                </flux:field>
            </div>

            <div class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                <flux:heading size="md">Variants</flux:heading>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs uppercase text-neutral-500">
                            <tr>
                                <th class="py-2">SKU</th>
                                <th class="py-2">Price</th>
                                <th class="py-2">Compare at</th>
                                <th class="py-2">Weight (g)</th>
                                <th class="py-2">On hand</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($variants as $index => $variant)
                                <tr wire:key="variant-{{ $variant['id'] }}" class="border-t border-neutral-100 dark:border-neutral-800">
                                    <td class="py-2 pr-2"><flux:input wire:model="variants.{{ $index }}.sku" size="sm" /></td>
                                    <td class="py-2 pr-2"><flux:input type="number" wire:model="variants.{{ $index }}.price_amount" size="sm" /></td>
                                    <td class="py-2 pr-2"><flux:input type="number" wire:model="variants.{{ $index }}.compare_at_amount" size="sm" /></td>
                                    <td class="py-2 pr-2"><flux:input type="number" wire:model="variants.{{ $index }}.weight_g" size="sm" /></td>
                                    <td class="py-2"><flux:input type="number" wire:model="variants.{{ $index }}.quantity_on_hand" size="sm" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                <flux:heading size="md">Media</flux:heading>
                <div class="mt-3 text-sm text-neutral-500">Media uploader is available in a later iteration.</div>
                @if ($product->media->isNotEmpty())
                    <ul class="mt-3 grid grid-cols-4 gap-3">
                        @foreach ($product->media as $asset)
                            <li wire:key="media-{{ $asset->id }}" class="rounded border border-neutral-200 p-2 text-xs text-neutral-500 dark:border-neutral-800">{{ $asset->alt_text ?? 'Asset '.$asset->id }}</li>
                        @endforeach
                    </ul>
                @endif
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
                    <flux:error name="status" />
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
                    <flux:input wire:model="tags" />
                </flux:field>
            </div>
            <flux:button type="submit" variant="primary" class="w-full">Save changes</flux:button>
        </div>
    </form>
</div>
