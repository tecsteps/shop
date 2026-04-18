<div class="space-y-4">
    <flux:heading size="xl">{{ $page && $page->exists ? 'Edit page' : 'New page' }}</flux:heading>
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif
    <form wire:submit="save" class="space-y-3 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
        <flux:input wire:model="title" label="Title" required />
        <flux:input wire:model="handle" label="Handle (optional)" />
        <flux:textarea wire:model="body_html" label="Body" rows="10" />
        <flux:select wire:model="status" label="Status">
            @foreach ($statuses as $case)
                <flux:select.option value="{{ $case->value }}">{{ ucfirst($case->value) }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:button type="submit" variant="primary">Save</flux:button>
    </form>
</div>
