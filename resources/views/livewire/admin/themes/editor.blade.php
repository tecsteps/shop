<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Editing {{ $theme->name }}</flux:heading>
        <flux:button variant="ghost" href="{{ route('admin.themes.index') }}" wire:navigate>Back</flux:button>
    </div>
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-2 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:heading size="sm">Sections</flux:heading>
            <flux:text class="text-zinc-500">Sections editor coming soon.</flux:text>
        </div>
        <div class="rounded-xl bg-white p-2 shadow-sm dark:bg-zinc-900">
            <iframe src="{{ url('/?theme_preview='.$theme->id) }}" class="h-[600px] w-full rounded"></iframe>
        </div>
        <form wire:submit="save" class="space-y-3 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:input wire:model="settings.hero_heading" label="Hero heading" />
            <flux:input wire:model="settings.hero_subheading" label="Hero subheading" />
            <flux:input wire:model="settings.featured_collection_handles" label="Featured collections (handles)" />
            <flux:input wire:model="settings.featured_product_handles" label="Featured products (handles)" />
            <flux:input wire:model="settings.primary_color" label="Primary color" />
            <flux:input wire:model="settings.accent_color" label="Accent color" />
            <flux:select wire:model="settings.dark_mode" label="Dark mode">
                <flux:select.option value="auto">Auto</flux:select.option>
                <flux:select.option value="light">Light</flux:select.option>
                <flux:select.option value="dark">Dark</flux:select.option>
            </flux:select>
            <flux:button type="submit" variant="primary">Save</flux:button>
        </form>
    </div>
</div>
