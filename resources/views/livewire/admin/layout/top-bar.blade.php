<header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-zinc-200 bg-white px-4 sm:px-6 lg:px-8 dark:border-zinc-800 dark:bg-zinc-900">
    {{-- Hamburger (mobile only) --}}
    <flux:button icon="bars-3" variant="ghost" class="lg:hidden" @click="sidebarOpen = true" aria-label="Open navigation menu" />

    {{-- Store selector --}}
    <flux:dropdown>
        <flux:button variant="ghost" icon-trailing="chevron-down" class="max-w-48 truncate font-semibold">
            {{ $currentStore->name }}
        </flux:button>

        <flux:menu>
            @foreach ($stores as $store)
                <flux:menu.item wire:click="switchStore({{ $store->id }})" icon="{{ $store->id === $currentStore->id ? 'check' : 'building-storefront' }}">
                    {{ $store->name }}
                </flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>

    <flux:spacer />

    {{-- Notifications --}}
    <div class="relative">
        <flux:button icon="bell" variant="ghost" aria-label="Notifications" />
        @if ($unreadNotificationCount > 0)
            <flux:badge size="sm" color="red" class="pointer-events-none absolute -top-1 -right-1">
                {{ $unreadNotificationCount }}
            </flux:badge>
        @endif
    </div>

    {{-- User profile --}}
    <flux:dropdown position="bottom" align="end">
        <flux:profile :name="$user->name" :initials="$user->initials()" />

        <flux:menu>
            <div class="px-3 py-2">
                <flux:text class="font-semibold">{{ $user->name }}</flux:text>
                <flux:text class="text-xs">{{ $user->email }}</flux:text>
                @if ($currentRole !== null)
                    <flux:badge size="sm" color="zinc" class="mt-1">{{ $currentRole->value }}</flux:badge>
                @endif
            </div>

            <flux:menu.separator />

            @if (Route::has('admin.settings.index'))
                <flux:menu.item icon="cog-6-tooth" :href="route('admin.settings.index')" wire:navigate>Settings</flux:menu.item>
                <flux:menu.separator />
            @endif

            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle">Log out</flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</header>
