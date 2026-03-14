<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="lg">Domains</flux:heading>
        <flux:button variant="primary" wire:click="$set('showAddModal', true)">Add domain</flux:button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="pb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Hostname</th>
                    <th class="pb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Type</th>
                    <th class="pb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Primary</th>
                    <th class="pb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($domains as $domain)
                    <tr wire:key="domain-{{ $domain->id }}">
                        <td class="py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $domain->hostname }}</td>
                        <td class="py-3">
                            <flux:badge size="sm">{{ ucfirst($domain->type->value) }}</flux:badge>
                        </td>
                        <td class="py-3">
                            @if ($domain->is_primary)
                                <flux:badge size="sm" color="green">Primary</flux:badge>
                            @endif
                        </td>
                        <td class="py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if (!$domain->is_primary)
                                    <flux:button size="sm" variant="ghost" wire:click="setPrimary({{ $domain->id }})">Set Primary</flux:button>
                                    <flux:button size="sm" variant="ghost" wire:click="removeDomain({{ $domain->id }})" wire:confirm="Are you sure you want to remove this domain?">
                                        <flux:icon name="trash" class="size-4 text-red-500" />
                                    </flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            No domains configured.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add domain modal --}}
    <flux:modal wire:model="showAddModal" name="add-domain" class="max-w-md">
        <form wire:submit="addDomain" class="space-y-4">
            <flux:heading size="lg">Add domain</flux:heading>

            <flux:input
                wire:model="newHostname"
                label="Hostname"
                placeholder="shop.example.com"
                required
            />
            @error('newHostname')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <flux:select wire:model="newType" label="Type">
                <option value="storefront">Storefront</option>
                <option value="admin">Admin</option>
                <option value="api">API</option>
            </flux:select>

            <div class="flex justify-end gap-3 pt-4">
                <flux:button variant="ghost" wire:click="$set('showAddModal', false)">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Add domain</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
