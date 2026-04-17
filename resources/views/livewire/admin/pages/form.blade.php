<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $mode === 'edit' ? 'Edit Page' : 'Create Page' }}</flux:heading>
        <a href="{{ route('admin.pages.index') }}">
            <flux:button variant="ghost">Back to Pages</flux:button>
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="space-y-4">
                <flux:input wire:model="title" label="Title" required />
                <flux:input wire:model="handle" label="Handle (URL slug)" />
                <flux:textarea wire:model="bodyHtml" label="Body" rows="8" />
                <flux:select wire:model="status" label="Status">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
                </flux:select>
            </div>
        </div>

        <flux:button type="submit" variant="primary">
            {{ $mode === 'edit' ? 'Update Page' : 'Create Page' }}
        </flux:button>
    </form>
</div>
