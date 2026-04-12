<div class="space-y-6 p-6">
    <flux:heading size="xl">Settings</flux:heading>

    @if (session('status'))
        <div class="rounded border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex flex-wrap gap-2 border-b border-zinc-200 pb-2 dark:border-zinc-700">
        <flux:button size="sm" variant="primary">General</flux:button>
        <flux:button size="sm" variant="ghost" :href="route('admin.settings.shipping')" wire:navigate>Shipping</flux:button>
        <flux:button size="sm" variant="ghost" :href="route('admin.settings.taxes')" wire:navigate>Taxes</flux:button>
    </div>

    <form wire:submit="save" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:field>
            <flux:label>Store name</flux:label>
            <flux:input wire:model="name" />
            <flux:error name="name" />
        </flux:field>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <flux:field>
                <flux:label>Currency</flux:label>
                <flux:input wire:model="defaultCurrency" maxlength="3" />
                <flux:error name="defaultCurrency" />
            </flux:field>
            <flux:field>
                <flux:label>Locale</flux:label>
                <flux:input wire:model="defaultLocale" />
                <flux:error name="defaultLocale" />
            </flux:field>
            <flux:field>
                <flux:label>Timezone</flux:label>
                <flux:input wire:model="timezone" />
                <flux:error name="timezone" />
            </flux:field>
        </div>
        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save settings</flux:button>
        </div>
    </form>

    <div class="rounded-lg border border-zinc-200 bg-white p-5 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Notifications</flux:heading>
        <p class="mt-2">Notification channel configuration is coming soon.</p>
    </div>
</div>
