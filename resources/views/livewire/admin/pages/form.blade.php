<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ $this->isEditing ? $title : 'Add page' }}</flux:heading>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>Title</flux:label>
                            <flux:input wire:model="title" placeholder="About Us" />
                            <flux:error name="title" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Handle</flux:label>
                            <flux:input wire:model="handle" placeholder="about-us" />
                            <flux:error name="handle" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Content</flux:label>
                            <flux:textarea wire:model="bodyHtml" rows="12" placeholder="Write your page content..." />
                        </flux:field>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="draft">Draft</flux:select.option>
                            <flux:select.option value="published">Published</flux:select.option>
                            <flux:select.option value="archived">Archived</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>
            </div>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white px-6 py-3 dark:border-zinc-700 dark:bg-zinc-800 lg:left-64">
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" href="{{ route('admin.pages.index') }}" wire:navigate>Discard</flux:button>
                <flux:button variant="primary" type="submit">Save</flux:button>
            </div>
        </div>
    </form>
</div>
