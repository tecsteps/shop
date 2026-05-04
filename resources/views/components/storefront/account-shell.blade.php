@props(['customer'])

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="grid gap-8 lg:grid-cols-[16rem_minmax(0,1fr)]">
        <aside class="h-fit rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-950">
            <div>
                <h1 class="text-2xl font-semibold tracking-normal text-zinc-950 dark:text-white">My Account</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $customer->name ?: $customer->email }}</p>
            </div>

            <nav class="mt-6 flex flex-col gap-1 text-sm font-medium" aria-label="Account navigation">
                <a
                    href="{{ route('account.dashboard') }}"
                    @class([
                        'rounded-md px-3 py-2',
                        'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' => request()->routeIs('account.dashboard'),
                        'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-900 dark:hover:text-white' => ! request()->routeIs('account.dashboard'),
                    ])
                    wire:navigate
                >
                    Overview
                </a>
                <a
                    href="{{ route('account.orders.index') }}"
                    @class([
                        'rounded-md px-3 py-2',
                        'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' => request()->routeIs('account.orders.*'),
                        'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-900 dark:hover:text-white' => ! request()->routeIs('account.orders.*'),
                    ])
                    wire:navigate
                >
                    Orders
                </a>
                <a
                    href="{{ route('account.addresses.index') }}"
                    @class([
                        'rounded-md px-3 py-2',
                        'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' => request()->routeIs('account.addresses.*'),
                        'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-900 dark:hover:text-white' => ! request()->routeIs('account.addresses.*'),
                    ])
                    wire:navigate
                >
                    Addresses
                </a>
            </nav>

            <form method="POST" action="{{ route('account.logout') }}" class="mt-6">
                @csrf
                <flux:button type="submit" variant="filled" icon="arrow-right-start-on-rectangle" class="w-full justify-center" data-test="customer-logout-button">
                    Logout
                </flux:button>
            </form>
        </aside>

        <div class="min-w-0">
            {{ $slot }}
        </div>
    </div>
</section>
