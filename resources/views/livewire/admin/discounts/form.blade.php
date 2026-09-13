<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.discounts.index') }}" wire:navigate>Discounts</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $isEditing ? ($this->discount->code ?? 'Automatic') : 'Create discount' }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">{{ $isEditing ? 'Edit discount' : 'Create discount' }}</flux:heading>

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:radio.group wire:model.live="type" label="Type">
                <flux:radio value="code" label="Discount code" description="Customers enter a code at checkout" />
                <flux:radio value="automatic" label="Automatic discount" description="Applied automatically at checkout" />
            </flux:radio.group>
        </div>

        @if ($this->type === 'code')
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:field>
                    <flux:label>Discount code</flux:label>
                    <div class="flex gap-2">
                        <flux:input wire:model="code" placeholder="SUMMER20" class="flex-1" />
                        <flux:button variant="ghost" wire:click="generateCode">Generate</flux:button>
                    </div>
                    <flux:error name="code" />
                </flux:field>
            </div>
        @endif

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:radio.group wire:model.live="valueType" label="Value">
                <flux:radio value="percent" label="Percentage" />
                <flux:radio value="fixed" label="Fixed amount" />
                <flux:radio value="free_shipping" label="Free shipping" />
            </flux:radio.group>
            @if ($this->valueType !== 'free_shipping')
                <flux:field class="mt-4">
                    <flux:label>{{ $this->valueType === 'percent' ? 'Percentage' : 'Amount' }}</flux:label>
                    <flux:input wire:model="valueAmount" type="number" step="0.01" min="0" />
                    <flux:error name="valueAmount" />
                </flux:field>
            @endif
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">Conditions</flux:heading>
            <flux:field class="mt-4">
                <flux:label>Minimum purchase amount</flux:label>
                <flux:input wire:model="minimumPurchaseAmount" type="number" step="0.01" min="0" placeholder="0.00" />
                <flux:description>Leave empty for no minimum</flux:description>
            </flux:field>

            <p class="mt-4 text-sm font-medium">Specific products</p>
            <flux:input wire:model.live.debounce.300ms="productSearch" placeholder="Search products..." aria-label="Search products" class="mt-2" />
            @if ($this->productSearch !== '')
                <div class="mt-2 max-h-48 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    @forelse ($productResults as $result)
                        <div class="flex items-center justify-between gap-2 border-b border-zinc-100 px-3 py-2 last:border-0 dark:border-zinc-800" wire:key="product-result-{{ $result->getKey() }}">
                            <span class="text-sm">{{ $result->title }}</span>
                            <flux:button variant="ghost" size="sm" wire:click="addProduct({{ $result->getKey() }})">Add</flux:button>
                        </div>
                    @empty
                        <p class="px-3 py-4 text-sm text-zinc-500">No products found.</p>
                    @endforelse
                </div>
            @endif
            @if ($selectedProducts->isNotEmpty())
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($selectedProducts as $product)
                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-xs dark:bg-zinc-800" wire:key="selected-product-{{ $product->getKey() }}">
                            {{ $product->title }}
                            <button type="button" wire:click="removeProduct({{ $product->getKey() }})" aria-label="Remove {{ $product->title }}">x</button>
                        </span>
                    @endforeach
                </div>
            @endif

            <p class="mt-4 text-sm font-medium">Specific collections</p>
            <flux:input wire:model.live.debounce.300ms="collectionSearch" placeholder="Search collections..." aria-label="Search collections" class="mt-2" />
            @if ($this->collectionSearch !== '')
                <div class="mt-2 max-h-48 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    @forelse ($collectionResults as $result)
                        <div class="flex items-center justify-between gap-2 border-b border-zinc-100 px-3 py-2 last:border-0 dark:border-zinc-800" wire:key="collection-result-{{ $result->getKey() }}">
                            <span class="text-sm">{{ $result->title }}</span>
                            <flux:button variant="ghost" size="sm" wire:click="addCollection({{ $result->getKey() }})">Add</flux:button>
                        </div>
                    @empty
                        <p class="px-3 py-4 text-sm text-zinc-500">No collections found.</p>
                    @endforelse
                </div>
            @endif
            @if ($selectedCollections->isNotEmpty())
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($selectedCollections as $collection)
                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-xs dark:bg-zinc-800" wire:key="selected-collection-{{ $collection->getKey() }}">
                            {{ $collection->title }}
                            <button type="button" wire:click="removeCollection({{ $collection->getKey() }})" aria-label="Remove {{ $collection->title }}">x</button>
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">Usage limits</flux:heading>
            <flux:input wire:model="usageLimit" label="Total usage limit" type="number" min="1" placeholder="Unlimited" class="mt-4" />
            <label class="mt-3 flex items-center gap-2 text-sm">
                <flux:checkbox wire:model="onePerCustomer" />
                <span>Limit to one use per customer</span>
            </label>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">Active dates</flux:heading>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <flux:input wire:model="startsAt" label="Start date" type="datetime-local" />
                <flux:field>
                    <flux:label>End date</flux:label>
                    <flux:input wire:model="endsAt" type="datetime-local" />
                    <flux:description>Leave empty for no end date</flux:description>
                </flux:field>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:switch wire:model.live="isActive" label="{{ $this->isActive ? 'Active' : 'Disabled' }}" />
        </div>
    </div>

    <div class="sticky bottom-0 z-10 mt-6 flex justify-end gap-2 border-t border-zinc-200 bg-zinc-100 py-4 dark:border-zinc-700 dark:bg-zinc-950">
        <flux:button variant="ghost" href="{{ route('admin.discounts.index') }}" wire:navigate>Discard</flux:button>
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Save</span>
            <span wire:loading wire:target="save">Saving...</span>
        </flux:button>
    </div>
</div>
