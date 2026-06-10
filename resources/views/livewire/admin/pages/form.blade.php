<div class="space-y-6 pb-24">
    <x-admin.breadcrumbs :items="[
        ['label' => __('Pages'), 'href' => route('admin.pages.index')],
        ['label' => $this->isEditing ? $page->title : __('Add page')],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" level="1">
            {{ $this->isEditing ? $page->title : __('Add page') }}
        </flux:heading>

        @if ($this->isEditing)
            @can('delete', $page)
                <flux:modal.trigger name="confirm-delete-page">
                    <flux:button variant="danger" data-test="delete-page-button">{{ __('Delete') }}</flux:button>
                </flux:modal.trigger>
            @endcan
        @endif
    </div>

    <form wire:submit="save" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- LEFT COLUMN (2/3) --}}
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Title') }}</flux:label>
                    <flux:input wire:model="title" :placeholder="__('About Us')" data-test="page-title-input" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Handle') }}</flux:label>
                    <flux:input wire:model="handle" placeholder="about-us" data-test="page-handle-input" />
                    <flux:description>{{ __('Leave empty to generate from the title. The page is served at /pages/{handle}.') }}</flux:description>
                    <flux:error name="handle" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Body') }}</flux:label>
                    <flux:textarea wire:model="bodyHtml" rows="16" :placeholder="__('Write your page content (HTML supported)...')" data-test="page-body-input" />
                    <flux:description>{{ __('Basic HTML tags are supported.') }}</flux:description>
                    <flux:error name="bodyHtml" />
                </flux:field>
            </x-admin.card>
        </div>

        {{-- RIGHT COLUMN (1/3) --}}
        <div class="space-y-6">
            <x-admin.card :heading="__('Status')">
                <flux:field>
                    <flux:select wire:model="status" data-test="page-status-select">
                        <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                        <flux:select.option value="published">{{ __('Published') }}</flux:select.option>
                        <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>
            </x-admin.card>

            <x-admin.card :heading="__('Publishing')">
                <flux:field>
                    <flux:label>{{ __('Published at') }}</flux:label>
                    <flux:input wire:model="publishedAt" type="datetime-local" data-test="page-published-at-input" />
                    <flux:error name="publishedAt" />
                </flux:field>
            </x-admin.card>
        </div>

        {{-- Sticky save bar --}}
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/95 backdrop-blur lg:pl-64 dark:border-zinc-700 dark:bg-zinc-900/95">
            <div class="mx-auto flex max-w-7xl items-center justify-end gap-3 px-4 py-3 sm:px-6 lg:px-8">
                <flux:button variant="ghost" :href="route('admin.pages.index')" wire:navigate>
                    {{ __('Discard') }}
                </flux:button>
                <flux:button type="submit" variant="primary" data-test="save-page-button">
                    <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </flux:button>
            </div>
        </div>
    </form>

    @if ($this->isEditing)
        <flux:modal name="confirm-delete-page" class="md:max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Delete this page?') }}</flux:heading>
                <flux:text>{{ __('The page will be permanently removed from your storefront.') }}</flux:text>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="deletePage" data-test="confirm-delete-page-button">
                        {{ __('Delete page') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
