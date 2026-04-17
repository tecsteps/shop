<div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $mode === 'create' ? 'New discount' : 'Edit discount' }}</flux:heading>
        <flux:button :href="route('admin.discounts.index')" variant="ghost" wire:navigate>Back</flux:button>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Details</flux:heading>
                <div class="mt-4 space-y-4">
                    <flux:field>
                        <flux:label>Type</flux:label>
                        <flux:select wire:model.live="type">
                            <flux:select.option value="code">Code</flux:select.option>
                            <flux:select.option value="automatic">Automatic</flux:select.option>
                        </flux:select>
                    </flux:field>

                    @if ($type === 'code')
                        <flux:field>
                            <flux:label>Code</flux:label>
                            <flux:input wire:model="code" placeholder="SAVE20" />
                            <flux:error name="code" />
                        </flux:field>
                    @endif

                    <flux:field>
                        <flux:label>Value type</flux:label>
                        <flux:select wire:model.live="valueType">
                            <flux:select.option value="percent">Percentage</flux:select.option>
                            <flux:select.option value="fixed">Fixed amount</flux:select.option>
                            <flux:select.option value="free_shipping">Free shipping</flux:select.option>
                        </flux:select>
                    </flux:field>

                    @if ($valueType !== 'free_shipping')
                        <flux:field>
                            <flux:label>Value {{ $valueType === 'percent' ? '(%)' : '(cents)' }}</flux:label>
                            <flux:input type="number" wire:model="valueAmount" min="0" />
                            <flux:error name="valueAmount" />
                        </flux:field>
                    @endif

                    <flux:field>
                        <flux:label>Minimum purchase (cents)</flux:label>
                        <flux:input type="number" wire:model="minimumPurchase" min="0" />
                    </flux:field>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Active dates</flux:heading>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Starts at</flux:label>
                        <flux:input type="datetime-local" wire:model="startsAt" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Ends at</flux:label>
                        <flux:input type="datetime-local" wire:model="endsAt" />
                    </flux:field>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Status</flux:heading>
                <div class="mt-4 space-y-4">
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="draft">Draft</flux:select.option>
                            <flux:select.option value="active">Active</flux:select.option>
                            <flux:select.option value="disabled">Disabled</flux:select.option>
                            <flux:select.option value="expired">Expired</flux:select.option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Usage limit</flux:label>
                        <flux:input type="number" wire:model="usageLimit" min="1" />
                    </flux:field>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 lg:col-span-3">
            <flux:button variant="ghost" :href="route('admin.discounts.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save discount</flux:button>
        </div>
    </form>
</div>
