<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{}"
      x-init="
          const stored = localStorage.getItem('theme');
          if (stored === 'dark' || (stored === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
              document.documentElement.classList.add('dark');
          }
      ">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title.' · '.__('Admin') : __('Admin') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-800 antialiased dark:bg-zinc-950 dark:text-zinc-200">
    @auth('web')
        <livewire:admin.layout.sidebar />

        <livewire:admin.layout.top-bar />

        <flux:main container>
            {{ $slot }}
        </flux:main>
    @else
        {{ $slot }}
    @endauth

    {{-- Global toast host. Listens for Livewire `toast` events. --}}
    <div
        x-data="{
            toasts: [],
            add(detail) {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, type: detail.type ?? 'info', message: detail.message ?? '' });
                setTimeout(() => this.remove(id), 5000);
            },
            remove(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            },
        }"
        x-on:toast.window="add($event.detail?.[0] ?? $event.detail ?? {})"
        class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-end gap-2 px-4 sm:right-4 sm:left-auto sm:max-w-sm"
        aria-live="polite"
        aria-atomic="true"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-4 opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="pointer-events-auto flex w-full items-start gap-3 rounded-lg border-l-4 bg-white p-4 shadow-lg dark:bg-zinc-900"
                :class="{
                    'border-green-500': toast.type === 'success',
                    'border-red-500': toast.type === 'error',
                    'border-blue-500': toast.type === 'info' || !['success','error'].includes(toast.type),
                }"
                role="status"
            >
                <flux:icon.check-circle x-show="toast.type === 'success'" class="size-5 shrink-0 text-green-500" />
                <flux:icon.exclamation-circle x-show="toast.type === 'error'" class="size-5 shrink-0 text-red-500" />
                <flux:icon.information-circle x-show="toast.type === 'info' || !['success','error'].includes(toast.type)" class="size-5 shrink-0 text-blue-500" />
                <p class="flex-1 text-sm text-zinc-800 dark:text-zinc-200" x-text="toast.message"></p>
                <button type="button" x-on:click="remove(toast.id)" class="shrink-0 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200" aria-label="{{ __('Dismiss') }}">
                    <flux:icon.x-mark class="size-4" />
                </button>
            </div>
        </template>
    </div>

    @livewireScripts
    @fluxScripts
</body>
</html>
