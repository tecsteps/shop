<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Staff</flux:heading>
        <flux:button variant="primary" wire:click="openInvite">Invite staff</flux:button>
    </div>

    <div class="flex flex-wrap gap-2 border-b border-neutral-200 pb-2 dark:border-neutral-800">
        <a href="{{ url('/admin/settings') }}" class="rounded-md px-3 py-1 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800">General</a>
        <a href="{{ url('/admin/settings/shipping') }}" class="rounded-md px-3 py-1 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800">Shipping</a>
        <a href="{{ url('/admin/settings/taxes') }}" class="rounded-md px-3 py-1 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800">Taxes</a>
        <a href="{{ url('/admin/settings/staff') }}" class="rounded-md px-3 py-1 text-sm font-medium bg-neutral-100 dark:bg-neutral-800">Staff</a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase text-neutral-500">
                <tr>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Email</th>
                    <th class="px-4 py-2">Role</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $member)
                    <tr wire:key="staff-{{ $member->id }}" class="border-t border-neutral-100 dark:border-neutral-800">
                        <td class="px-4 py-2">{{ $member->name }}</td>
                        <td class="px-4 py-2">{{ $member->email }}</td>
                        <td class="px-4 py-2"><flux:badge size="sm">{{ $member->pivot->role }}</flux:badge></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-neutral-500">No staff assigned yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal wire:model="showInviteModal" name="invite-staff">
        <div class="space-y-4">
            <flux:heading size="lg">Invite staff</flux:heading>
            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" wire:model="inviteEmail" />
                <flux:error name="inviteEmail" />
            </flux:field>
            <flux:field>
                <flux:label>Role</flux:label>
                <flux:select wire:model="inviteRole">
                    @foreach ($roles as $role)
                        <flux:select.option value="{{ $role }}">{{ ucfirst($role) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showInviteModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="invite">Invite</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
