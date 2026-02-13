<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $mode === 'edit' ? 'Edit Product' : 'Create Product' }}</flux:heading>
        <a href="{{ route('admin.products.index') }}">
            <flux:button variant="ghost">Back to Products</flux:button>
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="space-y-4">
                <flux:input wire:model="title" label="Title" required />
                <flux:textarea wire:model="descriptionHtml" label="Description" rows="4" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="status" label="Status">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="archived">Archived</option>
                    </flux:select>
                    <flux:input wire:model="vendor" label="Vendor" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="productType" label="Product Type" />
                    <flux:input wire:model="tags" label="Tags (comma separated)" />
                </div>
            </div>
        </div>

        {{-- Options / Variants --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">Options</flux:heading>
                <flux:button wire:click.prevent="addOption" variant="ghost" size="sm">Add Option</flux:button>
            </div>

            @foreach ($options as $index => $option)
                <div wire:key="option-{{ $index }}" class="mb-3 flex items-end gap-3">
                    <div class="flex-1">
                        <flux:input wire:model="options.{{ $index }}.name" label="Option Name" placeholder="Size, Color, etc." />
                    </div>
                    <div class="flex-1">
                        <flux:input wire:model="options.{{ $index }}.values" label="Values (comma separated)" placeholder="S, M, L, XL" />
                    </div>
                    <flux:button wire:click.prevent="removeOption({{ $index }})" variant="ghost" size="sm">Remove</flux:button>
                </div>
            @endforeach
        </div>

        {{-- Media Upload --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">Media</flux:heading>
            <input type="file" wire:model="mediaUploads" multiple accept="image/*" class="text-sm" />
            @error('mediaUploads.*')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary">
                {{ $mode === 'edit' ? 'Update Product' : 'Create Product' }}
            </flux:button>
        </div>
    </form>
</div>
