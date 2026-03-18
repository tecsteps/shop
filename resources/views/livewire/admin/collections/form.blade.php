<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ $this->isEditing ? $title : __('Add collection') }}</flux:heading>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 space-y-4">
                    <flux:field>
                        <flux:label>{{ __('Title') }}</flux:label>
                        <flux:input wire:model="title" />
                        <flux:error name="title" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Handle') }}</flux:label>
                        <flux:input wire:model="handle" />
                        <flux:error name="handle" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea wire:model="descriptionHtml" rows="4" />
                    </flux:field>
                </div>

                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                    <flux:heading size="md" class="mb-4">{{ __('Products') }}</flux:heading>
                    <flux:input wire:model.live.debounce.300ms="productSearch" placeholder="{{ __('Search products...') }}" icon="magnifying-glass" />

                    @if($this->searchResults->count() > 0)
                        <div class="mt-2 border border-zinc-200 dark:border-zinc-700 rounded-lg divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($this->searchResults as $product)
                                <div class="flex items-center justify-between p-3">
                                    <flux:text>{{ $product->title }}</flux:text>
                                    <flux:button size="sm" wire:click="addProduct({{ $product->id }})">{{ __('Add') }}</flux:button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($this->assignedProducts->count() > 0)
                        <div class="mt-4 space-y-2">
                            <flux:text class="font-medium text-zinc-500">{{ __('Assigned products') }}</flux:text>
                            @foreach($this->assignedProducts as $product)
                                <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                                    <flux:text>{{ $product->title }}</flux:text>
                                    <flux:button size="sm" variant="ghost" wire:click="removeProduct({{ $product->id }})" icon="x-mark" />
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                    <flux:field>
                        <flux:label>{{ __('Status') }}</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                            <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>
            </div>
        </div>

        <div class="sticky bottom-0 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700 p-4 mt-6 flex justify-end gap-4">
            <flux:button variant="ghost" :href="route('admin.collections.index')" wire:navigate>{{ __('Discard') }}</flux:button>
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>
