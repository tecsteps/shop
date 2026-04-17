<div class="space-y-6">
    <flux:heading size="xl">Taxes</flux:heading>

    <div class="flex flex-wrap gap-2 border-b border-neutral-200 pb-2 dark:border-neutral-800">
        <a href="{{ url('/admin/settings') }}" class="rounded-md px-3 py-1 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800">General</a>
        <a href="{{ url('/admin/settings/shipping') }}" class="rounded-md px-3 py-1 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800">Shipping</a>
        <a href="{{ url('/admin/settings/taxes') }}" class="rounded-md px-3 py-1 text-sm font-medium bg-neutral-100 dark:bg-neutral-800">Taxes</a>
        <a href="{{ url('/admin/settings/staff') }}" class="rounded-md px-3 py-1 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800">Staff</a>
    </div>

    <form wire:submit="save" class="space-y-4 rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
        <flux:field>
            <flux:label>Tax mode</flux:label>
            <flux:select wire:model="mode">
                @foreach ($modes as $m)
                    <flux:select.option value="{{ $m }}">{{ ucfirst($m) }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>
        <flux:field>
            <flux:label>Provider</flux:label>
            <flux:select wire:model="provider">
                @foreach ($providers as $p)
                    <flux:select.option value="{{ $p }}">{{ str_replace('_', ' ', $p) }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>
        <flux:checkbox wire:model="pricesIncludeTax" label="Prices include tax" />

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
