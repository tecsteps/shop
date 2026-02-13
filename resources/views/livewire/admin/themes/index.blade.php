<div>
    <flux:heading size="xl">{{ __('Themes') }}</flux:heading>

    <div class="mt-6 space-y-4">
        @forelse($themes as $theme)
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">{{ $theme->name }}</flux:heading>
                    <flux:badge size="sm" :color="$theme->status->value === 'published' ? 'green' : 'yellow'">
                        {{ ucfirst($theme->status->value) }}
                    </flux:badge>
                </div>
                <flux:text class="text-sm">{{ __('Version') }}: {{ $theme->version }}</flux:text>
            </div>
        @empty
            <flux:text>{{ __('No themes found.') }}</flux:text>
        @endforelse
    </div>
</div>
