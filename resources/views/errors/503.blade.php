<x-storefront.layouts.error>
    <x-slot:title>{{ __('Service Unavailable') }}</x-slot:title>

    <div class="flex min-h-[60vh] flex-col items-center justify-center px-4">
        <h1 class="text-6xl font-bold text-zinc-900 dark:text-white">503</h1>
        <h2 class="mt-4 text-xl font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Service Unavailable') }}</h2>
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('We are currently performing maintenance. Please check back soon.') }}</p>
    </div>
</x-storefront.layouts.error>
