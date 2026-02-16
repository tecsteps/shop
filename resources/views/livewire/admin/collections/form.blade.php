<div>
    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-admin.card>
                    <flux:input wire:model="title" label="Title" required />
                    <div class="mt-4">
                        <flux:textarea wire:model="description_html" label="Description" rows="4" />
                    </div>
                </x-admin.card>

                {{-- Product picker --}}
                <x-admin.card>
                    <h3 class="mb-4 font-semibold text-gray-900 dark:text-white">Products</h3>
                    <flux:input wire:model.live.debounce.300ms="productSearch" placeholder="Search products..." icon="magnifying-glass" />

                    @if(count($searchResults))
                        <ul class="mt-2 max-h-40 overflow-y-auto rounded border border-gray-200 dark:border-zinc-700">
                            @foreach($searchResults as $product)
                                <li wire:click="addProduct({{ $product->id }})" class="cursor-pointer px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-zinc-800">{{ $product->title }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="mt-4 space-y-2">
                        @foreach($selectedProductModels as $product)
                            <div class="flex items-center justify-between rounded border border-gray-100 px-3 py-2 dark:border-zinc-700">
                                <span class="text-sm text-gray-900 dark:text-white">{{ $product->title }}</span>
                                <flux:button wire:click="removeProduct({{ $product->id }})" variant="ghost" size="sm" icon="x-mark" />
                            </div>
                        @endforeach
                    </div>
                </x-admin.card>
            </div>

            <div class="space-y-6">
                <x-admin.card>
                    <flux:select wire:model="status" label="Status">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="archived">Archived</option>
                    </flux:select>
                </x-admin.card>
                <flux:button type="submit" variant="primary" class="w-full">{{ $collection ? 'Update' : 'Create' }} collection</flux:button>
            </div>
        </div>
    </form>
</div>
