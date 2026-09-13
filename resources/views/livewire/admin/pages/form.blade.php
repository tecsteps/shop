<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.pages.index') }}" wire:navigate>Pages</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $isEditing ? $this->page->title : 'Add page' }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ $isEditing ? $this->page->title : 'Add page' }}</flux:heading>
        @if ($isEditing)
            @can('delete', $this->page)
                <flux:button variant="danger" wire:click="$set('showDeleteModal', true)">Delete</flux:button>
            @endcan
        @endif
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:field>
                <flux:label>Title</flux:label>
                <flux:input wire:model="title" />
                <flux:error name="title" />
            </flux:field>
            <flux:field class="mt-4">
                <flux:label>Handle</flux:label>
                <flux:input wire:model="handle" />
                <flux:error name="handle" />
            </flux:field>
            <flux:field class="mt-4">
                <flux:label>Body</flux:label>
                <flux:textarea wire:model="bodyHtml" rows="16" />
                <flux:error name="bodyHtml" />
            </flux:field>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model="status">
                    <flux:select.option value="draft">Draft</flux:select.option>
                    <flux:select.option value="published">Published</flux:select.option>
                    <flux:select.option value="archived">Archived</flux:select.option>
                </flux:select>
                <flux:error name="status" />
            </flux:field>
            <flux:field class="mt-4">
                <flux:label>Published at</flux:label>
                <flux:input wire:model="publishedAt" type="datetime-local" />
                <flux:error name="publishedAt" />
            </flux:field>
        </div>
    </div>

    <div class="sticky bottom-0 z-10 mt-6 flex justify-end gap-2 border-t border-zinc-200 bg-zinc-100 py-4 dark:border-zinc-700 dark:bg-zinc-950">
        <flux:button variant="ghost" href="{{ route('admin.pages.index') }}" wire:navigate>Discard</flux:button>
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Save</span>
            <span wire:loading wire:target="save">Saving...</span>
        </flux:button>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <flux:heading size="lg">Delete this page?</flux:heading>
        <flux:text class="mt-2">The page will be permanently removed from your store.</flux:text>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
            <flux:button variant="danger" wire:click="deletePage">Delete</flux:button>
        </div>
    </flux:modal>
</div>
