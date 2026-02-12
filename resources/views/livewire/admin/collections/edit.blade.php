<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.collections.index') }}" wire:navigate>Collections</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $collection->title }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Edit collection</flux:heading>

    <form wire:submit="save" class="space-y-6 max-w-2xl">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <flux:input wire:model="title" label="Title" required />
            <flux:input wire:model="handle" label="Handle" required />
            <flux:textarea wire:model="descriptionHtml" label="Description" rows="4" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:select wire:model="type" label="Type">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}">{{ ucfirst($type->value) }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="status" label="Status">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}">{{ ucfirst($status->value) }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        {{-- SEO --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <flux:heading size="lg">SEO</flux:heading>
            <flux:input wire:model="seoTitle" label="SEO title" />
            <flux:textarea wire:model="seoDescription" label="SEO description" rows="2" />
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">Save changes</flux:button>
            <flux:button href="{{ route('admin.collections.index') }}" wire:navigate variant="ghost">Cancel</flux:button>
        </div>
    </form>

    {{-- Product management (manual collections only) --}}
    @if ($type === 'manual')
        <div class="mt-8 max-w-2xl">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                <flux:heading size="lg">Products</flux:heading>

                {{-- Search and add products --}}
                <div class="relative">
                    <flux:input wire:model.live.debounce.300ms="productSearch" placeholder="Search products to add..." icon="magnifying-glass" />

                    @if ($availableProducts->count() > 0)
                        <div class="absolute z-10 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                            @foreach ($availableProducts as $product)
                                <button
                                    type="button"
                                    wire:click="addProduct({{ $product->id }})"
                                    class="flex w-full items-center gap-3 px-4 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                >
                                    <span class="text-zinc-900 dark:text-zinc-100">{{ $product->title }}</span>
                                    <flux:badge size="sm" color="zinc" class="ml-auto">{{ ucfirst($product->status->value) }}</flux:badge>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Assigned products --}}
                @if ($assignedProducts->count() > 0)
                    <div class="space-y-2">
                        @foreach ($assignedProducts as $product)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-100 px-4 py-2 dark:border-zinc-800" wire:key="assigned-{{ $product->id }}">
                                <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $product->title }}</span>
                                <flux:button wire:click="removeProduct({{ $product->id }})" size="sm" variant="ghost" icon="x-mark" />
                            </div>
                        @endforeach
                    </div>
                @else
                    <flux:text class="text-zinc-500">No products assigned to this collection.</flux:text>
                @endif
            </div>
        </div>
    @endif
</div>
