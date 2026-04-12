<div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $mode === 'create' ? 'New page' : 'Edit page' }}</flux:heading>
        <flux:button :href="route('admin.pages.index')" variant="ghost" wire:navigate>Back</flux:button>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="title" />
                    <flux:error name="title" />
                </flux:field>
                <div class="mt-4">
                    <flux:field>
                        <flux:label>Handle</flux:label>
                        <flux:input wire:model="handle" placeholder="about-us" />
                        <flux:error name="handle" />
                    </flux:field>
                </div>
                <div class="mt-4">
                    <flux:field>
                        <flux:label>Body</flux:label>
                        <flux:textarea wire:model="bodyHtml" rows="12" placeholder="<p>Page content (HTML)</p>" />
                        <flux:error name="bodyHtml" />
                    </flux:field>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Settings</flux:heading>
                <div class="mt-4 space-y-4">
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="draft">Draft</flux:select.option>
                            <flux:select.option value="published">Published</flux:select.option>
                            <flux:select.option value="archived">Archived</flux:select.option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Publish date</flux:label>
                        <flux:input type="date" wire:model="publishedAt" />
                    </flux:field>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 lg:col-span-3">
            <flux:button variant="ghost" :href="route('admin.pages.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save page</flux:button>
        </div>
    </form>
</div>
