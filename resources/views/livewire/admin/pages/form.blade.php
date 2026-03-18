<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ $this->isEditing ? $title : __('Add page') }}</flux:heading>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 space-y-4">
                    <flux:field>
                        <flux:label>{{ __('Title') }}</flux:label>
                        <flux:input wire:model="title" />
                        <flux:error name="title" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Handle') }}</flux:label>
                        <flux:input wire:model="handle" />
                        <flux:error name="handle" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Content') }}</flux:label>
                        <flux:textarea wire:model="bodyHtml" rows="12" />
                    </flux:field>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                    <flux:field>
                        <flux:label>{{ __('Status') }}</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                            <flux:select.option value="published">{{ __('Published') }}</flux:select.option>
                            <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>
            </div>
        </div>

        <div class="sticky bottom-0 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700 p-4 mt-6 flex justify-end gap-4">
            <flux:button variant="ghost" :href="route('admin.pages.index')" wire:navigate>{{ __('Discard') }}</flux:button>
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>
