
@php
    $pageTitle = data_get($page, 'title', 'Page');
    $pageBody = data_get($page, 'body_html', data_get($page, 'body', ''));
    $pageDescription = \Illuminate\Support\Str::limit(trim(strip_tags($pageBody)), 160);
@endphp

@section('title', $pageTitle)
@section('meta_description', $pageDescription)
<div>
<article class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        @include('storefront.components.breadcrumbs', ['items' => [['label' => 'Home', 'url' => '/'], ['label' => $pageTitle]]])
        <div class="mx-auto mt-8 max-w-3xl"><h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">{{ $pageTitle }}</h1><div class="storefront-prose mt-7">{!! $pageBody !!}</div></div>
    </article>
</div>
