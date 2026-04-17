<div>
    <div class="mb-6">
        <flux:heading size="xl" level="1">{{ $this->isEditing ? $page->title : 'Add page' }}</flux:heading>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                    <flux:input wire:model="title" label="Title" placeholder="About Us" />
                    <flux:input wire:model="handle" label="Handle" />
                    <flux:textarea wire:model="bodyHtml" label="Content" rows="12" />
                </div>
            </div>
            <div>
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:select wire:model="status" label="Status">
                        <flux:select.option value="draft">Draft</flux:select.option>
                        <flux:select.option value="published">Published</flux:select.option>
                    </flux:select>
                </div>
            </div>
        </div>

        <div class="sticky bottom-0 mt-6 flex items-center justify-end gap-2 border-t border-zinc-200 bg-white px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:button variant="ghost" href="{{ route('admin.pages.index') }}" wire:navigate>Discard</flux:button>
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
