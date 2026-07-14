<form wire:submit="save" class="space-y-6 pb-20">
    <x-admin.page-header :title="$product ? $product->title : 'Add product'" :description="$product ? 'Update product details, options, media, and availability.' : 'Create a new item for your catalog.'">
        <x-slot:actions>
            @if($product && in_array($adminRole, ['owner', 'admin']))
                <x-admin.confirmation-modal name="delete-product" title="Archive this product?" description="The product will no longer appear in your storefront." confirm-action="deleteProduct" confirm-label="Archive product">
                    <x-slot:trigger><flux:button type="button" variant="danger">Archive</flux:button></x-slot:trigger>
                </x-admin.confirmation-modal>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <fieldset @disabled($adminRole === 'support') class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(19rem,1fr)]">
        <div class="space-y-6">
            <x-admin.card>
                <div class="space-y-5">
                    <flux:input wire:model.blur="title" label="Title" placeholder="Short Sleeve T-Shirt" required />
                    <flux:textarea wire:model="descriptionHtml" label="Description" rows="8" placeholder="Describe your product…" />
                </div>
            </x-admin.card>

            <x-admin.card title="Media" description="Upload product photography. JPG, PNG, GIF, or WebP up to 5 MB.">
                @if($media)
                    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach($media as $item)
                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
                                <div class="group relative aspect-square overflow-hidden"><img src="{{ $item['url'] }}" alt="{{ $item['alt_text'] }}" class="h-full w-full object-cover"><button type="button" wire:click="removeMedia({{ $item['id'] }})" wire:confirm="Remove this image?" class="absolute right-2 top-2 grid size-8 place-items-center rounded-full bg-slate-950/75 text-white opacity-0 transition group-hover:opacity-100 focus:opacity-100" aria-label="Remove image">&times;</button></div>
                                <div class="space-y-2 p-2"><input value="{{ $item['alt_text'] }}" wire:change="updateMediaAlt({{ $item['id'] }}, $event.target.value)" class="admin-input w-full text-xs" placeholder="Alt text" aria-label="Image alt text"><div class="flex justify-end"><flux:button type="button" size="xs" variant="ghost" icon="arrow-left" wire:click="moveMedia({{ $item['id'] }}, 'up')" aria-label="Move image earlier" /><flux:button type="button" size="xs" variant="ghost" icon="arrow-right" wire:click="moveMedia({{ $item['id'] }}, 'down')" aria-label="Move image later" /></div></div>
                            </div>
                        @endforeach
                    </div>
                @endif
                <label class="flex min-h-32 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 p-6 text-center hover:border-blue-500 hover:bg-blue-50/50 dark:border-slate-700 dark:hover:bg-blue-950/20">
                    <flux:icon.arrow-up-tray class="size-7 text-slate-400" /><span class="mt-2 text-sm font-medium">Choose images to upload</span><span class="mt-1 text-xs text-slate-500">Multiple files supported</span>
                    <input type="file" wire:model="newMedia" multiple accept="image/*" class="sr-only">
                </label>
                <div wire:loading wire:target="newMedia" class="mt-3 text-sm text-blue-700">Preparing uploads…</div>@error('newMedia.*')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </x-admin.card>

            <x-admin.card title="Variants" description="Define up to three options and set price and stock for every combination.">
                <div class="space-y-5">
                    @foreach($options as $optionIndex => $option)
                        <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700" wire:key="option-{{ $optionIndex }}">
                            <div class="flex items-start gap-3"><div class="flex-1"><flux:input wire:model.blur="options.{{ $optionIndex }}.name" label="Option name" placeholder="Size" /></div><flux:button type="button" variant="ghost" icon="trash" wire:click="removeOption({{ $optionIndex }})" aria-label="Remove option" class="mt-6" /></div>
                            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                @foreach($option['values'] as $valueIndex => $value)<div class="flex gap-2" wire:key="option-{{ $optionIndex }}-value-{{ $valueIndex }}"><flux:input wire:model.blur="options.{{ $optionIndex }}.values.{{ $valueIndex }}" placeholder="Option value" aria-label="Option value" /><flux:button type="button" variant="ghost" icon="x-mark" wire:click="removeOptionValue({{ $optionIndex }}, {{ $valueIndex }})" aria-label="Remove value" /></div>@endforeach
                            </div>
                            <flux:button type="button" variant="ghost" size="sm" icon="plus" wire:click="addOptionValue({{ $optionIndex }})" class="mt-3">Add value</flux:button>
                        </div>
                    @endforeach
                    @if(count($options) < 3)<flux:button type="button" variant="ghost" icon="plus" wire:click="addOption">Add option</flux:button>@endif
                </div>
                <div class="mt-6 overflow-x-auto"><table class="admin-table min-w-[760px]"><thead><tr><th>Variant</th><th>SKU</th><th>Price (minor)</th><th>Compare at</th><th>Quantity</th><th>Ships</th></tr></thead><tbody>
                    @foreach($variants as $variantIndex => $variant)<tr wire:key="variant-{{ md5($variant['title']) }}"><td class="font-medium">{{ $variant['title'] }}</td><td><input wire:model="variants.{{ $variantIndex }}.sku" class="admin-input min-w-28" aria-label="SKU for {{ $variant['title'] }}"></td><td><input wire:model="variants.{{ $variantIndex }}.price" type="number" min="0" class="admin-input w-28" aria-label="Price for {{ $variant['title'] }}"></td><td><input wire:model="variants.{{ $variantIndex }}.compareAtPrice" type="number" min="0" class="admin-input w-28" aria-label="Compare at price for {{ $variant['title'] }}"></td><td><input wire:model="variants.{{ $variantIndex }}.quantity" type="number" min="0" class="admin-input w-24" aria-label="Quantity for {{ $variant['title'] }}"></td><td><flux:checkbox wire:model="variants.{{ $variantIndex }}.requiresShipping" aria-label="Requires shipping for {{ $variant['title'] }}" /></td></tr>@endforeach
                </tbody></table></div>
            </x-admin.card>

            <x-admin.card title="Search engine listing"><flux:input wire:model="handle" label="URL handle" required><x-slot:description>{{ url('/products') }}/<span class="font-mono">{{ $handle ?: 'product-handle' }}</span></x-slot:description></flux:input></x-admin.card>
        </div>

        <div class="space-y-6 xl:sticky xl:top-24">
            <x-admin.card title="Status"><flux:select wire:model="status" label="Product status"><flux:select.option value="draft">Draft</flux:select.option><flux:select.option value="active">Active</flux:select.option><flux:select.option value="archived">Archived</flux:select.option></flux:select></x-admin.card>
            <x-admin.card title="Publishing"><flux:input wire:model="publishedAt" type="datetime-local" label="Published at" /></x-admin.card>
            <x-admin.card title="Organization"><div class="space-y-4"><flux:input wire:model="vendor" label="Vendor" placeholder="Nike" /><flux:input wire:model="productType" label="Product type" placeholder="T-Shirts" /><flux:input wire:model="tags" label="Tags" placeholder="summer, cotton, sale"><x-slot:description>Separate tags with commas.</x-slot:description></flux:input></div></x-admin.card>
            <x-admin.card title="Collections"><div class="max-h-72 space-y-2 overflow-y-auto">@forelse($this->availableCollections as $collection)<label class="flex min-h-10 items-center gap-3 rounded-lg px-2 hover:bg-slate-50 dark:hover:bg-slate-800"><input type="checkbox" wire:model="collectionIds" value="{{ $collection->id }}" class="rounded border-slate-300"><span class="text-sm">{{ $collection->title }}</span></label>@empty<p class="text-sm text-slate-500">No collections yet.</p>@endforelse</div></x-admin.card>
        </div>
    </fieldset>

    @if($adminRole !== 'support')
        <x-admin.sticky-save-bar :discard-url="url('/admin/products')" :dirty-only="false" />
    @endif
</form>
