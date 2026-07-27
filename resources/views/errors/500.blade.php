@extends('errors.layout', ['title' => 'Something went wrong'])

@section('content')
    <div class="w-full max-w-lg text-center">
        <p aria-hidden="true" class="pointer-events-none select-none text-[8rem] leading-none font-bold text-gray-100 dark:text-gray-900">500</p>

        <div class="-mt-10">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Something went wrong</h1>
            <p class="mx-auto mt-3 max-w-md text-sm text-gray-500 dark:text-gray-400">
                We're experiencing technical difficulties. Please try again in a moment.
            </p>
            <a href="/"
               class="mt-6 inline-block rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                Go to home page
            </a>
        </div>
    </div>
@endsection
