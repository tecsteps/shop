<header class="sticky top-0 z-20 border-b border-zinc-200 bg-white/95 px-4 py-3 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-3">
            <div class="lg:hidden">
                <x-app-logo-icon class="size-8" />
            </div>

            <flux:dropdown>
                <flux:button variant="ghost" icon-trailing="chevron-down" class="max-w-[14rem] truncate">
                    {{ $currentStoreName }}
                </flux:button>

                <flux:menu>
                    @foreach ($stores as $store)
                        <flux:menu.item wire:key="admin-store-{{ $store->id }}" wire:click="switchStore({{ $store->id }})">
                            {{ $store->name }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative">
                <flux:button variant="ghost" icon="bell" aria-label="Notifications" />
                @if ($unreadNotificationCount > 0)
                    <flux:badge size="sm" color="red" class="absolute -right-1 -top-1">{{ $unreadNotificationCount }}</flux:badge>
                @endif
            </div>

            <flux:dropdown position="bottom" align="end">
                <flux:profile :name="$user->name" :initials="$user->initials()" icon-trailing="chevron-down" />

                <flux:menu>
                    <flux:menu.item :href="route('admin.settings.index')" icon="cog-6-tooth" wire:navigate>
                        Settings
                    </flux:menu.item>
                    <flux:menu.separator />
                    <flux:menu.item as="button" icon="arrow-right-start-on-rectangle" wire:click="logout">
                        Log out
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>
</header>
