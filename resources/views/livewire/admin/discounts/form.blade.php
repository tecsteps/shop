<div>
    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-admin.card>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input wire:model="code" label="Discount code" required />
                        <flux:input wire:model="title" label="Title" />
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <flux:select wire:model="type" label="Type">
                            <option value="code">Code</option>
                            <option value="automatic">Automatic</option>
                        </flux:select>
                        <flux:select wire:model="value_type" label="Value type">
                            <option value="percent">Percentage</option>
                            <option value="fixed">Fixed amount</option>
                            <option value="free_shipping">Free shipping</option>
                        </flux:select>
                        <flux:input wire:model="value_amount" label="Value" type="number" step="0.01" />
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input wire:model="starts_at" label="Start date" type="datetime-local" />
                        <flux:input wire:model="ends_at" label="End date" type="datetime-local" />
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input wire:model="usage_limit" label="Usage limit" type="number" />
                        <flux:input wire:model="minimum_purchase" label="Minimum purchase ($)" type="number" step="0.01" />
                    </div>
                </x-admin.card>
            </div>
            <div class="space-y-6">
                <x-admin.card>
                    <flux:select wire:model="status" label="Status">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                        <option value="disabled">Disabled</option>
                    </flux:select>
                </x-admin.card>
                <flux:button type="submit" variant="primary" class="w-full">{{ $discount ? 'Update' : 'Create' }} discount</flux:button>
            </div>
        </div>
    </form>
</div>
