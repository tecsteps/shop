<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.themes.index') }}" wire:navigate>Themes</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $theme->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ $theme->name }} - Settings</flux:heading>
        <flux:badge size="sm" :color="$theme->status->value === 'published' ? 'green' : 'zinc'">
            {{ ucfirst($theme->status->value) }}
        </flux:badge>
    </div>

    <form wire:submit="save" class="space-y-6 max-w-3xl">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <flux:heading size="lg">Theme settings (JSON)</flux:heading>
            <flux:text class="text-zinc-500">Edit the raw theme settings JSON. This controls announcement bar, colors, typography, and other theme options.</flux:text>

            <flux:field>
                <flux:label>Settings JSON</flux:label>
                <flux:textarea wire:model="settingsJson" rows="20" class="font-mono text-sm" />
                <flux:error name="settingsJson" />
            </flux:field>
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">Save settings</flux:button>
            <flux:button href="{{ route('admin.themes.index') }}" wire:navigate variant="ghost">Back to themes</flux:button>
        </div>
    </form>
</div>
