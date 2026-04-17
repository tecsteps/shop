<div class="space-y-6">
    <flux:heading size="xl">Settings</flux:heading>

    @if (session('message'))
        <div class="rounded-md bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    {{-- Tabs --}}
    <div class="flex gap-2 border-b border-zinc-200 dark:border-zinc-700">
        <button wire:click="$set('activeTab', 'general')" class="border-b-2 px-4 py-2 text-sm font-medium {{ $activeTab === 'general' ? 'border-blue-500 text-blue-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400' }}">
            General
        </button>
        <button wire:click="$set('activeTab', 'domains')" class="border-b-2 px-4 py-2 text-sm font-medium {{ $activeTab === 'domains' ? 'border-blue-500 text-blue-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400' }}">
            Domains
        </button>
        <a href="{{ route('admin.settings.shipping') }}" class="border-b-2 border-transparent px-4 py-2 text-sm font-medium text-zinc-500 hover:text-zinc-700 dark:text-zinc-400">
            Shipping
        </a>
        <a href="{{ route('admin.settings.taxes') }}" class="border-b-2 border-transparent px-4 py-2 text-sm font-medium text-zinc-500 hover:text-zinc-700 dark:text-zinc-400">
            Taxes
        </a>
    </div>

    @if ($activeTab === 'general')
        <form wire:submit="saveGeneral" class="space-y-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:input wire:model="storeName" label="Store Name" required />
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <flux:input wire:model="defaultCurrency" label="Default Currency" />
                    <flux:input wire:model="timezone" label="Timezone" />
                </div>
            </div>
            <flux:button type="submit" variant="primary">Save Settings</flux:button>
        </form>
    @endif

    @if ($activeTab === 'domains')
        <div class="space-y-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="md" class="mb-4">Domains</flux:heading>
                @foreach ($domains as $domain)
                    <div wire:key="domain-{{ $domain->id }}" class="flex items-center justify-between border-b border-zinc-100 py-2 dark:border-zinc-800">
                        <div>
                            <span class="font-medium">{{ $domain->hostname }}</span>
                            @if ($domain->is_primary)
                                <flux:badge size="sm" class="ml-2">Primary</flux:badge>
                            @endif
                        </div>
                        @if (! $domain->is_primary)
                            <flux:button wire:click="deleteDomain({{ $domain->id }})" variant="ghost" size="sm">Remove</flux:button>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="md" class="mb-4">Add Domain</flux:heading>
                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <flux:input wire:model="newDomainHostname" label="Hostname" placeholder="store.example.com" />
                    </div>
                    <flux:button wire:click="addDomain" variant="primary">Add</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
