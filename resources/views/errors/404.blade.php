<x-layouts.storefront :title="__('Page not found')">
    <section class="flex min-h-[50vh] flex-col items-center justify-center gap-4 py-24 text-center">
        <p class="text-sm font-semibold uppercase tracking-widest text-zinc-500">404</p>
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-4xl">Page not found</h1>
        <p class="max-w-md text-sm text-zinc-600 dark:text-zinc-400">Sorry, we could not find the page you were looking for.</p>
        <a href="{{ route('storefront.home') }}" class="mt-4 inline-flex items-center rounded-full bg-zinc-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            Back to home
        </a>
    </section>
</x-layouts.storefront>
