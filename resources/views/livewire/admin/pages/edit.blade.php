<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $pageId ? 'Edit page' : 'Add page' }}</flux:heading>
        <flux:button variant="ghost" href="{{ url('/admin/pages') }}">Back</flux:button>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="space-y-4 rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="title" />
                    <flux:error name="title" />
                </flux:field>
                <flux:field>
                    <flux:label>Handle</flux:label>
                    <flux:input wire:model="handle" placeholder="auto-generated from title" />
                </flux:field>
                <flux:field>
                    <flux:label>Body (HTML)</flux:label>
                    <flux:textarea wire:model="bodyHtml" rows="12" />
                </flux:field>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model="status">
                        <flux:select.option value="draft">Draft</flux:select.option>
                        <flux:select.option value="published">Published</flux:select.option>
                        <flux:select.option value="archived">Archived</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>
            <flux:button type="submit" variant="primary" class="w-full">Save page</flux:button>
        </div>
    </form>
</div>
