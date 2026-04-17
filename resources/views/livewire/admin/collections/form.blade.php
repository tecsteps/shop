<div>
    <flux:heading size="xl">{{ $collection ? __('Edit Collection') : __('New Collection') }}</flux:heading>

    <form wire:submit="save" class="mt-6 space-y-6 max-w-2xl">
        <flux:field>
            <flux:input wire:model="title" :label="__('Title')" required />
        </flux:field>

        <flux:field>
            <flux:textarea wire:model="description_html" :label="__('Description')" rows="4" />
        </flux:field>

        <flux:field>
            <flux:select wire:model="type" :label="__('Type')">
                <flux:select.option value="manual">{{ __('Manual') }}</flux:select.option>
                <flux:select.option value="automated">{{ __('Automated') }}</flux:select.option>
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:select wire:model="status" :label="__('Status')">
                <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
            </flux:select>
        </flux:field>

        <flux:separator />

        <div class="flex items-center justify-end gap-2">
            <flux:button :href="route('admin.collections.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button variant="primary" type="submit">{{ $collection ? __('Save') : __('Create collection') }}</flux:button>
        </div>
    </form>
</div>
