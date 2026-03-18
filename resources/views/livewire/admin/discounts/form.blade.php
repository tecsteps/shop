<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ $this->isEditing ? __('Edit discount') : __('Add discount') }}</flux:heading>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 space-y-4">
                    <flux:field>
                        <flux:label>{{ __('Type') }}</flux:label>
                        <flux:select wire:model.live="type">
                            <flux:select.option value="code">{{ __('Discount code') }}</flux:select.option>
                            <flux:select.option value="automatic">{{ __('Automatic') }}</flux:select.option>
                        </flux:select>
                    </flux:field>

                    @if($type === 'code')
                        <flux:field>
                            <flux:label>{{ __('Discount code') }}</flux:label>
                            <flux:input wire:model="code" placeholder="{{ __('SUMMER20') }}" />
                            <flux:error name="code" />
                        </flux:field>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>{{ __('Value type') }}</flux:label>
                            <flux:select wire:model="valueType">
                                <flux:select.option value="percent">{{ __('Percentage') }}</flux:select.option>
                                <flux:select.option value="fixed">{{ __('Fixed amount') }}</flux:select.option>
                                <flux:select.option value="free_shipping">{{ __('Free shipping') }}</flux:select.option>
                            </flux:select>
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('Value') }}</flux:label>
                            <flux:input wire:model="valueAmount" type="number" min="0" />
                            <flux:error name="valueAmount" />
                        </flux:field>
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 space-y-4">
                    <flux:heading size="md">{{ __('Dates') }}</flux:heading>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>{{ __('Starts at') }}</flux:label>
                            <flux:input wire:model="startsAt" type="datetime-local" />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('Ends at') }}</flux:label>
                            <flux:input wire:model="endsAt" type="datetime-local" />
                            <flux:error name="endsAt" />
                        </flux:field>
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 space-y-4">
                    <flux:heading size="md">{{ __('Rules') }}</flux:heading>
                    <flux:field>
                        <flux:label>{{ __('Usage limit') }}</flux:label>
                        <flux:input wire:model="usageLimit" type="number" min="1" placeholder="{{ __('No limit') }}" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Minimum order amount (cents)') }}</flux:label>
                        <flux:input wire:model="minimumOrderAmount" type="number" min="0" />
                    </flux:field>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                    <flux:field>
                        <flux:label>{{ __('Status') }}</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                            <flux:select.option value="disabled">{{ __('Disabled') }}</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>

                @if($this->isEditing)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4">
                        <flux:text class="text-zinc-500">{{ __('Usage') }}: {{ $discount->usage_count }}{{ $discount->usage_limit ? ' / '.$discount->usage_limit : '' }}</flux:text>
                    </div>
                @endif
            </div>
        </div>

        <div class="sticky bottom-0 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700 p-4 mt-6 flex justify-end gap-4">
            <flux:button variant="ghost" :href="route('admin.discounts.index')" wire:navigate>{{ __('Discard') }}</flux:button>
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>
