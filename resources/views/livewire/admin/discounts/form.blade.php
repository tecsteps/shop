<div class="space-y-4">
    <flux:heading size="xl">{{ $discount && $discount->exists ? 'Edit discount' : 'New discount' }}</flux:heading>
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif
    <form wire:submit="save" class="space-y-4 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
        <div class="grid gap-3 sm:grid-cols-2">
            <flux:select wire:model="type" label="Type">
                @foreach ($types as $case)
                    <flux:select.option value="{{ $case->value }}">{{ ucfirst($case->value) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="code" label="Code" />
            <flux:select wire:model="value_type" label="Value type">
                @foreach ($valueTypes as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->value }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input type="number" wire:model.number="value_amount" label="Value amount" />
            <flux:input type="datetime-local" wire:model="starts_at" label="Starts at" />
            <flux:input type="datetime-local" wire:model="ends_at" label="Ends at" />
            <flux:input type="number" wire:model.number="usage_limit" label="Usage limit" />
            <flux:input type="number" wire:model.number="minimum_purchase_amount" label="Minimum purchase (cents)" />
            <flux:select wire:model="status" label="Status">
                @foreach ($statuses as $case)
                    <flux:select.option value="{{ $case->value }}">{{ ucfirst($case->value) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <flux:button type="submit" variant="primary">Save</flux:button>
    </form>
</div>
