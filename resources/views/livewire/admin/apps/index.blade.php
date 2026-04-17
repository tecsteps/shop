<div>
    <flux:heading size="xl" class="mb-6">Apps</flux:heading>

    @if($this->installedApps->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon name="squares-2x2" class="mx-auto mb-4 h-12 w-12 text-zinc-400" />
            <flux:heading size="md" class="mb-2">No apps installed</flux:heading>
            <flux:text class="text-zinc-500">Apps extend the functionality of your store. Install apps to add new features.</flux:text>
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->installedApps as $installation)
                <div class="flex items-center gap-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon name="squares-2x2" class="h-5 w-5 text-zinc-500" />
                    </div>
                    <div class="flex-1">
                        <flux:heading size="md">{{ $installation->app->name }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">
                            Installed {{ $installation->installed_at ? \Carbon\Carbon::parse($installation->installed_at)->diffForHumans() : 'recently' }}
                        </flux:text>
                    </div>
                    <flux:badge :color="$installation->status->value === 'active' ? 'green' : 'zinc'">
                        {{ ucfirst($installation->status->value) }}
                    </flux:badge>
                </div>
            @endforeach
        </div>
    @endif
</div>
