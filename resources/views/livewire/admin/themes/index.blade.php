<section class="space-y-6">
    <div>
        <flux:heading size="xl">Themes</flux:heading>
        <flux:text class="mt-1">Published and draft storefront themes.</flux:text>
    </div>

    @if (session('status'))
        <flux:callout color="green" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <flux:error name="theme" />

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($themes as $theme)
            <div wire:key="theme-{{ $theme->getKey() }}" class="overflow-hidden rounded-lg border bg-white dark:bg-zinc-900 {{ $theme->isPublished() ? 'border-blue-500 ring-2 ring-blue-100 dark:border-blue-400 dark:ring-blue-950' : 'border-zinc-200 dark:border-zinc-700' }}">
                <div class="aspect-video bg-zinc-100 p-4 dark:bg-zinc-800">
                    <div class="flex h-full items-center justify-center rounded-lg border border-dashed border-zinc-300 text-zinc-400 dark:border-zinc-600">
                        <flux:icon name="paint-brush" class="size-10" />
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <flux:heading size="lg">{{ $theme->name }}</flux:heading>
                            <flux:text class="mt-1">v{{ $theme->version }} · {{ $theme->files_count }} files</flux:text>
                        </div>
                        <flux:badge :color="$this->statusColor($theme)">{{ Str::headline($theme->status->value) }}</flux:badge>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <flux:button :href="route('admin.themes.editor', $theme)" wire:navigate variant="primary">Customize</flux:button>
                        @unless ($theme->isPublished())
                            <flux:button type="button" wire:click="publishTheme({{ $theme->getKey() }})" variant="filled">Publish</flux:button>
                        @endunless
                        <flux:button type="button" wire:click="duplicateTheme({{ $theme->getKey() }})" variant="filled">Duplicate</flux:button>
                        @unless ($theme->isPublished())
                            <flux:button type="button" wire:click="deleteTheme({{ $theme->getKey() }})" wire:confirm="Delete this theme?" variant="danger">Delete</flux:button>
                        @endunless
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>
