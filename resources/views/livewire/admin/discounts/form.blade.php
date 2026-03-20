<div>
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.discounts.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">&larr; Discounts</a>
    </div>

    <flux:heading size="xl" class="mt-4">{{ $isEdit ? 'Edit Discount' : 'New Discount' }}</flux:heading>

    <form wire:submit="save" class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Main content --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:input wire:model="code" label="Code" required placeholder="e.g. SUMMER20" />
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <flux:select wire:model="type" label="Type">
                        <option value="code">Code</option>
                        <option value="automatic">Automatic</option>
                    </flux:select>
                    <flux:select wire:model="value_type" label="Value type">
                        <option value="percent">Percentage</option>
                        <option value="fixed">Fixed amount</option>
                        <option value="free_shipping">Free shipping</option>
                    </flux:select>
                </div>
                <div class="mt-4">
                    <flux:input wire:model="value_amount" label="Value (cents for fixed, whole number for percent)" type="number" required />
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:heading size="lg">Schedule</flux:heading>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <flux:input wire:model="starts_at" label="Start date" type="datetime-local" />
                    <flux:input wire:model="ends_at" label="End date" type="datetime-local" />
                </div>
                <div class="mt-4">
                    <flux:input wire:model="usage_limit" label="Usage limit" type="number" placeholder="Unlimited" />
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:select wire:model="status" label="Status">
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="expired">Expired</option>
                    <option value="disabled">Disabled</option>
                </flux:select>
            </div>

            <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">{{ $isEdit ? 'Save changes' : 'Create discount' }}</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </form>
</div>
