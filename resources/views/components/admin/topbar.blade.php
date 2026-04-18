<header class="flex items-center justify-between border-b border-zinc-200 bg-white px-6 py-3 dark:border-zinc-800 dark:bg-zinc-900">
    <div class="flex items-center gap-3">
        <flux:heading size="sm">Admin</flux:heading>
    </div>
    <div class="flex items-center gap-3 text-sm">
        @auth
            <flux:text>{{ auth()->user()->email }}</flux:text>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <flux:button type="submit" variant="ghost" size="sm">Sign out</flux:button>
            </form>
        @endauth
    </div>
</header>
