@extends('errors.layout', ['title' => 'Page not found'])

@section('content')
    <div class="relative w-full max-w-lg text-center">
        {{-- Oversized muted status code as a background element (spec 04 §13.1) --}}
        <p aria-hidden="true" class="pointer-events-none select-none text-[10rem] leading-none font-bold text-gray-100 sm:text-[14rem] dark:text-gray-900">404</p>

        <div class="-mt-16 sm:-mt-24">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Page not found</h1>
            <p class="mx-auto mt-3 max-w-md text-sm text-gray-500 dark:text-gray-400">
                The page you're looking for doesn't exist or has been moved.
            </p>

            <form action="/search" method="get" role="search" class="mx-auto mt-8 flex max-w-sm gap-2">
                <label for="error-404-search" class="sr-only">Search products</label>
                <input id="error-404-search" type="search" name="q" placeholder="Search products..."
                       class="min-w-0 flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <button type="submit"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                    Search
                </button>
            </form>

            <a href="/"
               class="mt-6 inline-block rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                Go to home page
            </a>
        </div>
    </div>
@endsection
