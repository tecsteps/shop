<section class="space-y-6 pb-24">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ $isEditing ? 'Edit discount' : 'Create discount' }}</flux:heading>
            <flux:text class="mt-1">Configure the promotion type, value, eligibility, limits, and dates.</flux:text>
        </div>

        <flux:button :href="route('admin.discounts.index')" wire:navigate variant="filled" icon="arrow-left">
            Discounts
        </flux:button>
    </div>

    @if (session('status'))
        <flux:callout color="green" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:radio.group wire:model.live="type" label="Type" class="grid gap-3 sm:grid-cols-2">
                <flux:radio value="code" label="Discount code" description="Customers enter a code at checkout" />
                <flux:radio value="automatic" label="Automatic discount" description="Applied automatically at checkout" />
            </flux:radio.group>
            <flux:error name="type" />
        </div>

        @if ($type === 'code')
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Code</flux:heading>
                <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                    <div class="flex-1">
                        <flux:input wire:model="code" label="Discount code" placeholder="SUMMER20" />
                        <flux:error name="code" />
                    </div>
                    <div class="flex items-end">
                        <flux:button type="button" wire:click="generateCode" variant="filled">Generate</flux:button>
                    </div>
                </div>
            </div>
        @endif

        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Value</flux:heading>

            <div class="mt-4 grid gap-5 lg:grid-cols-[1fr_220px]">
                <flux:radio.group wire:model.live="valueType" label="Value type" class="grid gap-3 sm:grid-cols-3">
                    <flux:radio value="percent" label="Percentage" />
                    <flux:radio value="fixed" label="Fixed amount" />
                    <flux:radio value="free_shipping" label="Free shipping" />
                </flux:radio.group>

                @if ($valueType !== 'free_shipping')
                    <div>
                        <flux:input wire:model="valueAmount" type="number" step="0.01" min="0" label="{{ $valueType === 'percent' ? 'Percentage' : 'Amount' }}" />
                        <flux:error name="valueAmount" />
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Conditions</flux:heading>

            <div class="mt-4 space-y-6">
                <div class="max-w-xs">
                    <flux:input wire:model="minimumPurchaseAmount" type="number" step="0.01" min="0" label="Minimum purchase amount" description="Leave empty for no minimum" placeholder="0.00" />
                    <flux:error name="minimumPurchaseAmount" />
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-3">
                        <flux:input wire:model.live.debounce.300ms="productSearch" icon="magnifying-glass" label="Specific products" placeholder="Search products..." />

                        @if ($productResults->isNotEmpty())
                            <div class="max-h-48 overflow-y-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                                @foreach ($productResults as $product)
                                    <button type="button" wire:click="addProduct({{ $product->getKey() }})" wire:key="discount-product-result-{{ $product->getKey() }}" class="block w-full px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                        {{ $product->title }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <div class="space-y-2">
                            @foreach ($selectedProducts as $product)
                                <div wire:key="discount-selected-product-{{ $product->getKey() }}" class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                                    <span class="truncate">{{ $product->title }}</span>
                                    <flux:button type="button" wire:click="removeProduct({{ $product->getKey() }})" size="sm" variant="ghost" icon="x-mark" aria-label="Remove {{ $product->title }}" />
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-3">
                        <flux:input wire:model.live.debounce.300ms="collectionSearch" icon="magnifying-glass" label="Specific collections" placeholder="Search collections..." />

                        @if ($collectionResults->isNotEmpty())
                            <div class="max-h-48 overflow-y-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                                @foreach ($collectionResults as $collection)
                                    <button type="button" wire:click="addCollection({{ $collection->getKey() }})" wire:key="discount-collection-result-{{ $collection->getKey() }}" class="block w-full px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                        {{ $collection->title }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <div class="space-y-2">
                            @foreach ($selectedCollections as $collection)
                                <div wire:key="discount-selected-collection-{{ $collection->getKey() }}" class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                                    <span class="truncate">{{ $collection->title }}</span>
                                    <flux:button type="button" wire:click="removeCollection({{ $collection->getKey() }})" size="sm" variant="ghost" icon="x-mark" aria-label="Remove {{ $collection->title }}" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Usage limits</flux:heading>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="usageLimit" type="number" min="1" label="Total usage limit" placeholder="Unlimited" />
                <div class="flex items-end">
                    <flux:checkbox wire:model="onePerCustomer" label="Limit to one use per customer" />
                </div>
            </div>
            <flux:error name="usageLimit" />
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Active dates</flux:heading>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="startsAt" type="datetime-local" label="Start date" />
                <flux:input wire:model="endsAt" type="datetime-local" label="End date" description="Leave empty for no end date" />
            </div>
            <div class="mt-2 grid gap-1 sm:grid-cols-2">
                <flux:error name="startsAt" />
                <flux:error name="endsAt" />
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:switch wire:model.live="isActive" label="{{ $isActive ? 'Active' : 'Disabled' }}" align="left" />
        </div>

        <div class="fixed bottom-0 left-0 right-0 z-40 border-t border-zinc-200 bg-white/95 px-4 py-3 backdrop-blur dark:border-zinc-700 dark:bg-zinc-950/95 lg:left-64">
            <div class="mx-auto flex max-w-7xl justify-end gap-3">
                <flux:button :href="route('admin.discounts.index')" wire:navigate variant="ghost">Discard</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" data-test="discount-save-button">
                    <span wire:loading.remove>Save</span>
                    <span wire:loading>Saving...</span>
                </flux:button>
            </div>
        </div>
    </form>
</section>
