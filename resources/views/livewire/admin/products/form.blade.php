<div>
    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Main column --}}
            <div class="space-y-6 lg:col-span-2">
                <x-admin.card>
                    <flux:input wire:model="title" label="Title" required />
                    <div class="mt-4">
                        <flux:textarea wire:model="description_html" label="Description" rows="5" />
                    </div>
                </x-admin.card>

                {{-- Media --}}
                <x-admin.card>
                    <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Media</h3>
                    @if($product && $product->media->count())
                        <div class="mb-4 flex flex-wrap gap-2">
                            @foreach($product->media as $media)
                                <img src="{{ Storage::url($media->url) }}" alt="" class="h-20 w-20 rounded object-cover">
                            @endforeach
                        </div>
                    @endif
                    <input type="file" wire:model="newMedia" multiple accept="image/*"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:rounded file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                </x-admin.card>

                {{-- Options --}}
                <x-admin.card>
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Options</h3>
                        <flux:button wire:click="addOption" variant="ghost" size="sm">Add option</flux:button>
                    </div>
                    @foreach($options as $i => $option)
                        <div class="mb-3 flex items-start gap-3">
                            <flux:input wire:model="options.{{ $i }}.name" placeholder="Option name (e.g. Size)" class="w-40" />
                            <flux:input wire:model="options.{{ $i }}.values" placeholder="Values (comma separated)" class="flex-1" />
                            <flux:button wire:click="removeOption({{ $i }})" variant="ghost" size="sm" icon="x-mark" />
                        </div>
                    @endforeach
                    @if(count($options) > 0)
                        <flux:button wire:click="generateVariants" variant="ghost" size="sm" class="mt-2">Generate variants</flux:button>
                    @endif
                </x-admin.card>

                {{-- Variants --}}
                <x-admin.card>
                    <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Variants</h3>
                    <div class="space-y-4">
                        @foreach($variants as $i => $variant)
                            <div class="rounded border border-gray-100 p-4 dark:border-zinc-700">
                                @if(!empty($variant['option_values']))
                                    <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ implode(' / ', $variant['option_values']) }}
                                    </p>
                                @endif
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                                    <flux:input wire:model="variants.{{ $i }}.price" label="Price" type="number" step="0.01" required />
                                    <flux:input wire:model="variants.{{ $i }}.compare_at_price" label="Compare at" type="number" step="0.01" />
                                    <flux:input wire:model="variants.{{ $i }}.sku" label="SKU" />
                                    <flux:input wire:model="variants.{{ $i }}.barcode" label="Barcode" />
                                    <flux:input wire:model="variants.{{ $i }}.weight" label="Weight (g)" type="number" />
                                    <flux:input wire:model="variants.{{ $i }}.quantity" label="Quantity" type="number" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-admin.card>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <x-admin.card>
                    <flux:select wire:model="status" label="Status">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="archived">Archived</option>
                    </flux:select>
                </x-admin.card>

                <x-admin.card>
                    <flux:input wire:model="vendor" label="Vendor" class="mb-4" />
                    <flux:input wire:model="product_type" label="Product type" class="mb-4" />
                    <flux:input wire:model="tags" label="Tags" placeholder="Comma separated" />
                </x-admin.card>

                <flux:button type="submit" variant="primary" class="w-full">
                    {{ $product ? 'Update product' : 'Save product' }}
                </flux:button>
            </div>
        </div>
    </form>
</div>
