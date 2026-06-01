<div class="pb-24">
    <x-admin.breadcrumbs :items="[
        ['label' => __('Collections'), 'href' => route('admin.collections.index')],
        ['label' => $this->isEditing ? $title : __('Add collection')],
    ]" />

    <flux:heading size="xl" level="1" class="mb-6">{{ $this->isEditing ? $title : __('Add collection') }}</flux:heading>

    <form wire:submit="save">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-admin.card>
                    <flux:field>
                        <flux:label>{{ __('Title') }}</flux:label>
                        <flux:input wire:model="title" placeholder="{{ __('Summer Collection') }}" data-test="collection-title" />
                        <flux:error name="title" />
                    </flux:field>
                    <flux:field class="mt-4">
                        <flux:label>{{ __('Handle') }}</flux:label>
                        <flux:input wire:model="handle" placeholder="summer-collection" />
                        <flux:error name="handle" />
                    </flux:field>
                    <flux:field class="mt-4">
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea wire:model="descriptionHtml" rows="5" />
                    </flux:field>
                </x-admin.card>

                <x-admin.card title="{{ __('Products') }}">
                    <flux:field>
                        <flux:input wire:model.live.debounce.300ms="productSearch" icon="magnifying-glass" :placeholder="__('Search products...')" />
                    </flux:field>

                    @if ($this->searchResults->isNotEmpty())
                        <ul class="mt-2 max-h-56 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                            @foreach ($this->searchResults as $product)
                                <li class="flex items-center justify-between px-3 py-2 text-sm" wire:key="result-{{ $product->id }}">
                                    <span>{{ $product->title }}</span>
                                    <flux:button size="sm" variant="ghost" wire:click="addProduct({{ $product->id }})">{{ __('Add') }}</flux:button>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($this->assignedProducts->isNotEmpty())
                        <ul class="mt-4 space-y-2" wire:sort="sortProducts">
                            @foreach ($this->assignedProducts as $product)
                                <li class="flex items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700" wire:key="assigned-{{ $product->id }}" wire:sort:item="{{ $product->id }}">
                                    <div wire:sort:handle class="cursor-grab text-zinc-400"><flux:icon.bars-3 class="size-4" /></div>
                                    @php($img = $product->primaryImage())
                                    @if ($img)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($img->storage_key) }}" alt="" class="size-8 rounded object-cover" />
                                    @endif
                                    <span class="flex-1 text-sm">{{ $product->title }}</span>
                                    <div wire:sort:ignore>
                                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="removeProduct({{ $product->id }})" :aria-label="__('Remove')" />
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <flux:text class="mt-4 text-sm">{{ __('No products assigned yet.') }}</flux:text>
                    @endif
                </x-admin.card>
            </div>

            <div class="space-y-6">
                <x-admin.card title="{{ __('Status') }}">
                    <flux:select wire:model="status">
                        <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                        <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
                    </flux:select>
                </x-admin.card>
            </div>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/95 px-4 py-3 backdrop-blur lg:pl-72 dark:border-zinc-700 dark:bg-zinc-900/95">
            <div class="mx-auto flex max-w-5xl items-center justify-end gap-3">
                <flux:button variant="ghost" :href="route('admin.collections.index')" wire:navigate>{{ __('Discard') }}</flux:button>
                <flux:button type="submit" variant="primary" data-test="save-collection">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </form>
</div>
