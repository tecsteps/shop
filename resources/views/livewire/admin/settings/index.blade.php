<div>
    <div class="mb-6">
        <flux:heading size="xl">Settings</flux:heading>
    </div>

    {{-- Settings tabs --}}
    <div class="mb-6 flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
        <a href="{{ route('admin.settings.index') }}" class="border-b-2 border-zinc-900 px-4 py-2 text-sm font-medium text-zinc-900 dark:border-white dark:text-white">General</a>
        <a href="{{ route('admin.settings.shipping') }}" wire:navigate class="border-b-2 border-transparent px-4 py-2 text-sm font-medium text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300">Shipping</a>
        <a href="{{ route('admin.settings.taxes') }}" wire:navigate class="border-b-2 border-transparent px-4 py-2 text-sm font-medium text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300">Taxes</a>
    </div>

    <form wire:submit="save" class="space-y-8">
        {{-- Store details --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div>
                <flux:heading size="md">Store details</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">Basic information about your store.</flux:text>
            </div>
            <div class="space-y-4 lg:col-span-2">
                <flux:field>
                    <flux:label>Store name</flux:label>
                    <flux:input wire:model="storeName" />
                    <flux:error name="storeName" />
                </flux:field>
                <flux:field>
                    <flux:label>Store handle</flux:label>
                    <flux:input wire:model="storeHandle" disabled />
                    <flux:description>The store handle cannot be changed after creation.</flux:description>
                </flux:field>
            </div>
        </div>

        <flux:separator />

        {{-- Defaults --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div>
                <flux:heading size="md">Defaults</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">Currency, language, and timezone settings.</flux:text>
            </div>
            <div class="space-y-4 lg:col-span-2">
                <flux:field>
                    <flux:label>Default currency</flux:label>
                    <flux:select wire:model="defaultCurrency">
                        <flux:select.option value="USD">USD - US Dollar</flux:select.option>
                        <flux:select.option value="EUR">EUR - Euro</flux:select.option>
                        <flux:select.option value="GBP">GBP - British Pound</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>Default locale</flux:label>
                    <flux:select wire:model="defaultLocale">
                        <flux:select.option value="en">English</flux:select.option>
                        <flux:select.option value="de">German</flux:select.option>
                        <flux:select.option value="fr">French</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>Timezone</flux:label>
                    <flux:select wire:model="timezone">
                        @foreach (timezone_identifiers_list() as $tz)
                            <flux:select.option value="{{ $tz }}">{{ $tz }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>
        </div>

        <div class="flex justify-end">
            <flux:button variant="primary" type="submit">Save</flux:button>
        </div>
    </form>
</div>
