<div>
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.pages.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">&larr; Pages</a>
    </div>

    <flux:heading size="xl" class="mt-4">{{ $isEdit ? 'Edit Page' : 'New Page' }}</flux:heading>

    <form wire:submit="save" class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Main content --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:input wire:model="title" label="Title" required />
                <div class="mt-4">
                    <flux:textarea wire:model="body_html" label="Content" rows="12" />
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:select wire:model="status" label="Status">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
                </flux:select>
            </div>

            <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">{{ $isEdit ? 'Save changes' : 'Create page' }}</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </form>
</div>
