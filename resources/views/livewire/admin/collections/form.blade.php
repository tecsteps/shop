<div class="mx-auto max-w-3xl">
    <flux:heading size="xl" class="mb-6">{{ $collection ? 'Edit collection' : 'New collection' }}</flux:heading>
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" class="mb-4" heading="{{ session('success') }}"></flux:callout>
    @endif
    <form wire:submit="save" class="flex flex-col gap-4">
        <div class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
            <div class="flex flex-col gap-4">
                <flux:input label="Title" wire:model="title" required />
                <flux:input label="Handle" wire:model="handle" placeholder="Auto-generated if empty" />
                <flux:textarea label="Description" wire:model="descriptionHtml" rows="4" />
                <flux:select label="Status" wire:model="status">
                    <flux:select.option value="draft">Draft</flux:select.option>
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="archived">Archived</flux:select.option>
                </flux:select>
            </div>
        </div>
        <div class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
            <flux:heading size="lg" class="mb-3">Products</flux:heading>
            <div class="max-h-80 space-y-1 overflow-y-auto">
                @foreach ($availableProducts as $p)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="productIds" value="{{ $p->id }}" class="rounded">
                        <span>{{ $p->title }}</span>
                        <span class="text-xs text-zinc-500">{{ $p->handle }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="flex justify-end gap-3">
            <flux:button type="button" variant="ghost" href="{{ route('admin.collections.index') }}" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
