<div class="mx-auto max-w-3xl p-8 space-y-4">
    <flux:heading size="xl">Account dashboard</flux:heading>
    @auth('customer')
        <flux:text>Welcome back, {{ auth('customer')->user()->first_name ?? auth('customer')->user()->email }}.</flux:text>
    @endauth
    <div class="grid gap-3 sm:grid-cols-3">
        <a href="{{ route('account.orders.index') }}" class="rounded-lg border p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800">
            <flux:heading size="sm">Your orders</flux:heading>
            <flux:text size="sm">Track and manage previous purchases</flux:text>
        </a>
        <a href="{{ route('account.addresses.index') }}" class="rounded-lg border p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800">
            <flux:heading size="sm">Addresses</flux:heading>
            <flux:text size="sm">Manage shipping and billing addresses</flux:text>
        </a>
        <form method="POST" action="{{ route('account.logout') }}" class="rounded-lg border p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800">
            @csrf
            <button type="submit" class="w-full text-left">
                <flux:heading size="sm">Sign out</flux:heading>
                <flux:text size="sm">End your current session</flux:text>
            </button>
        </form>
    </div>
</div>
