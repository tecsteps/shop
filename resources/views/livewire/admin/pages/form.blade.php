<div class="mx-auto max-w-3xl">
    <flux:heading size="xl" class="mb-6">{{ $page ? 'Edit page' : 'New page' }}</flux:heading>
    @if (session('success'))
        <flux:callout variant="success" heading="{{ session('success') }}" class="mb-4"></flux:callout>
    @endif
    <form wire:submit="save" class="flex flex-col gap-4 rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
        <flux:input label="Title" wire:model="title" required />
        <flux:input label="Handle" wire:model="handle" placeholder="Auto-generated if empty" />
        <flux:textarea label="Body (HTML)" wire:model="bodyHtml" rows="10" />
        <flux:select label="Status" wire:model="status">
            <flux:select.option value="draft">Draft</flux:select.option>
            <flux:select.option value="published">Published</flux:select.option>
            <flux:select.option value="archived">Archived</flux:select.option>
        </flux:select>
        <div class="flex justify-end gap-3">
            <flux:button type="button" variant="ghost" href="{{ route('admin.pages.index') }}" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
