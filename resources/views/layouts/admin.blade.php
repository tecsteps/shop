<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" x-data="{
    darkMode: localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
    sidebarOpen: false,
}" x-init="$watch('darkMode', val => { localStorage.setItem('theme', val ? 'dark' : 'light') })" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($title ?? 'Dashboard') . ' - Admin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="h-full bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">

    {{-- Toast Notifications --}}
    <div x-data="{
        toasts: [],
        add(event) {
            const id = Date.now();
            this.toasts.push({ id, type: event.detail.type || 'info', message: event.detail.message });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id) }, 5000);
        }
    }" @toast.window="add($event)" class="fixed right-4 top-4 z-[100] space-y-2">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-x-full opacity-0"
                 x-transition:enter-end="translate-x-0 opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0 opacity-100"
                 x-transition:leave-end="translate-x-full opacity-0"
                 class="w-80 rounded-lg bg-white p-4 shadow-lg dark:bg-gray-800"
                 :class="{
                     'border-l-4 border-green-500': toast.type === 'success',
                     'border-l-4 border-red-500': toast.type === 'error',
                     'border-l-4 border-blue-500': toast.type === 'info',
                 }">
                <p class="text-sm" x-text="toast.message"></p>
            </div>
        </template>
    </div>

    {{-- Sidebar --}}
    @php
        $currentRoute = request()->route()?->getName() ?? '';
    @endphp

    {{-- Mobile backdrop --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-black/50 lg:hidden"></div>

    {{-- Sidebar panel --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 w-64 transform overflow-y-auto border-r border-gray-200 bg-white transition-transform duration-300 ease-in-out dark:border-gray-800 dark:bg-gray-900 lg:translate-x-0">
        <div class="flex h-16 items-center gap-2 border-b border-gray-200 px-6 dark:border-gray-800">
            <span class="text-lg font-bold">
                @if (app()->bound('current_store'))
                    {{ app('current_store')->name }}
                @else
                    {{ config('app.name') }}
                @endif
            </span>
        </div>

        <nav class="space-y-1 p-4">
            <a href="{{ route('admin.dashboard') }}" wire:navigate
               @class(['flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium', 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.dashboard'), 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => !str_starts_with($currentRoute, 'admin.dashboard')])>
                <flux:icon.chart-bar class="size-5" />
                Dashboard
            </a>

            <div class="pt-4">
                <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Products</p>
                <a href="{{ route('admin.products.index') }}" wire:navigate
                   @class(['flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium mt-1', 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.products'), 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => !str_starts_with($currentRoute, 'admin.products')])>
                    <flux:icon.cube class="size-5" />
                    Products
                </a>
                <a href="{{ route('admin.collections.index') }}" wire:navigate
                   @class(['flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium', 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.collections'), 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => !str_starts_with($currentRoute, 'admin.collections')])>
                    <flux:icon.rectangle-stack class="size-5" />
                    Collections
                </a>
            </div>

            <flux:separator />

            <div class="pt-2">
                <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Orders</p>
                <a href="{{ route('admin.orders.index') }}" wire:navigate
                   @class(['flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium mt-1', 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.orders'), 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => !str_starts_with($currentRoute, 'admin.orders')])>
                    <flux:icon.shopping-bag class="size-5" />
                    Orders
                </a>
            </div>

            <flux:separator />

            <div class="pt-2">
                <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Customers</p>
                <a href="{{ route('admin.customers.index') }}" wire:navigate
                   @class(['flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium mt-1', 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.customers'), 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => !str_starts_with($currentRoute, 'admin.customers')])>
                    <flux:icon.users class="size-5" />
                    Customers
                </a>
            </div>

            <flux:separator />

            <div class="pt-2">
                <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Discounts</p>
                <a href="{{ route('admin.discounts.index') }}" wire:navigate
                   @class(['flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium mt-1', 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.discounts'), 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => !str_starts_with($currentRoute, 'admin.discounts')])>
                    <flux:icon.tag class="size-5" />
                    Discounts
                </a>
            </div>

            <flux:separator />

            <a href="{{ route('admin.settings.index') }}" wire:navigate
               @class(['flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium', 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.settings'), 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => !str_starts_with($currentRoute, 'admin.settings')])>
                <flux:icon.cog-6-tooth class="size-5" />
                Settings
            </a>
        </nav>
    </aside>

    {{-- Main content --}}
    <div class="lg:pl-64">
        {{-- Top bar --}}
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 dark:border-gray-800 dark:bg-gray-900 sm:px-6">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = !sidebarOpen" class="rounded p-2 hover:bg-gray-100 dark:hover:bg-gray-800 lg:hidden" aria-label="Toggle sidebar">
                    <flux:icon.bars-3 class="size-5" />
                </button>

                @if (app()->bound('current_store'))
                    <flux:text class="hidden text-sm sm:block">{{ app('current_store')->name }}</flux:text>
                @endif
            </div>

            <div class="flex items-center gap-3">
                <button @click="darkMode = !darkMode" class="rounded p-2 hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="Toggle dark mode">
                    <flux:icon.sun x-show="darkMode" class="size-5" />
                    <flux:icon.moon x-show="!darkMode" x-cloak class="size-5" />
                </button>

                <flux:dropdown>
                    <flux:profile :name="auth()->user()?->name ?? 'Admin'" :initials="auth()->user()?->initials() ?? 'A'" />
                    <flux:menu>
                        <flux:menu.item href="{{ route('admin.settings.index') }}" icon="cog-6-tooth">Settings</flux:menu.item>
                        <flux:separator />
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <flux:menu.item type="submit" icon="arrow-right-start-on-rectangle">Log out</flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </header>

        {{-- Page content --}}
        <main class="p-4 sm:p-6 lg:p-8">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
