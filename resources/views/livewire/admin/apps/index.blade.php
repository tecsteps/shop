<div class="space-y-6">
    <div>
        <flux:heading size="xl">Apps</flux:heading>
        <flux:text>Installable integrations for store operations.</flux:text>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($apps as $app)
            <a wire:key="admin-app-{{ $app['id'] }}" href="{{ route('admin.apps.show', $app['id']) }}" wire:navigate class="rounded-lg border border-zinc-200 bg-white p-5 transition hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                <flux:heading size="lg">{{ $app['name'] }}</flux:heading>
                <flux:badge class="mt-4">{{ $app['status'] }}</flux:badge>
            </a>
        @endforeach
    </div>
</div>
