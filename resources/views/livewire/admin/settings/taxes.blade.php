<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Taxes</flux:heading>
        <flux:button variant="ghost" href="{{ route('admin.settings.index') }}" wire:navigate>Back</flux:button>
    </div>
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif
    <form wire:submit="save" class="space-y-3 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
        <flux:select wire:model="mode" label="Mode">
            @foreach ($modes as $case)
                <flux:select.option value="{{ $case->value }}">{{ ucfirst($case->value) }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:checkbox wire:model="prices_include_tax" label="Prices include tax" />
        <flux:input type="number" wire:model.number="default_rate_bp" label="Default rate (basis points)" />
        <flux:button type="submit" variant="primary">Save</flux:button>
    </form>
</div>
