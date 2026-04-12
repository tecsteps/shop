<div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Themes</flux:heading>
    </div>

    @if (session('status'))
        <div class="rounded border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
            {{ session('status') }}
        </div>
    @endif

    @if ($themes->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            No themes installed.
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($themes as $theme)
                <div @class([
                    'rounded-lg border p-5',
                    'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-950' => $theme->status->value === 'published',
                    'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' => $theme->status->value !== 'published',
                ])>
                    <div class="flex items-center justify-between">
                        <flux:heading size="lg">{{ $theme->name }}</flux:heading>
                        <flux:badge :color="$theme->status->value === 'published' ? 'green' : 'zinc'">
                            {{ $theme->status->value }}
                        </flux:badge>
                    </div>
                    <p class="mt-1 text-xs text-zinc-500">Version {{ $theme->version ?? '1.0' }}</p>
                    @if ($theme->published_at !== null)
                        <p class="text-xs text-zinc-500">Published {{ $theme->published_at->diffForHumans() }}</p>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($theme->status->value !== 'published')
                            <flux:button size="sm" variant="primary" wire:click="publish({{ $theme->id }})">Publish</flux:button>
                        @endif
                        <flux:button size="sm" variant="ghost" wire:click="duplicate({{ $theme->id }})">Duplicate</flux:button>
                        @if ($theme->status->value !== 'published')
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $theme->id }})" wire:confirm="Delete theme?">Delete</flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
