<div class="pb-24">
    <x-admin.breadcrumbs :items="[
        ['label' => __('Pages'), 'href' => route('admin.pages.index')],
        ['label' => $this->isEditing ? $title : __('Add page')],
    ]" />

    <flux:heading size="xl" level="1" class="mb-6">{{ $this->isEditing ? $title : __('Add page') }}</flux:heading>

    <form wire:submit="save">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-admin.card>
                    <flux:field>
                        <flux:label>{{ __('Title') }}</flux:label>
                        <flux:input wire:model="title" data-test="page-title" />
                        <flux:error name="title" />
                    </flux:field>
                    <flux:field class="mt-4">
                        <flux:label>{{ __('Handle') }}</flux:label>
                        <flux:input wire:model="handle" placeholder="about-us" />
                        <flux:error name="handle" />
                    </flux:field>
                    <flux:field class="mt-4">
                        <flux:label>{{ __('Body') }}</flux:label>
                        <flux:textarea wire:model="bodyHtml" rows="16" />
                        <flux:error name="bodyHtml" />
                    </flux:field>
                </x-admin.card>
            </div>

            <div class="space-y-6">
                <x-admin.card title="{{ __('Status') }}">
                    <flux:select wire:model="status">
                        <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                        <flux:select.option value="published">{{ __('Active') }}</flux:select.option>
                        <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
                    </flux:select>
                </x-admin.card>

                <x-admin.card title="{{ __('Publishing') }}">
                    <flux:field>
                        <flux:label>{{ __('Published at') }}</flux:label>
                        <flux:input type="datetime-local" wire:model="publishedAt" />
                    </flux:field>
                </x-admin.card>

                @if ($this->isEditing)
                    <x-admin.card>
                        <flux:button type="button" variant="danger" icon="trash" wire:click="$set('showDeleteModal', true)" class="w-full">{{ __('Delete page') }}</flux:button>
                    </x-admin.card>
                @endif
            </div>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/95 px-4 py-3 backdrop-blur lg:pl-72 dark:border-zinc-700 dark:bg-zinc-900/95">
            <div class="mx-auto flex max-w-5xl items-center justify-end gap-3">
                <flux:button variant="ghost" :href="route('admin.pages.index')" wire:navigate>{{ __('Discard') }}</flux:button>
                <flux:button type="submit" variant="primary" data-test="save-page">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </form>

    @if ($this->isEditing)
        <flux:modal wire:model.self="showDeleteModal" name="confirm-delete-page" class="md:w-96">
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Delete this page?') }}</flux:heading>
                <flux:text>{{ __('This action cannot be undone.') }}</flux:text>
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">{{ __('Cancel') }}</flux:button>
                    <flux:button variant="danger" wire:click="deletePage">{{ __('Delete') }}</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
