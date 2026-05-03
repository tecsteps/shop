<div class="space-y-6">
    <div>
        <flux:heading size="xl">Themes</flux:heading>
        <flux:text>Publish, duplicate, and customize storefront themes.</flux:text>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($themes as $theme)
            <section wire:key="admin-theme-{{ $theme->id }}" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <flux:heading size="lg">{{ $theme->name }}</flux:heading>
                        <flux:text>Version {{ $theme->version }}</flux:text>
                    </div>
                    <flux:badge>{{ $theme->status->value }}</flux:badge>
                </div>

                <div class="mt-6 flex flex-wrap gap-2">
                    <flux:button :href="route('admin.themes.editor', $theme)" wire:navigate>Customize</flux:button>
                    <flux:button wire:click="publish({{ $theme->id }})">Publish</flux:button>
                    <flux:button wire:click="duplicate({{ $theme->id }})">Duplicate</flux:button>
                </div>
            </section>
        @endforeach
    </div>
</div>
