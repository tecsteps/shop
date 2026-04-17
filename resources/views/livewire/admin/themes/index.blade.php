<div>
    <flux:heading size="xl">Themes</flux:heading>
    <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($themes as $theme)
            <div wire:key="theme-{{ $theme->id }}" class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:heading size="lg">{{ $theme->name }}</flux:heading>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Version {{ $theme->version ?? '1.0' }}</p>
                <div class="mt-3">
                    <flux:badge size="sm" :color="$theme->status === \App\Enums\ThemeStatus::Published ? 'green' : 'yellow'">
                        {{ ucfirst($theme->status->value) }}
                    </flux:badge>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">No themes.</p>
        @endforelse
    </div>
</div>
