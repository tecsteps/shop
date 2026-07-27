<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Themes</flux:heading>

        <flux:button variant="primary" icon="plus" wire:click="$set('showCreateForm', true)">Add theme</flux:button>
    </div>

    @if ($themes->isEmpty())
        <div class="flex flex-col items-center rounded-lg border border-zinc-200 bg-white px-6 py-16 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon name="paint-brush" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-4">Create your first theme</flux:heading>
            <flux:text class="mt-1">Themes control the look and feel of your storefront.</flux:text>
            <flux:button variant="primary" class="mt-6" wire:click="$set('showCreateForm', true)">Add theme</flux:button>
        </div>
    @else
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($themes as $theme)
                <div wire:key="theme-{{ $theme->id }}" class="overflow-hidden rounded-lg border bg-white dark:bg-zinc-900 {{ $theme->isPublished() ? 'border-blue-500 ring-2 ring-blue-500/30 dark:border-blue-500' : 'border-zinc-200 dark:border-zinc-700' }}">
                    {{-- Thumbnail placeholder --}}
                    <div class="flex aspect-video items-center justify-center bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon name="paint-brush" class="size-10 text-zinc-300 dark:text-zinc-600" />
                    </div>

                    <div class="p-4">
                        <div class="flex items-center justify-between gap-2">
                            <flux:heading size="md">{{ $theme->name }}</flux:heading>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">v{{ $theme->version }}</flux:text>
                        </div>

                        <div class="mt-2 flex items-center gap-2">
                            @if ($theme->isPublished())
                                <flux:badge size="sm" color="green">Published</flux:badge>
                                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ $theme->published_at?->diffForHumans() }}</flux:text>
                            @else
                                <flux:badge size="sm" color="zinc">Draft</flux:badge>
                            @endif
                        </div>

                        <div class="mt-4 flex items-center gap-2">
                            <flux:button size="sm" variant="primary" :href="route('admin.themes.editor', $theme)" wire:navigate>Customize</flux:button>

                            <flux:dropdown>
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="More actions for {{ $theme->name }}" />
                                <flux:menu>
                                    @if (! $theme->isPublished())
                                        <flux:menu.item icon="check" wire:click="publishTheme({{ $theme->id }})">Publish</flux:menu.item>
                                    @endif
                                    <flux:menu.item icon="document-duplicate" wire:click="duplicateTheme({{ $theme->id }})">Duplicate</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $theme->id }})">Delete</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Create theme modal --}}
    <flux:modal wire:model="showCreateForm" name="create-theme" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Add theme</flux:heading>

            <flux:field>
                <flux:label for="newThemeName">Theme name</flux:label>
                <flux:input id="newThemeName" wire:model.blur="newThemeName" placeholder="My Theme" />
                <flux:error name="newThemeName" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showCreateForm', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="createTheme">Create theme</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Delete confirmation modal (spec 03 §19.3) --}}
    <flux:modal wire:model="confirmingDelete" name="confirm-delete-theme" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Delete this theme?</flux:heading>
            <flux:text>The theme and its settings will be permanently removed. The published theme cannot be deleted.</flux:text>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('confirmingDelete', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="deleteTheme">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
