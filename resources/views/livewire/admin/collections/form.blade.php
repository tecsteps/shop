<div class="space-y-4">
    <flux:heading size="xl">{{ $collection && $collection->exists ? 'Edit collection' : 'New collection' }}</flux:heading>
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif
    <form wire:submit="save" class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-3 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900 lg:col-span-2">
            <flux:input wire:model="title" label="Title" required />
            <flux:input wire:model="handle" label="Handle (optional)" />
            <flux:textarea wire:model="description_html" label="Description" rows="5" />

            <flux:heading size="sm">Products</flux:heading>
            <flux:input wire:model.live.debounce.300ms="productSearch" placeholder="Search products to add..." />
            @foreach ($searchResults as $result)
                <div wire:key="result-{{ $result->id }}" class="flex items-center justify-between rounded border border-zinc-200 p-2 text-sm dark:border-zinc-800">
                    <span>{{ $result->title }}</span>
                    <flux:button size="sm" wire:click="addProduct({{ $result->id }})">Add</flux:button>
                </div>
            @endforeach

            <div class="space-y-1">
                @foreach ($product_ids as $pid)
                    @php($prod = $pickedProducts->get($pid))
                    @if ($prod)
                        <div wire:key="picked-{{ $pid }}" class="flex items-center justify-between rounded bg-zinc-100 px-3 py-2 text-sm dark:bg-zinc-800">
                            <span>{{ $prod->title }}</span>
                            <div class="flex gap-1">
                                <flux:button size="xs" wire:click="moveUp({{ $pid }})">Up</flux:button>
                                <flux:button size="xs" wire:click="moveDown({{ $pid }})">Down</flux:button>
                                <flux:button size="xs" variant="danger" wire:click="removeProduct({{ $pid }})">Remove</flux:button>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
        <div class="space-y-3 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:select wire:model="type" label="Type">
                @foreach ($types as $case)
                    <flux:select.option value="{{ $case->value }}">{{ ucfirst($case->value) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="status" label="Status">
                @foreach ($statuses as $case)
                    <flux:select.option value="{{ $case->value }}">{{ ucfirst($case->value) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:button type="submit" variant="primary" class="w-full">Save</flux:button>
        </div>
    </form>
</div>
