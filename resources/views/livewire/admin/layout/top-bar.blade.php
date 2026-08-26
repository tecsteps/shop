<header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-zinc-200 bg-white/90 px-4 backdrop-blur sm:px-6 dark:border-zinc-700 dark:bg-zinc-900/90">
    {{-- Mobile hamburger --}}
    <flux:button
        variant="ghost"
        icon="bars-3"
        class="lg:hidden"
        aria-label="Open navigation"
        @click="sidebarOpen = true"
    />

    {{-- Store switcher --}}
    <flux:dropdown>
        <flux:button variant="ghost" icon-trailing="chevron-down" class="max-w-xs truncate">
            {{ $currentStoreName }}
        </flux:button>

        <flux:menu>
            <flux:menu.heading>Switch store</flux:menu.heading>

            @forelse ($this->stores as $store)
                <flux:menu.item
                    wire:click="switchStore('{{ $store->id }}')"
                    :icon="$store->id === app('current_store')->id ? 'check' : 'building-storefront'"
                >
                    {{ $store->name }}
                </flux:menu.item>
            @empty
                <flux:menu.item disabled>No stores available</flux:menu.item>
            @endforelse
        </flux:menu>
    </flux:dropdown>

    <flux:spacer />

    {{-- Notifications --}}
    <div class="relative">
        <flux:button variant="ghost" icon="bell" aria-label="Notifications" />
        @if ($unreadNotificationCount > 0)
            <span class="absolute -end-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-semibold text-white">
                {{ min($unreadNotificationCount, 9) }}
            </span>
        @endif
    </div>

    {{-- Profile menu --}}
    <flux:dropdown position="bottom" align="end">
        <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

        <flux:menu>
            <flux:menu.radio.group>
                <div class="p-0 text-sm font-normal">
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                        <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                            <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                        </div>
                    </div>
                </div>
            </flux:menu.radio.group>

            <flux:menu.separator />

            <flux:menu.item :href="route('admin.settings.index')" icon="cog-6-tooth" wire:navigate>
                Settings
            </flux:menu.item>

            <flux:menu.separator />

            <form method="POST" action="{{ route('admin.logout') }}" class="w-full">
                @csrf
                <flux:menu.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                    class="w-full cursor-pointer"
                >
                    Log out
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</header>
