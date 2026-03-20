<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <flux:sidebar.brand
                    href="{{ route('admin.dashboard') }}"
                    name="{{ config('app.name') }} Admin"
                    wire:navigate
                />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="chart-bar" href="{{ route('admin.dashboard') }}" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                    Dashboard
                </flux:sidebar.item>

                <flux:sidebar.group expandable heading="Products" class="grid">
                    <flux:sidebar.item icon="cube" href="{{ route('admin.products.index') }}" :current="request()->routeIs('admin.products.*')" wire:navigate>
                        Products
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="rectangle-stack" href="{{ route('admin.collections.index') }}" :current="request()->routeIs('admin.collections.*')" wire:navigate>
                        Collections
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="archive-box" href="{{ route('admin.inventory.index') }}" :current="request()->routeIs('admin.inventory.*')" wire:navigate>
                        Inventory
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group expandable heading="Orders" class="grid">
                    <flux:sidebar.item icon="shopping-bag" href="{{ route('admin.orders.index') }}" :current="request()->routeIs('admin.orders.*')" wire:navigate>
                        Orders
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group expandable heading="Customers" class="grid">
                    <flux:sidebar.item icon="users" href="{{ route('admin.customers.index') }}" :current="request()->routeIs('admin.customers.*')" wire:navigate>
                        Customers
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group expandable heading="Discounts" class="grid">
                    <flux:sidebar.item icon="tag" href="{{ route('admin.discounts.index') }}" :current="request()->routeIs('admin.discounts.*')" wire:navigate>
                        Discounts
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group expandable heading="Content" class="grid">
                    <flux:sidebar.item icon="document-text" href="{{ route('admin.pages.index') }}" :current="request()->routeIs('admin.pages.*')" wire:navigate>
                        Pages
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="bars-3" href="{{ route('admin.navigation.index') }}" :current="request()->routeIs('admin.navigation.*')" wire:navigate>
                        Navigation
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="paint-brush" href="{{ route('admin.themes.index') }}" :current="request()->routeIs('admin.themes.*')" wire:navigate>
                        Themes
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="chart-pie" href="{{ route('admin.analytics.index') }}" :current="request()->routeIs('admin.analytics.*')" wire:navigate>
                    Analytics
                </flux:sidebar.item>
                <flux:sidebar.item icon="cog-6-tooth" href="{{ route('admin.settings.index') }}" :current="request()->routeIs('admin.settings.*')" wire:navigate>
                    Settings
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:sidebar.profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                />
                <flux:menu>
                    @if(isset($currentStore))
                        <flux:menu.heading>{{ $currentStore->name }}</flux:menu.heading>
                        <flux:menu.separator />
                    @endif
                    <flux:menu.item icon="cog-6-tooth" href="{{ route('admin.settings.index') }}" wire:navigate>Settings</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            Log out
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        {{-- Mobile header --}}
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            @if(isset($currentStore))
                <flux:text class="text-sm font-medium">{{ $currentStore->name }}</flux:text>
                <flux:spacer />
            @endif

            <flux:dropdown position="bottom" align="end">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                />
                <flux:menu>
                    <flux:menu.item icon="cog-6-tooth" href="{{ route('admin.settings.index') }}" wire:navigate>Settings</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            Log out
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <flux:main>
            {{-- Breadcrumbs --}}
            @if(isset($breadcrumbs) && count($breadcrumbs) > 0)
                <flux:breadcrumbs class="mb-6">
                    <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" wire:navigate />
                    @foreach($breadcrumbs as $crumb)
                        @if(isset($crumb['url']))
                            <flux:breadcrumbs.item href="{{ $crumb['url'] }}" wire:navigate>{{ $crumb['label'] }}</flux:breadcrumbs.item>
                        @else
                            <flux:breadcrumbs.item>{{ $crumb['label'] }}</flux:breadcrumbs.item>
                        @endif
                    @endforeach
                </flux:breadcrumbs>
            @endif

            {{ $slot }}
        </flux:main>

        {{-- Toast notifications --}}
        <div
            x-data="{
                toasts: [],
                addToast(event) {
                    const id = Date.now()
                    this.toasts.push({ id, type: event.detail.type || 'success', message: event.detail.message })
                    setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id) }, 5000)
                }
            }"
            @toast.window="addToast($event)"
            class="fixed top-4 right-4 z-50 flex flex-col gap-2"
        >
            <template x-for="toast in toasts" :key="toast.id">
                <div
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-x-4"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-x-0"
                    x-transition:leave-end="opacity-0 translate-x-4"
                    class="flex w-80 items-center gap-3 rounded-lg border bg-white p-4 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
                    :class="{
                        'border-l-4 border-l-green-500': toast.type === 'success',
                        'border-l-4 border-l-red-500': toast.type === 'error',
                        'border-l-4 border-l-blue-500': toast.type === 'info',
                    }"
                >
                    <template x-if="toast.type === 'success'">
                        <svg class="h-5 w-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <svg class="h-5 w-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    </template>
                    <template x-if="toast.type === 'info'">
                        <svg class="h-5 w-5 shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                    </template>
                    <span class="text-sm text-zinc-700 dark:text-zinc-200" x-text="toast.message"></span>
                    <button @click="toasts = toasts.filter(t => t.id !== toast.id)" class="ml-auto shrink-0 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </template>
        </div>

        @fluxScripts
    </body>
</html>
