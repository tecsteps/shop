<div>
    <x-slot:title>Settings</x-slot:title>

    <flux:heading size="xl">Settings</flux:heading>

    <div class="mt-6 flex gap-4 border-b border-gray-200 dark:border-gray-700">
        <a href="{{ route('admin.settings.index') }}" class="border-b-2 border-blue-600 px-4 py-2 text-sm font-medium text-blue-600">General</a>
        <a href="{{ route('admin.settings.shipping') }}" wire:navigate class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Shipping</a>
        <a href="{{ route('admin.settings.taxes') }}" wire:navigate class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Taxes</a>
    </div>

    <form wire:submit="save" class="mt-6 space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <flux:heading size="lg">General Settings</flux:heading>
            <div class="mt-4 space-y-4">
                <flux:field>
                    <flux:label>Store Name</flux:label>
                    <flux:input wire:model="storeName" />
                    <flux:error name="storeName" />
                </flux:field>

                <flux:field>
                    <flux:label>Currency</flux:label>
                    <flux:input wire:model="currency" maxlength="3" />
                    <flux:error name="currency" />
                </flux:field>

                <flux:field>
                    <flux:label>Timezone</flux:label>
                    <flux:input wire:model="timezone" />
                    <flux:error name="timezone" />
                </flux:field>
            </div>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save Settings</flux:button>
        </div>
    </form>
</div>
