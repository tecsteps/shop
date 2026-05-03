<form wire:submit="save" class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl">Settings</flux:heading>
            <flux:text>Store identity, locale, domains, shipping, and tax controls.</flux:text>
        </div>

        <flux:button type="submit" variant="primary">Save settings</flux:button>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="grid gap-4">
                <flux:input wire:model="name" label="Store name" />
                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model="defaultCurrency" label="Currency" maxlength="3" />
                    <flux:input wire:model="defaultLocale" label="Locale" />
                    <flux:input wire:model="timezone" label="Timezone" />
                </div>
                <flux:select wire:model="status" label="Status">
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</option>
                    @endforeach
                </flux:select>
            </div>
        </section>

        <aside class="space-y-6">
            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg">Store domains</flux:heading>
                <div class="mt-4 space-y-3 text-sm">
                    @foreach ($domains as $domain)
                        <div wire:key="admin-domain-{{ $domain->id }}" class="rounded-md border border-zinc-200 p-3 dark:border-zinc-800">
                            <div class="font-medium">{{ $domain->hostname }}</div>
                            <div class="text-zinc-500">{{ $domain->type->value }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="grid gap-3">
                <flux:button :href="route('admin.settings.shipping')" wire:navigate>Shipping settings</flux:button>
                <flux:button :href="route('admin.settings.taxes')" wire:navigate>Tax settings</flux:button>
            </div>
        </aside>
    </div>
</form>
