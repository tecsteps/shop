<header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-zinc-200 bg-white px-4 dark:border-zinc-700 dark:bg-zinc-900 sm:px-6">
    {{-- Mobile hamburger --}}
    <flux:button variant="ghost" icon="bars-3" class="lg:hidden" @click="sidebarOpen = !sidebarOpen" />

    {{-- Store selector --}}
    <flux:dropdown>
        <flux:button variant="ghost" icon-trailing="chevron-down">
            {{ $currentStoreName }}
        </flux:button>

        <flux:menu>
            @foreach ($stores as $store)
                <flux:menu.item wire:click="switchStore({{ $store->id }})">
                    {{ $store->name }}
                </flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>

    <div class="ml-auto flex items-center gap-2">
        {{-- Dark mode toggle --}}
        <flux:button
            variant="ghost"
            x-on:click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light')"
            x-tooltip="'Toggle dark mode'"
        >
            <flux:icon x-show="!darkMode" name="moon" variant="outline" class="size-5" />
            <flux:icon x-show="darkMode" x-cloak name="sun" variant="outline" class="size-5" />
        </flux:button>

        {{-- Notification bell --}}
        <div class="relative">
            <flux:button variant="ghost" icon="bell" />
            @if ($unreadNotificationCount > 0)
                <flux:badge size="sm" color="red" class="absolute -top-1 -right-1">
                    {{ $unreadNotificationCount }}
                </flux:badge>
            @endif
        </div>

        {{-- User profile dropdown --}}
        <flux:dropdown position="bottom" align="end">
            <flux:profile name="{{ Auth::user()?->name ?? 'Admin' }}" />

            <flux:menu>
                <flux:menu.item href="{{ route('admin.settings.index') }}" wire:navigate icon="cog-6-tooth">
                    Settings
                </flux:menu.item>

                <flux:separator />

                <flux:menu.item
                    x-on:click.prevent="document.getElementById('logout-form').submit()"
                    icon="arrow-right-start-on-rectangle"
                >
                    Log out
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>

        <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="hidden">
            @csrf
        </form>
    </div>
</header>
