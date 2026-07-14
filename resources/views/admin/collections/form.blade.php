<form wire:submit="save" class="space-y-6 pb-20">
    <x-admin.page-header :title="$collection ? $collection->title : 'Add collection'" description="Curate products and control how this collection appears." />
    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
        <div class="space-y-6">
            <x-admin.card><div class="space-y-5"><flux:input wire:model.blur="title" label="Title" required /><flux:input wire:model="handle" label="URL handle" required /><flux:textarea wire:model="descriptionHtml" label="Description" rows="7" /></div></x-admin.card>
            <x-admin.card title="Products" description="Search for products, then add them to this collection.">
                <div class="relative"><flux:input wire:model.live.debounce.300ms="productSearch" icon="magnifying-glass" placeholder="Search products…" aria-label="Search products to assign" />
                    @if($this->searchResults->isNotEmpty())<div class="absolute z-10 mt-2 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900">@foreach($this->searchResults as $result)<div class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800"><span class="truncate text-sm font-medium">{{ $result->title }}</span><flux:button type="button" size="sm" wire:click="addProduct({{ $result->id }})">Add</flux:button></div>@endforeach</div>@endif
                </div>
                <div class="mt-5 space-y-2">
                    @forelse($this->assignedProducts as $assigned)<div class="flex min-h-14 items-center gap-2 rounded-xl border border-slate-200 px-3 dark:border-slate-700" wire:key="assigned-product-{{ $assigned->id }}"><flux:icon.bars-2 class="size-4 text-slate-400" /><div class="grid size-9 place-items-center overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800">@if($assigned->media->first())<img src="{{ Storage::disk('public')->url($assigned->media->first()->storage_key) }}" alt="" class="h-full w-full object-cover">@else<flux:icon.cube class="size-4" />@endif</div><span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $assigned->title }}</span><flux:button type="button" variant="ghost" size="sm" icon="arrow-up" wire:click="moveProduct({{ $assigned->id }}, 'up')" aria-label="Move {{ $assigned->title }} up" /><flux:button type="button" variant="ghost" size="sm" icon="arrow-down" wire:click="moveProduct({{ $assigned->id }}, 'down')" aria-label="Move {{ $assigned->title }} down" /><flux:button type="button" variant="ghost" size="sm" icon="x-mark" wire:click="removeProduct({{ $assigned->id }})" aria-label="Remove {{ $assigned->title }}" /></div>
                    @empty<p class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-slate-700">No products assigned yet.</p>@endforelse
                </div>
            </x-admin.card>
        </div>
        <x-admin.card title="Status" class="xl:sticky xl:top-24"><flux:select wire:model="status" label="Collection status"><flux:select.option value="active">Active</flux:select.option><flux:select.option value="archived">Archived</flux:select.option></flux:select></x-admin.card>
    </div>
    <x-admin.sticky-save-bar :discard-url="url('/admin/collections')" :dirty-only="false" />
</form>
