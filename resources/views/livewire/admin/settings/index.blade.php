<div>
    <flux:heading size="xl">Settings</flux:heading>
    @if($store)
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:heading size="lg">General</flux:heading>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-gray-500 dark:text-gray-400">Store name</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $store->name }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Handle</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $store->handle }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Currency</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $store->default_currency }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Timezone</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $store->timezone }}</dd></div>
                </dl>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:heading size="lg">Domains</flux:heading>
                <div class="mt-4 space-y-2">
                    @foreach($store->domains as $domain)
                        <div wire:key="domain-{{ $domain->id }}" class="flex items-center justify-between text-sm">
                            <span class="text-gray-900 dark:text-white">{{ $domain->hostname }}</span>
                            @if($domain->is_primary)
                                <flux:badge size="sm" color="green">Primary</flux:badge>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">No store selected.</p>
    @endif
</div>
