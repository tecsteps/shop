<section class="space-y-6 pb-24">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ $isEditing ? 'Edit page' : 'Create page' }}</flux:heading>
            <flux:text class="mt-1">Title, handle, publication state, and HTML body.</flux:text>
        </div>

        <div class="flex gap-2">
            @if ($isEditing)
                <flux:button type="button" wire:click="deletePage" wire:confirm="Delete this page?" variant="danger" icon="trash">Delete</flux:button>
            @endif
            <flux:button :href="route('admin.pages.index')" wire:navigate variant="filled" icon="arrow-left">Pages</flux:button>
        </div>
    </div>

    @if ($actionMessage !== '' || session('status'))
        <flux:callout color="green" icon="check-circle">{{ $actionMessage !== '' ? $actionMessage : session('status') }}</flux:callout>
    @endif

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="space-y-4">
                    <flux:input wire:model.live.debounce.300ms="title" label="Title" />
                    <flux:error name="title" />

                    <flux:input wire:model="handle" label="Handle" />
                    <flux:error name="handle" />

                    <flux:textarea wire:model="bodyHtml" label="Body HTML" rows="16" />
                    <flux:error name="bodyHtml" />
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Publishing</flux:heading>

                <div class="mt-4 space-y-4">
                    <flux:select wire:model="status" label="Status">
                        <flux:select.option value="draft">Draft</flux:select.option>
                        <flux:select.option value="published">Published</flux:select.option>
                        <flux:select.option value="archived">Archived</flux:select.option>
                    </flux:select>
                    <flux:error name="status" />

                    <flux:input wire:model="publishedAt" type="datetime-local" label="Published at" />
                    <flux:error name="publishedAt" />
                </div>
            </div>
        </div>

        <div class="fixed bottom-0 left-0 right-0 z-40 border-t border-zinc-200 bg-white/95 px-4 py-3 backdrop-blur dark:border-zinc-700 dark:bg-zinc-950/95 lg:left-64">
            <div class="mx-auto flex max-w-7xl justify-end gap-3">
                <flux:button :href="route('admin.pages.index')" wire:navigate variant="ghost">Discard</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" data-test="page-save-button">
                    <span wire:loading.remove>Save</span>
                    <span wire:loading>Saving...</span>
                </flux:button>
            </div>
        </div>
    </form>
</section>
