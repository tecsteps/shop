<div class="mx-auto max-w-2xl">
    <flux:heading size="xl" class="mb-6">{{ $discount ? 'Edit discount' : 'New discount' }}</flux:heading>
    @if (session('success'))
        <flux:callout variant="success" heading="{{ session('success') }}" class="mb-4"></flux:callout>
    @endif
    <form wire:submit="save" class="flex flex-col gap-4 rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
        <flux:input label="Code" wire:model="code" required />
        <div class="grid grid-cols-2 gap-3">
            <flux:select label="Value type" wire:model="valueType">
                <flux:select.option value="percent">Percent</flux:select.option>
                <flux:select.option value="fixed">Fixed (cents)</flux:select.option>
                <flux:select.option value="free_shipping">Free shipping</flux:select.option>
            </flux:select>
            <flux:input type="number" label="Value amount" wire:model="valueAmount" />
        </div>
        <div class="grid grid-cols-2 gap-3">
            <flux:input type="date" label="Starts" wire:model="startsAt" />
            <flux:input type="date" label="Ends" wire:model="endsAt" />
        </div>
        <flux:input type="number" label="Usage limit (blank for unlimited)" wire:model="usageLimit" />
        <flux:select label="Status" wire:model="status">
            <flux:select.option value="draft">Draft</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="disabled">Disabled</flux:select.option>
        </flux:select>
        <div class="flex justify-end gap-3">
            <flux:button type="button" variant="ghost" href="{{ route('admin.discounts.index') }}" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
