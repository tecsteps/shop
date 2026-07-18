<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-950 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <livewire:admin.layout.sidebar />

        <div class="lg:pl-64">
            <livewire:admin.layout.top-bar />

            <main class="mx-auto w-full max-w-7xl p-4 sm:p-6 lg:p-8">
                @if (session('toast'))
                    <flux:callout class="mb-6" variant="success">{{ session('toast') }}</flux:callout>
                @endif

                {{ $slot }}
            </main>
        </div>

        <div
            x-data="{ toasts: [] }"
            x-on:toast.window="toasts.push({ id: Date.now(), ...$event.detail }); setTimeout(() => toasts.shift(), 5000)"
            class="fixed right-4 top-4 z-50 flex w-80 flex-col gap-2"
            aria-live="polite"
        >
            <template x-for="toast in toasts" :key="toast.id">
                <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-sm font-medium" x-text="toast.message"></p>
                </div>
            </template>
        </div>

        @fluxScripts
    </body>
</html>
