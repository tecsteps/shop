@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $title ? $title . ' - Admin' : 'Admin' }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-gray-50 antialiased dark:bg-zinc-900" x-data="{ sidebarOpen: false }">
        {{-- Mobile sidebar overlay --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
             x-transition.opacity class="fixed inset-0 z-40 bg-black/50 lg:hidden"></div>

        {{-- Sidebar --}}
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-gray-200 bg-white transition-transform duration-200 lg:translate-x-0 dark:border-zinc-700 dark:bg-zinc-900"
        >
            {{-- Store name --}}
            <div class="flex h-16 items-center gap-2 border-b border-gray-200 px-6 dark:border-zinc-700">
                <a href="{{ route('admin.dashboard') }}" class="text-lg font-bold text-gray-900 dark:text-white">
                    @php $store = app()->bound('current_store') ? app('current_store') : null; @endphp
                    {{ $store?->name ?? config('app.name') }}
                </a>
                <button @click="sidebarOpen = false" class="ml-auto lg:hidden">
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <flux:navlist>
                    <flux:navlist.item href="{{ route('admin.dashboard') }}" icon="chart-bar" :current="request()->routeIs('admin.dashboard')">
                        Dashboard
                    </flux:navlist.item>
                    <flux:navlist.item href="{{ route('admin.products.index') }}" icon="cube" :current="request()->routeIs('admin.products.*')">
                        Products
                    </flux:navlist.item>
                    <flux:navlist.item href="{{ route('admin.collections.index') }}" icon="rectangle-group" :current="request()->routeIs('admin.collections.*')">
                        Collections
                    </flux:navlist.item>
                    <flux:navlist.item href="{{ route('admin.inventory.index') }}" icon="archive-box" :current="request()->routeIs('admin.inventory.*')">
                        Inventory
                    </flux:navlist.item>
                    <flux:navlist.item href="{{ route('admin.orders.index') }}" icon="shopping-bag" :current="request()->routeIs('admin.orders.*')">
                        Orders
                    </flux:navlist.item>
                    <flux:navlist.item href="{{ route('admin.customers.index') }}" icon="users" :current="request()->routeIs('admin.customers.*')">
                        Customers
                    </flux:navlist.item>
                    <flux:navlist.item href="{{ route('admin.discounts.index') }}" icon="tag" :current="request()->routeIs('admin.discounts.*')">
                        Discounts
                    </flux:navlist.item>
                    <flux:navlist.item href="{{ route('admin.analytics.index') }}" icon="chart-pie" :current="request()->routeIs('admin.analytics.*')">
                        Analytics
                    </flux:navlist.item>
                </flux:navlist>

                <flux:navlist>
                    <flux:navlist.group heading="Content" expandable>
                        <flux:navlist.item href="{{ route('admin.pages.index') }}" icon="document-text" :current="request()->routeIs('admin.pages.*')">
                            Pages
                        </flux:navlist.item>
                        <flux:navlist.item href="{{ route('admin.navigation.index') }}" icon="bars-3" :current="request()->routeIs('admin.navigation.*')">
                            Navigation
                        </flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:navlist>
                    <flux:navlist.group heading="Online Store" expandable>
                        <flux:navlist.item href="{{ route('admin.themes.index') }}" icon="paint-brush" :current="request()->routeIs('admin.themes.*')">
                            Themes
                        </flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:navlist>
                    <flux:navlist.item href="{{ route('admin.settings.index') }}" icon="cog-6-tooth" :current="request()->routeIs('admin.settings.*')">
                        Settings
                    </flux:navlist.item>
                    <flux:navlist.item href="{{ route('admin.developers.index') }}" icon="code-bracket" :current="request()->routeIs('admin.developers.*')">
                        Developers
                    </flux:navlist.item>
                </flux:navlist>
            </nav>
        </aside>

        {{-- Main content --}}
        <div class="lg:pl-64">
            {{-- Top bar --}}
            <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 sm:px-6 dark:border-zinc-700 dark:bg-zinc-900">
                <button @click="sidebarOpen = true" class="lg:hidden">
                    <svg class="h-6 w-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                </button>

                @if($title)
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h1>
                @endif

                <div class="ml-auto flex items-center gap-3">
                    <flux:dropdown>
                        <flux:button variant="ghost" icon-trailing="chevron-down">
                            {{ auth()->user()?->name ?? 'Admin' }}
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item icon="arrow-left-start-on-rectangle"
                                onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                                Logout
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                    <form id="admin-logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
                        @csrf
                    </form>
                </div>
            </header>

            {{-- Page content --}}
            <main class="p-4 sm:p-6 lg:p-8">
                @if(session('success'))
                    <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">
                        {{ session('success') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>

        @fluxScripts
    </body>
</html>
