<div>
    <flux:heading size="xl" class="mb-6">
        {{ $this->isEditing ? "Edit: {$title}" : 'Create Page' }}
    </flux:heading>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Left column --}}
            <div class="lg:col-span-2 space-y-6">
                <flux:input
                    wire:model.live.debounce.500ms="title"
                    label="Title"
                    placeholder="About Us"
                    required
                />
                @error('title')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <flux:input
                    wire:model="handle"
                    label="Handle"
                    placeholder="about-us"
                    required
                />
                @error('handle')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <flux:textarea
                    wire:model="bodyHtml"
                    label="Content"
                    placeholder="Write your page content here..."
                    rows="16"
                />

                {{-- SEO fields --}}
                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 space-y-4">
                    <flux:heading size="md">SEO</flux:heading>
                    <flux:input
                        wire:model="metaTitle"
                        label="Meta title"
                        placeholder="Custom meta title"
                    />
                    <flux:textarea
                        wire:model="metaDescription"
                        label="Meta description"
                        placeholder="A brief description for search engines..."
                        rows="3"
                    />
                </div>
            </div>

            {{-- Right column --}}
            <div class="space-y-6">
                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 space-y-4">
                    <flux:select wire:model="status" label="Status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </flux:select>
                </div>

                @if ($this->isEditing)
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                        <flux:button
                            variant="ghost"
                            class="w-full text-red-600 dark:text-red-400"
                            wire:click="deletePage"
                            wire:confirm="Are you sure you want to delete this page?"
                        >
                            Delete page
                        </flux:button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sticky save bar --}}
        <div class="sticky bottom-0 mt-8 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-4 bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-end gap-3">
                <flux:button variant="ghost" :href="route('admin.pages.index')" wire:navigate>Discard</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </div>
        </div>
    </form>
</div>
