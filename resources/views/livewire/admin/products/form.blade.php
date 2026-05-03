<form wire:submit="save" class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl">{{ $this->product ? 'Edit product' : 'Create product' }}</flux:heading>
            <flux:text>{{ $title ?: 'Product details' }}</flux:text>
        </div>

        <div class="flex gap-2">
            <flux:button :href="route('admin.products.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save product</flux:button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="space-y-6">
            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="grid gap-4">
                    <flux:input wire:model="title" label="Title" required />
                    <flux:textarea wire:model="descriptionHtml" label="Description" rows="8" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:input wire:model="vendor" label="Vendor" />
                        <flux:input wire:model="productType" label="Product type" />
                    </div>
                    <flux:input wire:model="tags" label="Tags" placeholder="linen, summer, featured" />
                </div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg">Default variant</flux:heading>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model="priceAmount" type="number" min="0" label="Price cents" />
                    <flux:input wire:model="sku" label="SKU" />
                    <flux:input wire:model="quantityOnHand" type="number" min="0" label="Stock" />
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:select wire:model="status" label="Status">
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</option>
                    @endforeach
                </flux:select>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:input wire:model="handle" label="Handle" />
            </section>
        </aside>
    </div>
</form>
