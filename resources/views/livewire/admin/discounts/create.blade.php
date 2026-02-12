<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.discounts.index') }}" wire:navigate>Discounts</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Create discount</flux:heading>

    <form wire:submit="save" class="space-y-6 max-w-2xl">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="code" label="Code" placeholder="e.g. SUMMER20" required />
                <flux:input wire:model="title" label="Title" placeholder="e.g. Summer sale 20% off" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:select wire:model="type" label="Discount type">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}">{{ ucfirst($type->value) }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="valueType" label="Value type">
                    @foreach ($valueTypes as $vt)
                        <option value="{{ $vt->value }}">{{ match($vt->value) { 'percent' => 'Percentage', 'fixed' => 'Fixed amount (cents)', 'free_shipping' => 'Free shipping' } }}</option>
                    @endforeach
                </flux:select>
            </div>

            <flux:input wire:model="valueAmount" label="Value" type="number" min="0" description="For percentage: enter whole number (e.g. 20 for 20%). For fixed: enter amount in cents." required />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="minimumPurchaseAmount" label="Minimum purchase (cents)" type="number" min="0" />
                <flux:input wire:model="usageLimit" label="Usage limit" type="number" min="0" description="Leave empty for unlimited." />
            </div>

            <flux:select wire:model="status" label="Status">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ ucfirst($status->value) }}</option>
                @endforeach
            </flux:select>
        </div>

        {{-- Schedule --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <flux:heading size="lg">Schedule</flux:heading>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="startsAt" label="Starts at" type="datetime-local" />
                <flux:input wire:model="endsAt" label="Ends at" type="datetime-local" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">Create discount</flux:button>
            <flux:button href="{{ route('admin.discounts.index') }}" wire:navigate variant="ghost">Cancel</flux:button>
        </div>
    </form>
</div>
