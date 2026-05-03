<form wire:submit="save" class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl">{{ $collection ? 'Edit collection' : 'Create collection' }}</flux:heading>
            <flux:text>{{ $title ?: 'Collection details' }}</flux:text>
        </div>

        <div class="flex gap-2">
            <flux:button :href="route('admin.collections.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save collection</flux:button>
        </div>
    </div>

    <section class="max-w-3xl rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-4">
            <flux:input wire:model="title" label="Title" required />
            <flux:input wire:model="handle" label="Handle" />
            <flux:textarea wire:model="descriptionHtml" label="Description" rows="8" />
            <flux:select wire:model="status" label="Status">
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</option>
                @endforeach
            </flux:select>
        </div>
    </section>
</form>
