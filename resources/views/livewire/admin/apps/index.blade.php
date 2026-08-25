<div>
    <flux:heading size="xl">Apps</flux:heading>

    <div class="mt-6 space-y-4">
        @forelse ($this->installedApps as $installation)
            <a href="{{ route('admin.apps.show', $installation) }}" wire:navigate>
                <flux:card class="p-5 transition hover:border-zinc-300 dark:hover:border-zinc-600">
                    <div class="flex items-center gap-4">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                            <flux:icon.squares-2x2 class="size-6" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <flux:heading size="md">{{ $installation->app?->name ?? 'App' }}</flux:heading>
                            <flux:text>Installed {{ $installation->installed_at?->diffForHumans() }}</flux:text>
                        </div>
                        <flux:badge :color="$installation->status === 'active' ? 'green' : 'zinc'" size="sm">
                            {{ ucfirst($installation->status) }}
                        </flux:badge>
                    </div>
                </flux:card>
            </a>
        @empty
            <flux:card class="p-10 text-center">
                <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
                    <flux:icon.squares-2x2 class="size-6" />
                </div>
                <flux:heading size="lg" class="mt-4">No apps installed</flux:heading>
                <flux:text class="mt-1">Apps you install will appear here with their settings and permissions.</flux:text>
            </flux:card>
        @endforelse
    </div>

    {{-- Uninstall confirmation --}}
    <flux:modal wire:model="confirmingUninstall" class="max-w-md">
        <flux:heading size="lg">Uninstall this app?</flux:heading>
        <flux:text class="mt-2">The app will lose access to your store data. This can be reversed by reinstalling.</flux:text>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="$set('confirmingUninstall', false)">Cancel</flux:button>
            <flux:button variant="danger" wire:click="uninstallApp">Uninstall</flux:button>
        </div>
    </flux:modal>
</div>
