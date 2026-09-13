<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Settings</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Store Settings</flux:heading>

    <div class="mb-6 flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-700" role="tablist" aria-label="Settings sections">
        <button type="button" wire:click="$set('tab', 'general')" role="tab" aria-selected="{{ $this->tab === 'general' ? 'true' : 'false' }}" class="px-3 py-2 text-sm {{ $this->tab === 'general' ? 'border-b-2 border-zinc-900 font-semibold dark:border-white' : 'text-zinc-500 dark:text-zinc-400' }}">General</button>
        <button type="button" wire:click="$set('tab', 'domains')" role="tab" aria-selected="{{ $this->tab === 'domains' ? 'true' : 'false' }}" class="px-3 py-2 text-sm {{ $this->tab === 'domains' ? 'border-b-2 border-zinc-900 font-semibold dark:border-white' : 'text-zinc-500 dark:text-zinc-400' }}">Domains</button>
        <a href="{{ route('admin.settings.shipping') }}" wire:navigate class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">Shipping</a>
        <a href="{{ route('admin.settings.taxes') }}" wire:navigate class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">Taxes</a>
    </div>

    @if ($this->tab === 'general')
        <livewire:admin.settings.general wire:key="settings-general" />
    @else
        <livewire:admin.settings.domains wire:key="settings-domains" />
    @endif
</div>
