<div>
    <x-slot:title>{{ $collection ? 'Edit Collection' : 'Create Collection' }}</x-slot:title>

    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $collection ? 'Edit Collection' : 'Create Collection' }}</flux:heading>
        <flux:button href="{{ route('admin.collections.index') }}" variant="ghost" wire:navigate>Back to Collections</flux:button>
    </div>

    <form wire:submit="save" class="mt-6 space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="title" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="description" rows="4" />
                </flux:field>

                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model="status">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="archived">Archived</option>
                    </flux:select>
                </flux:field>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <flux:button href="{{ route('admin.collections.index') }}" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">
                {{ $collection ? 'Update Collection' : 'Create Collection' }}
            </flux:button>
        </div>
    </form>
</div>
