<div class="space-y-4">
    <flux:heading size="xl">Settings</flux:heading>

    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <nav class="flex gap-3 border-b border-zinc-200 pb-2 text-sm dark:border-zinc-800">
        <button wire:click="$set('tab', 'general')" class="{{ $tab === 'general' ? 'font-semibold text-sky-600' : '' }}">General</button>
        <button wire:click="$set('tab', 'domains')" class="{{ $tab === 'domains' ? 'font-semibold text-sky-600' : '' }}">Domains</button>
        <a href="{{ route('admin.settings.shipping') }}" wire:navigate>Shipping</a>
        <a href="{{ route('admin.settings.taxes') }}" wire:navigate>Taxes</a>
        <button wire:click="$set('tab', 'notifications')" class="{{ $tab === 'notifications' ? 'font-semibold text-sky-600' : '' }}">Notifications</button>
    </nav>

    @if ($tab === 'general')
        <form wire:submit="saveGeneral" class="space-y-3 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:input wire:model="name" label="Store name" required />
            <flux:input wire:model="default_currency" label="Currency" />
            <flux:input wire:model="default_locale" label="Locale" />
            <flux:input wire:model="timezone" label="Timezone" />
            <flux:button type="submit" variant="primary" data-testid="save-general">Save</flux:button>
        </form>
    @elseif ($tab === 'domains')
        <div class="space-y-4 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <form wire:submit="addDomain" class="flex items-end gap-3">
                <flux:input wire:model="newDomain" label="Hostname" placeholder="example.com" />
                <flux:button type="submit" variant="primary">Add domain</flux:button>
            </form>
            <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($domains as $domain)
                    <li wire:key="domain-{{ $domain->id }}" class="flex items-center justify-between py-2 text-sm">
                        <span>{{ $domain->hostname }} ({{ $domain->type?->value }}) {{ $domain->is_primary ? '[primary]' : '' }}</span>
                        @if (! $domain->is_primary)
                            <flux:button size="xs" variant="danger" wire:click="removeDomain({{ $domain->id }})" wire:confirm="Remove domain?">Remove</flux:button>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @elseif ($tab === 'notifications')
        <div class="space-y-2 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:text>Notification templates (feature flag). Coming soon.</flux:text>
        </div>
    @endif
</div>
