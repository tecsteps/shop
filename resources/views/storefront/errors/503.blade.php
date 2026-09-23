@extends('storefront.layouts.app')

@section('title', 'We’ll be back soon')
@section('content')
    <section class="mx-auto flex min-h-[70vh] max-w-2xl flex-col items-center justify-center px-4 py-16 text-center sm:px-6">
        @php($storeName = data_get($currentStore ?? null, 'name', config('app.name', 'Store')))
        @if (data_get($currentStore ?? null, 'logo_url'))<img src="{{ data_get($currentStore, 'logo_url') }}" alt="{{ $storeName }}" class="mb-8 max-h-12 w-auto">@else<span class="mb-8 text-xl font-semibold tracking-tight">{{ $storeName }}</span>@endif
        <span class="flex size-14 items-center justify-center rounded-full bg-stone-100 text-stone-500 dark:bg-stone-900 dark:text-stone-300">@include('storefront.components.icon', ['name' => 'package', 'class' => 'size-7'])</span>
        <h1 class="mt-5 text-2xl font-semibold tracking-tight sm:text-3xl">We’ll be back soon</h1>
        <p class="mt-3 max-w-md text-sm leading-6 text-stone-600 dark:text-stone-300">We’re currently performing maintenance. Please check back shortly.</p>
    </section>
@endsection
