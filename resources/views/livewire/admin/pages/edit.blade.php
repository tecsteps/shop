<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.pages.index') }}" wire:navigate>Pages</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $page->title }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Edit page</flux:heading>

    <form wire:submit="save" class="space-y-6 max-w-2xl">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <flux:field>
                <flux:label>Title</flux:label>
                <flux:input wire:model="title" />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>Handle</flux:label>
                <flux:input wire:model="handle" />
                <flux:description>URL slug for this page.</flux:description>
                <flux:error name="handle" />
            </flux:field>

            <flux:field>
                <flux:label>Content</flux:label>
                <flux:textarea wire:model="bodyHtml" rows="12" />
                <flux:error name="bodyHtml" />
            </flux:field>

            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model="status">
                    @foreach ($statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ ucfirst($status->value) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="status" />
            </flux:field>
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">Save changes</flux:button>
            <flux:button href="{{ route('admin.pages.index') }}" wire:navigate variant="ghost">Cancel</flux:button>
        </div>
    </form>
</div>
