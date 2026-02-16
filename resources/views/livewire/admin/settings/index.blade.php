<div>
    <form wire:submit="save" class="space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-4 font-semibold text-gray-900 dark:text-white">General</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="store_name" label="Store name" required />
                <flux:input wire:model="store_email" label="Store email" type="email" />
                <flux:input wire:model="timezone" label="Timezone" />
                <flux:input wire:model="currency" label="Currency" />
                <flux:input wire:model="weight_unit" label="Weight unit" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">Save settings</flux:button>
            <a href="{{ route('admin.settings.shipping') }}" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Shipping settings</a>
            <a href="{{ route('admin.settings.taxes') }}" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Tax settings</a>
        </div>
    </form>
</div>
