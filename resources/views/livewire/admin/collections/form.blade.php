<div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $mode === 'create' ? 'New collection' : 'Edit collection' }}</flux:heading>
        <flux:button :href="route('admin.collections.index')" variant="ghost" wire:navigate>Back</flux:button>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="title" />
                    <flux:error name="title" />
                </flux:field>
                <div class="mt-4">
                    <flux:field>
                        <flux:label>Handle</flux:label>
                        <flux:input wire:model="handle" />
                        <flux:error name="handle" />
                    </flux:field>
                </div>
                <div class="mt-4">
                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="description" rows="4" />
                    </flux:field>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Products</flux:heading>
                <div class="mt-4">
                    <flux:input wire:model.live.debounce.300ms="productSearch" placeholder="Search products..." icon="magnifying-glass" />
                    @if ($searchResults->isNotEmpty())
                        <div class="mt-2 rounded border border-zinc-200 dark:border-zinc-700">
                            @foreach ($searchResults as $product)
                                <div class="flex items-center justify-between p-2 text-sm">
                                    <span>{{ $product->title }}</span>
                                    <flux:button size="sm" wire:click="addProduct({{ $product->id }})">Add</flux:button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="mt-4">
                    @if ($assignedProducts->isEmpty())
                        <p class="text-sm text-zinc-500">No products assigned.</p>
                    @else
                        <ul class="space-y-2">
                            @foreach ($assignedProducts as $product)
                                <li class="flex items-center justify-between rounded border border-zinc-200 p-2 text-sm dark:border-zinc-700">
                                    <span>{{ $product->title }}</span>
                                    <flux:button size="sm" variant="ghost" wire:click="removeProduct({{ $product->id }})">Remove</flux:button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Settings</flux:heading>
                <div class="mt-4 space-y-4">
                    <flux:field>
                        <flux:label>Type</flux:label>
                        <flux:select wire:model="type">
                            <flux:select.option value="manual">Manual</flux:select.option>
                            <flux:select.option value="smart">Smart</flux:select.option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="draft">Draft</flux:select.option>
                            <flux:select.option value="active">Active</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 lg:col-span-3">
            <flux:button variant="ghost" :href="route('admin.collections.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save collection</flux:button>
        </div>
    </form>
</div>
