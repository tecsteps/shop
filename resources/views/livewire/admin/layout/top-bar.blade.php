<div>
    <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 dark:border-gray-800 dark:bg-gray-900 sm:px-6">
        {{-- Mobile hamburger --}}
        <flux:button
            variant="ghost"
            icon="bars-3"
            class="lg:hidden"
            wire:click="$dispatch('toggle-sidebar')"
        />

        {{-- Store selector --}}
        <flux:dropdown>
            <flux:button variant="ghost" class="flex items-center gap-2">
                {{ $currentStoreName }}
                <flux:icon name="chevron-down" variant="mini" class="h-4 w-4" />
            </flux:button>

            <flux:menu>
                @foreach ($this->stores as $store)
                    <flux:menu.item wire:click="switchStore({{ $store->id }})">
                        {{ $store->name }}
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>

        <div class="ml-auto flex items-center gap-3">
            {{-- Notification bell --}}
            <div class="relative">
                <flux:button variant="ghost" icon="bell" />
                @if ($unreadNotificationCount > 0)
                    <flux:badge size="sm" color="red" class="absolute -right-1 -top-1">
                        {{ $unreadNotificationCount }}
                    </flux:badge>
                @endif
            </div>

            {{-- User profile dropdown --}}
            <flux:dropdown align="end">
                <flux:profile
                    :name="auth()->user()?->name ?? 'Admin'"
                />

                <flux:menu>
                    <flux:menu.item href="{{ route('admin.settings.index') }}" wire:navigate icon="cog-6-tooth">
                        Settings
                    </flux:menu.item>

                    <flux:separator />

                    <flux:menu.item
                        wire:click="$dispatch('admin-logout')"
                        icon="arrow-right-start-on-rectangle"
                    >
                        Log out
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </header>
</div>
