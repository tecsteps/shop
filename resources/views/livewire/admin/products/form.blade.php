<form wire:submit="save" class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl">{{ $this->product ? 'Edit product' : 'Create product' }}</flux:heading>
            <flux:text>{{ $title ?: 'Product details' }}</flux:text>
        </div>

        <div class="flex gap-2">
            <flux:button :href="route('admin.products.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save product</flux:button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="space-y-6">
            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="grid gap-4">
                    <flux:input wire:model="title" label="Title" required />
                    <flux:textarea wire:model="descriptionHtml" label="Description" rows="8" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:input wire:model="vendor" label="Vendor" />
                        <flux:input wire:model="productType" label="Product type" />
                    </div>
                    <flux:input wire:model="tags" label="Tags" placeholder="linen, summer, featured" />
                </div>
            </section>

            @if($variants === [])
                <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <flux:heading size="lg">Default variant</flux:heading>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <flux:input wire:model="priceAmount" type="number" min="0" label="Price cents" />
                        <flux:input wire:model="sku" label="SKU" />
                        <flux:input wire:model="quantityOnHand" type="number" min="0" label="Stock" />
                    </div>
                </section>
            @endif

            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <flux:heading size="lg">Variants</flux:heading>
                    </div>
                    <flux:button type="button" variant="ghost" icon="plus" wire:click="addOption" :disabled="count($options) >= 3">Add option</flux:button>
                </div>

                @error('options')
                    <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">{{ $message }}</div>
                @enderror

                <div class="mt-4 space-y-4">
                    @foreach($options as $optionIndex => $option)
                        <div wire:key="product-option-{{ $optionIndex }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                            <div class="grid gap-4 lg:grid-cols-[16rem_1fr_auto] lg:items-start">
                                <flux:input wire:model.live.blur="options.{{ $optionIndex }}.name" label="Option name" placeholder="Size" />

                                <div class="grid gap-2">
                                    <div class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Values</div>
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        @foreach(($option['values'] ?? []) as $valueIndex => $value)
                                            <div wire:key="product-option-{{ $optionIndex }}-value-{{ $valueIndex }}" class="flex gap-2">
                                                <flux:input wire:model.live.blur="options.{{ $optionIndex }}.values.{{ $valueIndex }}" placeholder="S" />
                                                <flux:button type="button" variant="ghost" icon="x-mark" square tooltip="Remove value" wire:click="removeOptionValue({{ $optionIndex }}, {{ $valueIndex }})" />
                                            </div>
                                        @endforeach
                                    </div>
                                    <div>
                                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addOptionValue({{ $optionIndex }})">Add value</flux:button>
                                    </div>
                                </div>

                                <flux:button type="button" variant="danger" icon="trash" wire:click="removeOption({{ $optionIndex }})">Remove</flux:button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @error('variants')
                    <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">{{ $message }}</div>
                @enderror

                @if($variants !== [])
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-normal text-zinc-500 dark:text-zinc-400">
                                    <th class="px-3 py-2">Variant</th>
                                    <th class="px-3 py-2">SKU</th>
                                    <th class="px-3 py-2">Barcode</th>
                                    <th class="px-3 py-2">Price</th>
                                    <th class="px-3 py-2">Compare</th>
                                    <th class="px-3 py-2">Weight</th>
                                    <th class="px-3 py-2">Stock</th>
                                    <th class="px-3 py-2">Ship</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach($variants as $variantIndex => $variant)
                                    <tr wire:key="product-variant-row-{{ $variant['key'] ?? $variantIndex }}">
                                        <td class="min-w-36 px-3 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $variant['title'] ?? 'Variant' }}</td>
                                        <td class="min-w-36 px-3 py-3">
                                            <flux:input wire:model="variants.{{ $variantIndex }}.sku" aria-label="SKU for {{ $variant['title'] ?? 'variant' }}" />
                                        </td>
                                        <td class="min-w-36 px-3 py-3">
                                            <flux:input wire:model="variants.{{ $variantIndex }}.barcode" aria-label="Barcode for {{ $variant['title'] ?? 'variant' }}" />
                                        </td>
                                        <td class="min-w-28 px-3 py-3">
                                            <flux:input wire:model="variants.{{ $variantIndex }}.priceAmount" type="number" min="0" aria-label="Price for {{ $variant['title'] ?? 'variant' }}" />
                                        </td>
                                        <td class="min-w-28 px-3 py-3">
                                            <flux:input wire:model="variants.{{ $variantIndex }}.compareAtAmount" type="number" min="0" aria-label="Compare at price for {{ $variant['title'] ?? 'variant' }}" />
                                        </td>
                                        <td class="min-w-28 px-3 py-3">
                                            <flux:input wire:model="variants.{{ $variantIndex }}.weightG" type="number" min="0" aria-label="Weight for {{ $variant['title'] ?? 'variant' }}" />
                                        </td>
                                        <td class="min-w-28 px-3 py-3">
                                            <flux:input wire:model="variants.{{ $variantIndex }}.quantityOnHand" type="number" min="0" aria-label="Stock for {{ $variant['title'] ?? 'variant' }}" />
                                        </td>
                                        <td class="px-3 py-3">
                                            <flux:checkbox wire:model="variants.{{ $variantIndex }}.requiresShipping" aria-label="Requires shipping for {{ $variant['title'] ?? 'variant' }}" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:select wire:model="status" label="Status">
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</option>
                    @endforeach
                </flux:select>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:input wire:model="handle" label="Handle" />
            </section>
        </aside>
    </div>
</form>
