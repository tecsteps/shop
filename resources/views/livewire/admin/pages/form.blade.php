<div class="pb-20">
    <flux:heading size="xl">{{ $this->isEditing() ? $page->title : 'Add page' }}</flux:heading>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Left column (2/3): primary content (spec 03 §13.2) --}}
        <div class="lg:col-span-2">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:field>
                    <flux:label for="title">Title</flux:label>
                    <flux:input id="title" wire:model.blur="title" placeholder="About Us" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field class="mt-4">
                    <flux:label for="handle">Handle</flux:label>
                    <flux:input id="handle" wire:model.blur="handle" placeholder="about-us" />
                    <flux:error name="handle" />
                </flux:field>

                <flux:field class="mt-4">
                    <flux:label for="bodyHtml">Body</flux:label>
                    <flux:textarea id="bodyHtml" wire:model.blur="bodyHtml" rows="16" placeholder="Write your page content..." />
                    <flux:error name="bodyHtml" />
                </flux:field>
            </div>
        </div>

        {{-- Right column (1/3): status and publishing --}}
        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:field>
                    <flux:label for="status">Status</flux:label>
                    <flux:select id="status" wire:model="status">
                        <flux:select.option value="draft">Draft</flux:select.option>
                        <flux:select.option value="published">Published</flux:select.option>
                        <flux:select.option value="archived">Archived</flux:select.option>
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:field>
                    <flux:label for="publishedAt">Published at</flux:label>
                    <flux:input id="publishedAt" type="datetime-local" wire:model.blur="publishedAt" />
                    <flux:description>Set automatically when publishing.</flux:description>
                    <flux:error name="publishedAt" />
                </flux:field>
            </div>
        </div>
    </div>

    {{-- Sticky save bar (spec 03 §19.2) --}}
    <div class="fixed inset-x-0 bottom-0 z-20 border-t border-zinc-200 bg-white/95 backdrop-blur lg:left-64 dark:border-zinc-700 dark:bg-zinc-900/95">
        <div class="mx-auto flex max-w-7xl items-center justify-end gap-3 px-4 py-3 sm:px-6 lg:px-8">
            <flux:button variant="ghost" :href="route('admin.pages.index')" wire:navigate>Discard</flux:button>
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </div>
</div>
