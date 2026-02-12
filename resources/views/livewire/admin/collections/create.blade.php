<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.collections.index') }}" wire:navigate>Collections</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Create collection</flux:heading>

    <form wire:submit="save" class="space-y-6 max-w-2xl">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <flux:input wire:model="title" label="Title" placeholder="e.g. Summer sale" required />
            <flux:input wire:model="handle" label="Handle" placeholder="e.g. summer-sale" required />
            <flux:textarea wire:model="descriptionHtml" label="Description" rows="4" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:select wire:model="type" label="Type">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}">{{ ucfirst($type->value) }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="status" label="Status">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}">{{ ucfirst($status->value) }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        {{-- SEO --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <flux:heading size="lg">SEO</flux:heading>
            <flux:input wire:model="seoTitle" label="SEO title" placeholder="Page title for search engines" />
            <flux:textarea wire:model="seoDescription" label="SEO description" rows="2" placeholder="Meta description for search engines" />
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">Create collection</flux:button>
            <flux:button href="{{ route('admin.collections.index') }}" wire:navigate variant="ghost">Cancel</flux:button>
        </div>
    </form>
</div>
