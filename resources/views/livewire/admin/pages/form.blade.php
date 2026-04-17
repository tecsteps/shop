<div>
    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('admin.pages.index') }}" wire:navigate>Pages</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $page ? $title : 'Add page' }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <flux:heading size="xl" class="mb-6">{{ $page ? $title : 'Add page' }}</flux:heading>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Title</flux:label>
                        <flux:input wire:model.live.debounce.500ms="title" />
                        <flux:error name="title" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Handle</flux:label>
                        <flux:input wire:model="handle" />
                        <flux:error name="handle" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Body</flux:label>
                        <flux:textarea wire:model="bodyHtml" rows="16" />
                    </flux:field>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model="status">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                    </flux:select>
                </flux:field>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:field>
                    <flux:label>Published at</flux:label>
                    <flux:input type="datetime-local" wire:model="publishedAt" />
                </flux:field>
            </div>
        </div>
    </div>

    <div class="fixed bottom-0 left-0 right-0 z-30 border-t border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900 lg:left-64">
        <div class="flex items-center justify-end gap-3">
            @if ($page)
                <flux:button variant="ghost" wire:click="deletePage" wire:confirm="Delete this page?">Delete</flux:button>
            @endif
            <flux:button variant="ghost" href="{{ route('admin.pages.index') }}" wire:navigate>Discard</flux:button>
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </div>
</div>
