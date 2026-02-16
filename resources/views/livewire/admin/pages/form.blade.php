<div>
    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:input wire:model="title" label="Title" required />
                    <div class="mt-4">
                        <flux:textarea wire:model="content" label="Content" rows="10" />
                    </div>
                </div>
            </div>
            <div class="space-y-6">
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:select wire:model="status" label="Status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </flux:select>
                </div>
                <flux:button type="submit" variant="primary" class="w-full">{{ $page ? 'Update' : 'Create' }} page</flux:button>
            </div>
        </div>
    </form>
</div>
