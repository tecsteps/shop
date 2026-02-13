<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $mode === 'edit' ? 'Edit Collection' : 'Create Collection' }}</flux:heading>
        <a href="{{ route('admin.collections.index') }}">
            <flux:button variant="ghost">Back to Collections</flux:button>
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="space-y-4">
                <flux:input wire:model="title" label="Title" required />
                <flux:textarea wire:model="descriptionHtml" label="Description" rows="4" />
                <flux:select wire:model="status" label="Status">
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="archived">Archived</option>
                </flux:select>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">Products</flux:heading>
            <div class="space-y-2">
                @foreach ($availableProducts as $product)
                    <label wire:key="product-pick-{{ $product->id }}" class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="selectedProducts" value="{{ $product->id }}" class="rounded" />
                        {{ $product->title }}
                    </label>
                @endforeach
            </div>
        </div>

        <flux:button type="submit" variant="primary">
            {{ $mode === 'edit' ? 'Update Collection' : 'Create Collection' }}
        </flux:button>
    </form>
</div>
