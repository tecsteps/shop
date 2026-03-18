<nav class="w-full lg:w-56 shrink-0">
    <ul class="flex flex-row gap-1 overflow-x-auto lg:flex-col lg:overflow-visible">
        <li>
            <a href="{{ route('storefront.account') }}"
               class="block rounded-md px-3 py-2 text-sm font-medium whitespace-nowrap {{ request()->routeIs('storefront.account') && !request()->routeIs('storefront.account.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/50 dark:hover:text-white' }}">
                Dashboard
            </a>
        </li>
        <li>
            <a href="{{ route('storefront.account.orders') }}"
               class="block rounded-md px-3 py-2 text-sm font-medium whitespace-nowrap {{ request()->routeIs('storefront.account.orders*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/50 dark:hover:text-white' }}">
                Orders
            </a>
        </li>
        <li>
            <a href="{{ route('storefront.account.addresses') }}"
               class="block rounded-md px-3 py-2 text-sm font-medium whitespace-nowrap {{ request()->routeIs('storefront.account.addresses') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/50 dark:hover:text-white' }}">
                Addresses
            </a>
        </li>
        <li>
            <form method="POST" action="{{ route('storefront.logout') }}">
                @csrf
                <button type="submit"
                        class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium whitespace-nowrap text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/50 dark:hover:text-white">
                    Logout
                </button>
            </form>
        </li>
    </ul>
</nav>
