<div>
    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-admin.card>
                    <flux:input wire:model="title" label="Title" required />
                    <div class="mt-4">
                        <flux:textarea wire:model="content" label="Content" rows="10" />
                    </div>
                </x-admin.card>
            </div>
            <div class="space-y-6">
                <x-admin.card>
                    <flux:select wire:model="status" label="Status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </flux:select>
                </x-admin.card>
                <flux:button type="submit" variant="primary" class="w-full">{{ $page ? 'Update' : 'Create' }} page</flux:button>
            </div>
        </div>
    </form>
</div>
