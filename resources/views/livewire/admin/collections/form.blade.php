<div class="mx-auto max-w-3xl space-y-6">
    <div><p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Catalog</p><h1 class="mt-2 text-3xl font-bold">{{ $collection ? 'Edit collection' : 'Create collection' }}</h1></div>
    <form wire:submit="save" class="space-y-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-zinc-200">
        <flux:input wire:model="title" label="Title" required />
        <flux:input wire:model="handle" label="Handle" description="Used in the storefront URL." />
        <flux:textarea wire:model="description" label="Description" rows="4" />
        <flux:select wire:model="status" label="Status"><option value="draft">Draft</option><option value="active">Active</option><option value="archived">Archived</option></flux:select>
        <div class="flex justify-end gap-3"><flux:button variant="ghost" :href="route('admin.collections.index')" wire:navigate>Cancel</flux:button><flux:button type="submit" variant="primary">Save collection</flux:button></div>
    </form>
</div>
