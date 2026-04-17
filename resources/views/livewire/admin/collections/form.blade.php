<div>
    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('admin.collections.index') }}" wire:navigate>Collections</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $collection ? $title : 'Add collection' }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <flux:heading size="xl" class="mb-6">{{ $collection ? $title : 'Add collection' }}</flux:heading>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Title</flux:label>
                        <flux:input wire:model.live.debounce.500ms="title" placeholder="Summer Collection" />
                        <flux:error name="title" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Handle</flux:label>
                        <flux:input wire:model="handle" placeholder="summer-collection" />
                        <flux:error name="handle" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="descriptionHtml" rows="4" />
                    </flux:field>
                </div>
            </div>

            {{-- Products --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:heading size="md" class="mb-4">Products</flux:heading>

                <flux:input wire:model.live.debounce.300ms="productSearch" placeholder="Search products..." icon="magnifying-glass" class="mb-3" />

                @if ($this->searchResults->isNotEmpty())
                    <div class="mb-4 max-h-48 overflow-y-auto rounded border border-gray-200 dark:border-gray-700">
                        @foreach ($this->searchResults as $product)
                            <div class="flex items-center justify-between px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800" wire:key="sr-{{ $product->id }}">
                                <span class="text-sm">{{ $product->title }}</span>
                                <flux:button variant="ghost" size="sm" wire:click="addProduct({{ $product->id }})">Add</flux:button>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($this->assignedProducts->isNotEmpty())
                    <div class="space-y-2">
                        @foreach ($this->assignedProducts as $product)
                            <div class="flex items-center justify-between rounded border border-gray-200 px-3 py-2 dark:border-gray-700" wire:key="ap-{{ $product->id }}">
                                <span class="text-sm font-medium">{{ $product->title }}</span>
                                <flux:button variant="ghost" size="sm" wire:click="removeProduct({{ $product->id }})">
                                    <flux:icon name="x-mark" variant="mini" class="h-4 w-4 text-red-500" />
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model="status">
                        <option value="active">Active</option>
                        <option value="archived">Archived</option>
                    </flux:select>
                </flux:field>
            </div>
        </div>
    </div>

    <div class="fixed bottom-0 left-0 right-0 z-30 border-t border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900 lg:left-64">
        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('admin.collections.index') }}" wire:navigate>Discard</flux:button>
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </div>
</div>
