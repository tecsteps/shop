@extends('storefront.layouts.app')

@section('content')
    <div class="flex min-h-[70vh] flex-col items-center justify-center px-4 py-20 text-center">
        <p class="text-8xl font-bold tracking-tight text-zinc-100 sm:text-9xl dark:text-zinc-800" aria-hidden="true">404</p>
        <h1 class="mt-2 text-2xl font-bold text-zinc-900 sm:text-3xl dark:text-white">Page not found</h1>
        <p class="mt-3 max-w-md text-zinc-500 dark:text-zinc-400">
            The page you are looking for does not exist or has been moved.
        </p>

        <form action="{{ route('storefront.search') }}" method="GET" class="mt-8 flex w-full max-w-sm gap-2" role="search">
            <label for="not-found-query" class="sr-only">Search products</label>
            <input
                id="not-found-query"
                type="search"
                name="q"
                placeholder="Search products..."
                class="block w-full rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 placeholder-zinc-400 transition focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder-zinc-500 dark:focus:border-white dark:focus:ring-white/20"
            />
            <button
                type="submit"
                class="shrink-0 rounded-lg bg-zinc-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
            >
                Search
            </button>
        </form>

        <a
            href="{{ route('storefront.home') }}"
            class="mt-4 inline-flex items-center gap-2 rounded-lg border border-zinc-300 px-6 py-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m15 18-6-6 6-6" />
            </svg>
            Go to home page
        </a>
    </div>
@endsection
