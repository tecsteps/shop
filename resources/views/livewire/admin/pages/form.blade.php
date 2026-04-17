<div>
    <flux:heading size="xl">{{ $page ? __('Edit Page') : __('New Page') }}</flux:heading>

    <form wire:submit="save" class="mt-6 space-y-6 max-w-2xl">
        <flux:field>
            <flux:input wire:model="title" :label="__('Title')" required />
        </flux:field>

        <flux:field>
            <flux:textarea wire:model="body_html" :label="__('Content')" rows="10" />
        </flux:field>

        <flux:field>
            <flux:select wire:model="status" :label="__('Status')">
                <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                <flux:select.option value="published">{{ __('Published') }}</flux:select.option>
                <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
            </flux:select>
        </flux:field>

        <flux:separator />

        <div class="flex items-center justify-end gap-2">
            <flux:button :href="route('admin.pages.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button variant="primary" type="submit">{{ $page ? __('Save') : __('Create page') }}</flux:button>
        </div>
    </form>
</div>
