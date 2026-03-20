<div class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-gray-200 bg-white px-4 dark:border-gray-800 dark:bg-gray-900">
    {{-- Left: hamburger + store selector --}}
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="bars-3" class="lg:hidden" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle sidebar" />

        @if($stores->isNotEmpty())
            <flux:dropdown>
                <flux:button variant="ghost" size="sm">
                    {{ $currentStore?->name ?? 'Select Store' }}
                    <flux:icon name="chevron-down" variant="mini" class="ml-1 h-4 w-4" />
                </flux:button>
                <flux:menu>
                    @foreach($stores as $store)
                        <flux:menu.item wire:click="switchStore({{ $store->id }})">
                            {{ $store->name }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>
        @endif
    </div>

    {{-- Right: profile --}}
    <div class="flex items-center gap-3">
        @if($user)
            <flux:dropdown position="bottom" align="end">
                <flux:profile name="{{ $user->name }}" />
                <flux:menu>
                    <flux:menu.item href="{{ route('admin.settings.index') }}" icon="cog-6-tooth">
                        Settings
                    </flux:menu.item>
                    <flux:separator />
                    <flux:menu.item wire:click="logout" icon="arrow-right-start-on-rectangle">
                        Log out
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        @endif
    </div>
</div>
