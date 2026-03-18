<x-storefront.layouts.error>
    <x-slot:title>{{ __('Page Not Found') }}</x-slot:title>

    <div class="flex min-h-[60vh] flex-col items-center justify-center px-4">
        <h1 class="text-6xl font-bold text-zinc-900 dark:text-white">404</h1>
        <h2 class="mt-4 text-xl font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Page Not Found') }}</h2>
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('The page you are looking for does not exist or has been moved.') }}</p>
        <a href="/"
           class="mt-8 inline-flex items-center rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
            {{ __('Back to Home') }}
        </a>
    </div>
</x-storefront.layouts.error>
