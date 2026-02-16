<div>
    <form wire:submit="save" class="space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-4 font-semibold text-gray-900 dark:text-white">Tax Settings</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:select wire:model="mode" label="Tax mode">
                    <option value="manual">Manual</option>
                    <option value="provider">Provider</option>
                </flux:select>
                <flux:input wire:model="rate_basis_points" label="Rate (basis points)" type="number" />
                <flux:input wire:model="tax_name" label="Tax name" />
            </div>
            <div class="mt-4 space-y-3">
                <flux:checkbox wire:model="prices_include_tax" label="Prices include tax" />
                <flux:checkbox wire:model="charge_tax_on_shipping" label="Charge tax on shipping" />
            </div>
        </div>
        <flux:button type="submit" variant="primary">Save tax settings</flux:button>
    </form>
</div>
