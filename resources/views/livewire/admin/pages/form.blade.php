<div class="pb-24">
    <flux:heading size="xl">
        {{ $this->isEditing ? $page->title : 'Add page' }}
    </flux:heading>

    <form wire:submit="save">
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Left column --}}
            <div class="space-y-6 lg:col-span-2">
                <flux:card class="p-6">
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>Title</flux:label>
                            <flux:input wire:model="title" placeholder="About Us" />
                            <flux:error name="title" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Handle</flux:label>
                            <flux:input wire:model="handle" placeholder="about-us" />
                            <flux:error name="handle" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Body</flux:label>
                            <flux:textarea wire:model="bodyHtml" rows="16" placeholder="Page content..." />
                            <flux:error name="bodyHtml" />
                        </flux:field>
                    </div>
                </flux:card>
            </div>

            {{-- Right column --}}
            <div class="space-y-6">
                <flux:card class="p-6">
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="archived">Archived</option>
                        </flux:select>
                        <flux:error name="status" />
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label>Published at</flux:label>
                        <flux:input type="datetime-local" wire:model="publishedAt" />
                    </flux:field>
                </flux:card>

                @if ($this->isEditing)
                    <flux:button variant="danger" icon="trash" class="w-full" wire:click="$set('confirmingDelete', true)">
                        Delete page
                    </flux:button>
                @endif
            </div>
        </div>

        {{-- Sticky save bar --}}
        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95 lg:start-64">
            <div class="mx-auto flex max-w-7xl items-center justify-end gap-3 px-4 py-3 sm:px-6 lg:px-8">
                <flux:button variant="ghost" :href="route('admin.pages.index')" wire:navigate>Discard</flux:button>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Save</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </div>
        </div>
    </form>

    {{-- Delete confirmation --}}
    <flux:modal wire:model="confirmingDelete" class="max-w-md">
        <flux:heading size="lg">Delete this page?</flux:heading>
        <flux:text class="mt-2">This will permanently remove the page.</flux:text>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="$set('confirmingDelete', false)">Cancel</flux:button>
            <flux:button variant="danger" wire:click="deletePage">Delete</flux:button>
        </div>
    </flux:modal>
</div>
