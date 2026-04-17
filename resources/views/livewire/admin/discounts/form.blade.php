<div>
    <div class="mb-6">
        <flux:heading size="xl" level="1">{{ $this->isEditing ? 'Edit discount' : 'Create discount' }}</flux:heading>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                {{-- Type --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                    <flux:select wire:model.live="type" label="Discount type">
                        <flux:select.option value="code">Discount code</flux:select.option>
                        <flux:select.option value="automatic">Automatic discount</flux:select.option>
                    </flux:select>

                    @if($type === 'code')
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <flux:input wire:model="code" label="Code" placeholder="SAVE10" />
                                @error('code') <flux:text class="mt-1 text-sm text-red-500">{{ $message }}</flux:text> @enderror
                            </div>
                            <div class="pt-7">
                                <flux:button type="button" variant="ghost" wire:click="generateCode">Generate</flux:button>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Value --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                    <flux:select wire:model.live="valueType" label="Value type">
                        <flux:select.option value="percent">Percentage</flux:select.option>
                        <flux:select.option value="fixed">Fixed amount</flux:select.option>
                        <flux:select.option value="free_shipping">Free shipping</flux:select.option>
                    </flux:select>

                    @if($valueType !== 'free_shipping')
                        <flux:input type="number" wire:model="valueAmount" label="{{ $valueType === 'percent' ? 'Percentage' : 'Amount (cents)' }}" min="0" />
                    @endif
                </div>

                {{-- Conditions --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                    <flux:heading size="md">Conditions</flux:heading>
                    <div>
                        <flux:input type="number" wire:model="minimumPurchaseAmount" label="Minimum purchase amount (cents)" min="0" />
                        <flux:text class="mt-1 text-xs text-zinc-500">Leave empty for no minimum</flux:text>
                    </div>
                    <div>
                        <flux:input type="number" wire:model="usageLimit" label="Total usage limit" min="0" />
                        <flux:text class="mt-1 text-xs text-zinc-500">Leave empty for unlimited usage</flux:text>
                    </div>
                </div>

                {{-- Dates --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                    <flux:heading size="md">Active dates</flux:heading>
                    <flux:input type="datetime-local" wire:model="startsAt" label="Start date" />
                    <div>
                        <flux:input type="datetime-local" wire:model="endsAt" label="End date" />
                        <flux:text class="mt-1 text-xs text-zinc-500">Leave empty for no end date</flux:text>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:select wire:model="status" label="Status">
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="disabled">Disabled</flux:select.option>
                        <flux:select.option value="draft">Draft</flux:select.option>
                    </flux:select>
                </div>
            </div>
        </div>

        <div class="sticky bottom-0 mt-6 flex items-center justify-end gap-2 border-t border-zinc-200 bg-white px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:button variant="ghost" href="{{ route('admin.discounts.index') }}" wire:navigate>Discard</flux:button>
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
