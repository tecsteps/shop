<div>
    <x-slot:title>Tax Settings</x-slot:title>

    <flux:heading size="xl">Settings</flux:heading>

    <div class="mt-6 flex gap-4 border-b border-gray-200 dark:border-gray-700">
        <a href="{{ route('admin.settings.index') }}" wire:navigate class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">General</a>
        <a href="{{ route('admin.settings.shipping') }}" wire:navigate class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Shipping</a>
        <a href="{{ route('admin.settings.taxes') }}" class="border-b-2 border-blue-600 px-4 py-2 text-sm font-medium text-blue-600">Taxes</a>
    </div>

    <form wire:submit="save" class="mt-6 space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <flux:heading size="lg">Tax Configuration</flux:heading>
            <div class="mt-4 space-y-4">
                <flux:field>
                    <flux:label>Tax Mode</flux:label>
                    <flux:select wire:model="taxMode">
                        <option value="manual">Manual</option>
                        <option value="provider">Provider</option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Tax Rate (percent, e.g. 1900 = 19%)</flux:label>
                    <flux:input wire:model="taxRate" type="number" min="0" max="10000" />
                </flux:field>

                <flux:checkbox wire:model="pricesIncludeTax" label="Prices include tax" />
            </div>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save Tax Settings</flux:button>
        </div>
    </form>
</div>
