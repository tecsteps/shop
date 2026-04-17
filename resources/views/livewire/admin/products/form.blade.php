<div class="mx-auto max-w-3xl">
    <flux:heading size="xl" class="mb-6">{{ $product ? 'Edit product' : 'New product' }}</flux:heading>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" class="mb-4" heading="{{ session('success') }}"></flux:callout>
    @endif

    <form wire:submit="save" class="flex flex-col gap-6">
        <div class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
            <flux:heading size="lg" class="mb-4">Details</flux:heading>
            <div class="flex flex-col gap-4">
                <flux:input label="Title" wire:model="title" required />
                <flux:input label="Handle (URL slug)" wire:model="handle" placeholder="Auto-generated if empty" />
                <flux:textarea label="Description" wire:model="descriptionHtml" rows="5" />
                <div class="grid grid-cols-2 gap-3">
                    <flux:input label="Vendor" wire:model="vendor" />
                    <flux:input label="Type" wire:model="productType" />
                </div>
                <flux:select label="Status" wire:model="status">
                    <flux:select.option value="draft">Draft</flux:select.option>
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="archived">Archived</flux:select.option>
                </flux:select>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
            <flux:heading size="lg" class="mb-4">Pricing & inventory</flux:heading>
            <div class="grid grid-cols-2 gap-3">
                <flux:input type="number" label="Price (in cents)" wire:model="priceAmount" />
                <flux:input type="number" label="Quantity on hand" wire:model="quantityOnHand" />
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <flux:button type="button" variant="ghost" href="{{ route('admin.products.index') }}" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
