<div>
<flux:header class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
    {{-- Mobile sidebar toggle. --}}
    <flux:sidebar.toggle class="lg:hidden" icon="bars-3" inset="left" />

    {{-- Store selector. --}}
    @if ($this->stores->count() > 1)
        <flux:dropdown>
            <flux:button variant="ghost" icon-trailing="chevron-down" size="sm" data-test="store-selector">
                {{ $this->currentStoreName }}
            </flux:button>

            <flux:menu>
                @foreach ($this->stores as $store)
                    <flux:menu.item
                        wire:click="switchStore({{ $store->id }})"
                        :icon="$store->name === $this->currentStoreName ? 'check' : null"
                    >
                        {{ $store->name }}
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>
    @else
        <flux:text class="font-medium">{{ $this->currentStoreName }}</flux:text>
    @endif

    <flux:spacer />

    {{-- Notification bell. --}}
    <flux:button variant="ghost" size="sm" icon="bell" class="relative" :aria-label="__('Notifications')">
        @if ($unreadNotificationCount > 0)
            <flux:badge size="sm" color="red" class="absolute -right-1 -top-1">{{ $unreadNotificationCount }}</flux:badge>
        @endif
    </flux:button>

    {{-- User profile menu. --}}
    <flux:dropdown position="bottom" align="end">
        <flux:profile
            :name="auth('web')->user()?->name"
            :initials="auth('web')->user()?->initials()"
            icon-trailing="chevron-down"
            data-test="user-menu"
        />

        <flux:menu>
            <div class="flex items-center gap-2 px-2 py-1.5">
                <flux:avatar :name="auth('web')->user()?->name" :initials="auth('web')->user()?->initials()" size="sm" />
                <div class="grid flex-1 text-start text-sm leading-tight">
                    <flux:heading class="truncate">{{ auth('web')->user()?->name }}</flux:heading>
                    <flux:text class="truncate text-xs">{{ auth('web')->user()?->email }}</flux:text>
                </div>
            </div>

            <flux:menu.separator />

            <flux:menu.item :href="route('admin.settings.index')" icon="cog-6-tooth" wire:navigate>
                {{ __('Settings') }}
            </flux:menu.item>

            <flux:menu.separator />

            <form method="POST" action="{{ route('admin.logout') }}" class="w-full">
                @csrf
                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="admin-logout">
                    {{ __('Log out') }}
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</flux:header>
</div>
