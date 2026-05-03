<form wire:submit="save" class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl">{{ $theme->name }}</flux:heading>
            <flux:text>Theme settings JSON for the storefront renderer.</flux:text>
        </div>

        <div class="flex gap-2">
            <flux:button :href="route('admin.themes.index')" wire:navigate>Back to themes</flux:button>
            <flux:button type="submit" variant="primary">Save settings</flux:button>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[18rem_minmax(0,1fr)_18rem]">
        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading size="lg">Sections</flux:heading>
            <div class="mt-4 space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                <div>Announcement</div>
                <div>Hero</div>
                <div>Featured products</div>
                <div>Footer</div>
            </div>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading size="lg">Preview</flux:heading>
            <iframe title="Storefront preview" src="{{ route('home') }}" class="mt-4 h-[32rem] w-full rounded-md border border-zinc-200 bg-white dark:border-zinc-800"></iframe>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:textarea wire:model="settingsJson" label="Settings JSON" rows="18" />
        </section>
    </div>
</form>
