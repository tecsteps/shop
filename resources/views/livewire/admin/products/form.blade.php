<div>
    @include('livewire.admin.components.page-heading', [
        'title' => $isEditing ? 'Edit product' : 'Add product',
        'breadcrumbs' => [['label' => 'Products', 'href' => route('admin.products')], ['label' => $isEditing ? ($title ?: 'Edit product') : 'New product']],
    ])

    <form wire:submit="save" class="pb-24">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="grid content-start gap-6">
                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="product-details-heading">
                    <h2 id="product-details-heading" class="text-base font-semibold">Product details</h2>
                    <div class="mt-5 grid gap-5">
                        <div>
                            <label for="product-title" class="mb-1.5 block text-sm font-medium">Title <span class="text-rose-600" aria-hidden="true">*</span></label>
                            <input id="product-title" type="text" wire:model.blur="title" required maxlength="255" class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            @error('title')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="product-description" class="mb-1.5 block text-sm font-medium">Description</label>
                            <textarea id="product-description" rows="7" wire:model="descriptionHtml" placeholder="Describe your product" class="w-full resize-y rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                            <p class="mt-1.5 text-xs text-zinc-500">Safe formatting such as paragraphs, emphasis, and lists is retained.</p>
                            @error('descriptionHtml')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="product-pricing-heading">
                    <h2 id="product-pricing-heading" class="text-base font-semibold">Default pricing and inventory</h2>
                    <p class="mt-1 text-sm text-zinc-500">New variants inherit this price. Each variant can be adjusted below after saving.</p>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="product-price" class="mb-1.5 block text-sm font-medium">Price (minor units)</label>
                            <input id="product-price" type="number" min="0" step="1" inputmode="numeric" wire:model="priceAmount" class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            <p class="mt-1 text-xs text-zinc-500">For example, 2499 = 24.99 {{ data_get(app()->bound('current_store') ? app('current_store') : null, 'default_currency', 'EUR') }}</p>
                            @error('priceAmount')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="product-compare-price" class="mb-1.5 block text-sm font-medium">Compare-at price (minor units)</label>
                            <input id="product-compare-price" type="number" min="0" step="1" inputmode="numeric" wire:model="compareAtAmount" class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            @error('compareAtAmount')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="product-inventory" class="mb-1.5 block text-sm font-medium">Available quantity</label>
                            <input id="product-inventory" type="number" min="0" step="1" inputmode="numeric" wire:model="quantityOnHand" class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            @error('quantityOnHand')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="product-sku" class="mb-1.5 block text-sm font-medium">Default SKU</label>
                            <input id="product-sku" type="text" maxlength="100" wire:model="sku" class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            @error('sku')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="product-options-heading">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 id="product-options-heading" class="text-base font-semibold">Options and variants</h2>
                            <p class="mt-1 text-sm text-zinc-500">Add up to three options. Separate values with commas; the full combination matrix is created on save.</p>
                        </div>
                        @if (count($options) < 3)
                            <button type="button" wire:click="addOption" class="inline-flex min-h-10 items-center rounded-xl border border-zinc-300 px-3 text-sm font-semibold hover:bg-zinc-50 focus-visible:outline-2 focus-visible:outline-blue-600 dark:border-zinc-700 dark:hover:bg-zinc-800">Add option</button>
                        @endif
                    </div>
                    <div class="mt-5 grid gap-4">
                        @forelse ($options as $index => $option)
                            <div wire:key="product-option-{{ $index }}" class="grid gap-3 rounded-xl border border-zinc-200 p-4 sm:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)_auto] dark:border-zinc-800">
                                <div>
                                    <label for="option-name-{{ $index }}" class="mb-1.5 block text-sm font-medium">Option name</label>
                                    <input id="option-name-{{ $index }}" type="text" wire:model="options.{{ $index }}.name" placeholder="Size" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                </div>
                                <div>
                                    <label for="option-values-{{ $index }}" class="mb-1.5 block text-sm font-medium">Values</label>
                                    <input id="option-values-{{ $index }}" type="text" wire:model="options.{{ $index }}.values" placeholder="Small, Medium, Large" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                </div>
                                <div class="flex items-end">
                                    <button type="button" wire:click="removeOption({{ $index }})" class="min-h-10 rounded-lg px-3 text-sm font-medium text-rose-700 hover:bg-rose-50 focus-visible:outline-2 focus-visible:outline-rose-600 dark:text-rose-300 dark:hover:bg-rose-950">Remove</button>
                                </div>
                                @error("options.{$index}.name")<p class="text-sm text-rose-600 sm:col-span-2">{{ $message }}</p>@enderror
                                @error("options.{$index}.values")<p class="text-sm text-rose-600 sm:col-span-2">{{ $message }}</p>@enderror
                            </div>
                        @empty
                            <p class="rounded-xl bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-950 dark:text-zinc-400">No options yet. Products without options use a single default variant.</p>
                        @endforelse
                    </div>
                    @error('options')<p class="mt-3 text-sm text-rose-600">{{ $message }}</p>@enderror
                </section>

                @if ($isEditing && $hasOptionMatrix)
                    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="variant-matrix-heading">
                        <h2 id="variant-matrix-heading" class="text-base font-semibold">Variant inventory and pricing</h2>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                                <thead><tr class="text-left text-xs font-semibold uppercase tracking-wide text-zinc-500"><th class="py-3 pr-4">Variant</th><th class="py-3 pr-4">SKU</th><th class="py-3 pr-4">Price</th><th class="py-3 pr-4">Compare-at</th><th class="py-3 pr-4">Available</th><th class="py-3 pr-4">Policy</th><th class="py-3">Shipping</th></tr></thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach ($variants as $index => $variant)
                                        <tr wire:key="variant-{{ $variant['id'] }}" class="align-middle">
                                            <td class="py-3 pr-4 font-medium">{{ $variant['title'] }}<input type="hidden" wire:model="variants.{{ $index }}.id"></td>
                                            <td class="py-3 pr-4"><input aria-label="SKU for {{ $variant['title'] }}" type="text" wire:model="variants.{{ $index }}.sku" class="w-28 rounded-lg border border-zinc-300 px-2 py-2 dark:border-zinc-700 dark:bg-zinc-950"></td>
                                            <td class="py-3 pr-4"><input aria-label="Price for {{ $variant['title'] }}" type="number" min="0" wire:model="variants.{{ $index }}.price_amount" class="w-24 rounded-lg border border-zinc-300 px-2 py-2 dark:border-zinc-700 dark:bg-zinc-950"></td>
                                            <td class="py-3 pr-4"><input aria-label="Compare-at price for {{ $variant['title'] }}" type="number" min="0" wire:model="variants.{{ $index }}.compare_at_amount" class="w-24 rounded-lg border border-zinc-300 px-2 py-2 dark:border-zinc-700 dark:bg-zinc-950"></td>
                                            <td class="py-3 pr-4"><input aria-label="Available quantity for {{ $variant['title'] }}" type="number" min="0" wire:model="variants.{{ $index }}.quantity_on_hand" class="w-20 rounded-lg border border-zinc-300 px-2 py-2 dark:border-zinc-700 dark:bg-zinc-950"><span class="sr-only">{{ $variant['quantity_reserved'] }} reserved</span></td>
                                            <td class="py-3 pr-4"><select aria-label="Inventory policy for {{ $variant['title'] }}" wire:model="variants.{{ $index }}.inventory_policy" class="rounded-lg border border-zinc-300 px-2 py-2 dark:border-zinc-700 dark:bg-zinc-950"><option value="deny">Deny</option><option value="continue">Continue selling</option></select></td>
                                            <td class="py-3"><label class="inline-flex items-center gap-2"><input type="checkbox" wire:model="variants.{{ $index }}.requires_shipping" class="size-4 rounded border-zinc-300 text-blue-600"><span class="sr-only">Requires shipping</span></label></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @error('variants')<p class="mt-3 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </section>
                @endif

                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="product-organization-heading">
                    <h2 id="product-organization-heading" class="text-base font-semibold">Organization</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div><label for="product-type" class="mb-1.5 block text-sm font-medium">Product type</label><input id="product-type" type="text" wire:model="productType" class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">@error('productType')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                        <div><label for="product-vendor" class="mb-1.5 block text-sm font-medium">Vendor</label><input id="product-vendor" type="text" wire:model="vendor" class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">@error('vendor')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                    </div>
                    <fieldset class="mt-5">
                        <legend class="text-sm font-medium">Collections</legend>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @forelse ($collections as $collection)
                                <label for="product-collection-{{ $collection->id }}" class="flex min-h-11 items-center gap-3 rounded-lg border border-zinc-200 px-3 text-sm dark:border-zinc-800">
                                    <input id="product-collection-{{ $collection->id }}" type="checkbox" value="{{ $collection->id }}" wire:model="collectionIds" class="size-4 rounded border-zinc-300 text-blue-600">
                                    <span>{{ $collection->title }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-zinc-500">Create a collection before assigning this product.</p>
                            @endforelse
                        </div>
                        @error('collectionIds')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </fieldset>
                </section>
            </div>

            <aside class="grid content-start gap-6">
                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="product-status-heading">
                    <h2 id="product-status-heading" class="text-base font-semibold">Status</h2>
                    <label for="product-status" class="sr-only">Product status</label>
                    <select id="product-status" wire:model="status" class="mt-4 min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950"><option value="draft">Draft</option><option value="active">Active</option><option value="archived">Archived</option></select>
                    @error('status')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </section>
                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="product-search-heading">
                    <h2 id="product-search-heading" class="text-base font-semibold">Search engine listing</h2>
                    <p class="mt-2 text-sm leading-6 text-zinc-500 dark:text-zinc-400">The product title, description, type, vendor, and tags help customers find this item.</p>
                    <div class="mt-4">
                        <label for="product-handle" class="mb-1.5 block text-sm font-medium">URL handle</label>
                        <div class="flex items-center rounded-xl border border-zinc-300 bg-zinc-50 px-3 dark:border-zinc-700 dark:bg-zinc-950"><span class="text-xs text-zinc-500">/products/</span><input id="product-handle" type="text" wire:model="handle" class="min-w-0 flex-1 bg-transparent py-3 text-sm outline-none"></div>
                        @error('handle')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </section>
            </aside>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-zinc-200 bg-white/95 px-4 py-3 backdrop-blur sm:px-6 lg:left-64 lg:px-8 dark:border-zinc-800 dark:bg-zinc-950/95">
            <div class="mx-auto flex max-w-[1600px] items-center justify-between gap-3"><a href="{{ route('admin.products') }}" wire:navigate class="inline-flex min-h-11 items-center rounded-xl px-4 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800">Discard</a><button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:cursor-wait disabled:opacity-60"><span wire:loading.remove wire:target="save">{{ $isEditing ? 'Save product' : 'Create product' }}</span><span wire:loading wire:target="save">Saving…</span></button></div>
        </div>
    </form>
</div>
