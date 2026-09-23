<div>
    @include('livewire.admin.components.page-heading', ['title' => $isEditing ? 'Edit discount' : 'Create discount', 'breadcrumbs' => [['label' => 'Discounts', 'href' => '/admin/discounts'], ['label' => $isEditing ? ($title ?: 'Edit discount') : 'New discount']]])

    <form wire:submit="save" class="pb-24">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="grid content-start gap-6">
                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="discount-type-heading">
                    <h2 id="discount-type-heading" class="text-base font-semibold">Discount method</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <input type="radio" value="code" wire:model.live="type" class="mt-1 accent-blue-600">
                            <span><span class="block text-sm font-semibold">Discount code</span><span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">Customers enter a code at checkout.</span></span>
                        </label>
                        <label class="flex cursor-pointer gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <input type="radio" value="automatic" wire:model.live="type" class="mt-1 accent-blue-600">
                            <span><span class="block text-sm font-semibold">Automatic discount</span><span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">Apply automatically when the cart qualifies.</span></span>
                        </label>
                    </div>
                    @error('type')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="discount-title" class="mb-1.5 block text-sm font-medium">Name</label>
                            <input id="discount-title" type="text" wire:model.blur="title" maxlength="255" required class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            @error('title')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        @if ($type === 'code')
                            <div>
                                <label for="discount-code" class="mb-1.5 block text-sm font-medium">Discount code</label>
                                <div class="flex gap-2">
                                    <input id="discount-code" type="text" wire:model.blur="code" maxlength="64" autocomplete="off" required placeholder="SUMMER20" class="min-w-0 flex-1 rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm uppercase outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                                    <button type="button" wire:click="generateCode" class="min-h-11 rounded-xl border border-zinc-300 px-3 text-sm font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">Generate</button>
                                </div>
                                @error('code')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        @endif
                    </div>
                </section>

                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="discount-value-heading">
                    <h2 id="discount-value-heading" class="text-base font-semibold">Value</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        @foreach ([['percentage', 'Percentage'], ['fixed_amount', 'Fixed amount'], ['free_shipping', 'Free shipping']] as [$value, $label])
                            <label wire:key="discount-value-type-{{ $value }}" class="flex cursor-pointer items-center gap-2 rounded-xl border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                                <input type="radio" value="{{ $value }}" wire:model.live="valueType" class="accent-blue-600">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('valueType')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    @if ($valueType !== 'free_shipping')
                        <div class="mt-4 max-w-sm">
                            <label for="discount-value-amount" class="mb-1.5 block text-sm font-medium">{{ $valueType === 'percentage' ? 'Percentage off' : 'Amount off (minor units)' }}</label>
                            <input id="discount-value-amount" type="number" min="{{ $valueType === 'percentage' ? 1 : 0 }}" @if ($valueType === 'percentage') max="100" @endif step="1" inputmode="numeric" wire:model="valueAmount" class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            @if ($valueType === 'fixed_amount')<p class="mt-1 text-xs text-zinc-500">For example, 500 = 5.00 {{ data_get(app()->bound('current_store') ? app('current_store') : null, 'default_currency', 'EUR') }}.</p>@endif
                            @error('valueAmount')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    @else
                        <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">The discount removes the shipping charge for qualifying orders.</p>
                    @endif
                </section>

                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="discount-conditions-heading">
                    <h2 id="discount-conditions-heading" class="text-base font-semibold">Conditions</h2>
                    <div class="mt-5 grid gap-5">
                        <div class="max-w-sm">
                            <label for="discount-minimum" class="mb-1.5 block text-sm font-medium">Minimum purchase (minor units)</label>
                            <input id="discount-minimum" type="number" min="0" step="1" inputmode="numeric" wire:model="minimumPurchaseAmount" placeholder="No minimum" class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            <p class="mt-1 text-xs text-zinc-500">Leave empty for no minimum. For example, 2500 = 25.00 {{ data_get(app()->bound('current_store') ? app('current_store') : null, 'default_currency', 'EUR') }}.</p>
                            @error('minimumPurchaseAmount')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="discount-product-search" class="mb-1.5 block text-sm font-medium">Specific products</label>
                            <input id="discount-product-search" type="search" wire:model.live.debounce.300ms="productSearch" autocomplete="off" placeholder="Search products..." class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            @error('specificProductIds.*')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                            @if ($products->isNotEmpty())
                                <ul class="mt-2 max-h-52 divide-y divide-zinc-100 overflow-y-auto rounded-xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                                    @foreach ($products as $product)
                                        <li wire:key="discount-product-result-{{ $product->id }}"><button type="button" wire:click="addProduct({{ $product->id }})" class="flex min-h-11 w-full items-center justify-between px-3 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800"><span>{{ $product->title }}</span><span class="text-blue-700 dark:text-blue-300">Add</span></button></li>
                                    @endforeach
                                </ul>
                            @elseif (mb_strlen(trim($productSearch)) >= 2)
                                <p class="mt-2 text-sm text-zinc-500">No matching products.</p>
                            @endif
                            @if ($selectedProducts->isNotEmpty())
                                <ul class="mt-3 flex flex-wrap gap-2" aria-label="Selected products">
                                    @foreach ($selectedProducts as $product)
                                        <li wire:key="discount-product-selected-{{ $product->id }}" class="inline-flex items-center gap-2 rounded-full bg-zinc-100 py-1 pl-3 pr-1 text-sm dark:bg-zinc-800"><span>{{ $product->title }}</span><button type="button" wire:click="removeProduct({{ $product->id }})" aria-label="Remove {{ $product->title }}" class="grid size-7 place-items-center rounded-full text-zinc-600 hover:bg-white dark:text-zinc-300 dark:hover:bg-zinc-700">×</button></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <div>
                            <label for="discount-collection-search" class="mb-1.5 block text-sm font-medium">Specific collections</label>
                            <input id="discount-collection-search" type="search" wire:model.live.debounce.300ms="collectionSearch" autocomplete="off" placeholder="Search collections..." class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            @error('specificCollectionIds.*')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                            @if ($collections->isNotEmpty())
                                <ul class="mt-2 max-h-52 divide-y divide-zinc-100 overflow-y-auto rounded-xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                                    @foreach ($collections as $collection)
                                        <li wire:key="discount-collection-result-{{ $collection->id }}"><button type="button" wire:click="addCollection({{ $collection->id }})" class="flex min-h-11 w-full items-center justify-between px-3 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800"><span>{{ $collection->title }}</span><span class="text-blue-700 dark:text-blue-300">Add</span></button></li>
                                    @endforeach
                                </ul>
                            @elseif (mb_strlen(trim($collectionSearch)) >= 2)
                                <p class="mt-2 text-sm text-zinc-500">No matching collections.</p>
                            @endif
                            @if ($selectedCollections->isNotEmpty())
                                <ul class="mt-3 flex flex-wrap gap-2" aria-label="Selected collections">
                                    @foreach ($selectedCollections as $collection)
                                        <li wire:key="discount-collection-selected-{{ $collection->id }}" class="inline-flex items-center gap-2 rounded-full bg-zinc-100 py-1 pl-3 pr-1 text-sm dark:bg-zinc-800"><span>{{ $collection->title }}</span><button type="button" wire:click="removeCollection({{ $collection->id }})" aria-label="Remove {{ $collection->title }}" class="grid size-7 place-items-center rounded-full text-zinc-600 hover:bg-white dark:text-zinc-300 dark:hover:bg-zinc-700">×</button></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="discount-usage-heading">
                    <h2 id="discount-usage-heading" class="text-base font-semibold">Usage limits</h2>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="discount-usage-limit" class="mb-1.5 block text-sm font-medium">Total usage limit</label>
                            <input id="discount-usage-limit" type="number" min="1" step="1" wire:model="usageLimit" placeholder="Unlimited" class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            @error('usageLimit')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <input type="checkbox" wire:model="onePerCustomer" class="mt-1 size-4 rounded border-zinc-300 accent-blue-600">
                            <span><span class="block text-sm font-medium">Limit to one use per customer</span><span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">This rule is saved with the discount for checkout eligibility.</span></span>
                        </label>
                    </div>
                </section>

                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="discount-dates-heading">
                    <h2 id="discount-dates-heading" class="text-base font-semibold">Active dates</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="discount-starts-at" class="mb-1.5 block text-sm font-medium">Start date</label>
                            <input id="discount-starts-at" type="datetime-local" wire:model="startsAt" class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            @error('startsAt')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="discount-ends-at" class="mb-1.5 block text-sm font-medium">End date <span class="font-normal text-zinc-500">(optional)</span></label>
                            <input id="discount-ends-at" type="datetime-local" wire:model="endsAt" class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            @error('endsAt')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </section>
            </div>

            <aside class="grid content-start gap-6">
                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="discount-status-heading">
                    <h2 id="discount-status-heading" class="text-base font-semibold">Status</h2>
                    <label for="discount-active" class="mt-4 flex cursor-pointer items-center gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <input id="discount-active" type="checkbox" wire:model="isActive" class="size-4 rounded border-zinc-300 accent-blue-600">
                        <span><span class="block text-sm font-medium">Active</span><span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">Scheduled and expired dates are enforced at checkout.</span></span>
                    </label>
                    @error('isActive')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </section>
                <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold">How it works</h2>
                    <p class="mt-2 text-sm leading-6 text-zinc-500 dark:text-zinc-400">Discount values and purchase minimums use minor currency units. Product and collection rules limit which cart lines qualify.</p>
                </section>
            </aside>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-zinc-200 bg-white/95 px-4 py-3 backdrop-blur sm:px-6 lg:left-64 lg:px-8 dark:border-zinc-800 dark:bg-zinc-950/95">
            <div class="mx-auto flex max-w-[1600px] items-center justify-between gap-3">
                <a href="/admin/discounts" wire:navigate class="inline-flex min-h-11 items-center rounded-xl px-4 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">Discard</a>
                <button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"><span wire:loading.remove wire:target="save">{{ $isEditing ? 'Save discount' : 'Create discount' }}</span><span wire:loading wire:target="save">Saving…</span></button>
            </div>
        </div>
    </form>
</div>
