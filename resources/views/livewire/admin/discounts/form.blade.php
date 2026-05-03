<form wire:submit="save" class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl">{{ $discount ? 'Edit discount' : 'Create discount' }}</flux:heading>
            <flux:text>{{ $code ?: 'Discount configuration' }}</flux:text>
        </div>

        <div class="flex gap-2">
            <flux:button :href="route('admin.discounts.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save discount</flux:button>
        </div>
    </div>

    <section class="max-w-3xl rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-4">
            <flux:select wire:model.live="type" label="Type">
                @foreach ($types as $typeOption)
                    <option value="{{ $typeOption->value }}">{{ ucfirst($typeOption->value) }}</option>
                @endforeach
            </flux:select>

            @if ($type === 'code')
                <flux:input wire:model="code" label="Code" placeholder="SUMMER10" />
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="valueType" label="Value type">
                    @foreach ($valueTypes as $valueTypeOption)
                        <option value="{{ $valueTypeOption->value }}">{{ ucfirst(str_replace('_', ' ', $valueTypeOption->value)) }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="valueAmount" type="number" min="0" label="Value amount" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="startsAt" type="datetime-local" label="Starts" />
                <flux:input wire:model="endsAt" type="datetime-local" label="Ends" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="usageLimit" type="number" min="1" label="Usage limit" />
                <flux:select wire:model="status" label="Status">
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </section>
</form>
