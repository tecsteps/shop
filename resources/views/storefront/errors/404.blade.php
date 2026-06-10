<x-layouts::storefront :title="__('Page not found')">
    <div class="relative mx-auto flex min-h-[60vh] max-w-2xl flex-col items-center justify-center px-4 py-24 text-center sm:px-6">
        <p class="pointer-events-none absolute inset-x-0 top-1/2 -translate-y-1/2 text-[10rem] leading-none font-black text-zinc-100 select-none sm:text-[14rem] dark:text-zinc-900" aria-hidden="true">
            404
        </p>
        <div class="relative">
            <h1 class="text-2xl font-bold text-zinc-900 sm:text-3xl dark:text-white">{{ __('Page not found') }}</h1>
            <p class="mx-auto mt-3 max-w-md text-sm text-zinc-600 dark:text-zinc-400">
                {{ __("The page you're looking for doesn't exist or has been moved.") }}
            </p>
            <form action="{{ route('storefront.search') }}" method="get" class="mx-auto mt-8 flex max-w-sm items-center gap-2">
                <label class="relative block w-full">
                    <span class="sr-only">{{ __('Search products') }}</span>
                    <input
                        type="search"
                        name="q"
                        placeholder="{{ __('Search products...') }}"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                    />
                </label>
                <button
                    type="submit"
                    class="shrink-0 rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                >
                    {{ __('Search') }}
                </button>
            </form>
            <div class="mt-4 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a
                    href="{{ route('home') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-(--sf-primary,#1d4ed8) px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2"
                >
                    {{ __('Go to home page') }}
                </a>
                <a
                    href="{{ route('storefront.collections.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-zinc-300 px-5 py-2.5 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                >
                    {{ __('Browse collections') }}
                </a>
            </div>
        </div>
    </div>
</x-layouts::storefront>
