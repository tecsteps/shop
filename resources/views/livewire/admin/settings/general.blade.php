<div class="mx-auto max-w-2xl">
    <flux:heading size="xl" class="mb-6">General settings</flux:heading>
    @if (session('success'))
        <flux:callout variant="success" heading="{{ session('success') }}" class="mb-4"></flux:callout>
    @endif
    <form wire:submit="save" class="flex flex-col gap-4 rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
        <flux:input label="Store name" wire:model="storeName" />
        <div class="grid grid-cols-3 gap-3">
            <flux:input label="Currency" wire:model="defaultCurrency" />
            <flux:input label="Locale" wire:model="defaultLocale" />
            <flux:input label="Timezone" wire:model="timezone" />
        </div>
        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
