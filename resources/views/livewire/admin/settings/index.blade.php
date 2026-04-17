<div>
    <flux:heading size="xl">Settings</flux:heading>

    <div class="mt-6 border-b border-zinc-200 dark:border-zinc-700">
        <nav class="flex gap-6">
            @foreach (['general' => 'General', 'domains' => 'Domains'] as $tab => $label)
                <button
                    wire:click="setTab('{{ $tab }}')"
                    class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === $tab ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    <div class="mt-6">
        @if ($activeTab === 'general')
            <livewire:admin.settings.general />
        @elseif ($activeTab === 'domains')
            <livewire:admin.settings.domains />
        @endif
    </div>
</div>
