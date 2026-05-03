<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl">{{ \Illuminate\Support\Str::headline($installation) }}</flux:heading>
            <flux:text>Scopes, webhooks, and installation state.</flux:text>
        </div>

        <flux:button :href="route('admin.apps.index')" wire:navigate>Back to apps</flux:button>
    </div>

    <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-4 sm:grid-cols-3">
            <div><div class="text-sm text-zinc-500">Status</div><div class="font-medium">Available</div></div>
            <div><div class="text-sm text-zinc-500">Scopes</div><div class="font-medium">Read products, read orders</div></div>
            <div><div class="text-sm text-zinc-500">Webhooks</div><div class="font-medium">Not configured</div></div>
        </div>
    </section>
</div>
