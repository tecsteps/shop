<div>
    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('admin.discounts.index') }}" wire:navigate>Discounts</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $this->isEditing ? 'Edit' : 'Create' }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <flux:heading size="xl" class="mb-6">{{ $this->isEditing ? 'Edit Discount' : 'Create Discount' }}</flux:heading>

    <form wire:submit="save" class="max-w-2xl space-y-6">
        {{-- Type --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="md" class="mb-4">Discount Type</flux:heading>
            <flux:separator class="mb-4" />

            <div class="space-y-3">
                <flux:radio wire:model.live="type" value="code" label="Discount code" description="Customers enter a code at checkout" />
                <flux:radio wire:model.live="type" value="automatic" label="Automatic discount" description="Applied automatically at checkout" />
            </div>
        </div>

        {{-- Code input (only for code type) --}}
        @if ($type === 'code')
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="md" class="mb-4">Discount Code</flux:heading>
                <flux:separator class="mb-4" />

                <div class="flex gap-2">
                    <div class="flex-1">
                        <flux:input wire:model="code" placeholder="SUMMER20" />
                    </div>
                    <flux:button wire:click="generateCode" type="button" variant="ghost">Generate</flux:button>
                </div>
                @error('code')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        @endif

        {{-- Value --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="md" class="mb-4">Value</flux:heading>
            <flux:separator class="mb-4" />

            <div class="space-y-3 mb-4">
                <flux:radio wire:model.live="valueType" value="percent" label="Percentage" />
                <flux:radio wire:model.live="valueType" value="fixed" label="Fixed amount" />
                <flux:radio wire:model.live="valueType" value="free_shipping" label="Free shipping" />
            </div>

            @if ($valueType !== 'free_shipping')
                <flux:field>
                    <flux:label>{{ $valueType === 'percent' ? 'Percentage' : 'Amount' }}</flux:label>
                    <flux:input wire:model="valueAmount" type="number" step="{{ $valueType === 'percent' ? '1' : '0.01' }}" min="0" placeholder="{{ $valueType === 'percent' ? '10' : '5.00' }}" />
                    @error('valueAmount')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </flux:field>
            @endif
        </div>

        {{-- Conditions --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="md" class="mb-4">Conditions</flux:heading>
            <flux:separator class="mb-4" />

            <flux:field>
                <flux:label>Minimum purchase amount</flux:label>
                <flux:input wire:model="minimumPurchaseAmount" type="number" step="0.01" min="0" placeholder="0.00" />
                <flux:description>Leave empty for no minimum.</flux:description>
            </flux:field>
        </div>

        {{-- Usage limits --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="md" class="mb-4">Usage Limits</flux:heading>
            <flux:separator class="mb-4" />

            <flux:field>
                <flux:label>Total usage limit</flux:label>
                <flux:input wire:model="usageLimit" type="number" min="1" placeholder="Unlimited" />
            </flux:field>
        </div>

        {{-- Active dates --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="md" class="mb-4">Active Dates</flux:heading>
            <flux:separator class="mb-4" />

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Start date</flux:label>
                    <flux:input wire:model="startsAt" type="datetime-local" />
                    @error('startsAt')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>End date</flux:label>
                    <flux:input wire:model="endsAt" type="datetime-local" />
                    <flux:description>Leave empty for no end date.</flux:description>
                    @error('endsAt')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </flux:field>
            </div>
        </div>

        {{-- Status --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="md">Status</flux:heading>
                    <flux:text class="text-zinc-500 dark:text-zinc-400 text-sm">{{ $isActive ? 'Active' : 'Draft' }}</flux:text>
                </div>
                <flux:switch wire:model.live="isActive" />
            </div>
        </div>

        {{-- Save bar --}}
        <div class="sticky bottom-0 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 flex justify-end gap-2 shadow-lg">
            <flux:button href="{{ route('admin.discounts.index') }}" wire:navigate variant="ghost">Discard</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </form>
</div>
