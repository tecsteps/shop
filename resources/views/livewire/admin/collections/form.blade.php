<div>
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.collections.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">&larr; Collections</a>
    </div>

    <flux:heading size="xl" class="mt-4">{{ $isEdit ? 'Edit Collection' : 'New Collection' }}</flux:heading>

    <form wire:submit="save" class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Main content --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:input wire:model="title" label="Title" required />
                <div class="mt-4">
                    <flux:textarea wire:model="description_html" label="Description" rows="6" />
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:select wire:model="status" label="Status">
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="archived">Archived</option>
                </flux:select>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:select wire:model="type" label="Type">
                    <option value="manual">Manual</option>
                    <option value="automated">Automated</option>
                </flux:select>
            </div>

            <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">{{ $isEdit ? 'Save changes' : 'Create collection' }}</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </form>
</div>
