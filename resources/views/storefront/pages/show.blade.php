<article class="sf-container sf-page-y">
    <x-storefront.breadcrumbs :items="[['label' => 'Home', 'url' => url('/')], ['label' => $page->title]]" />
    <header class="mx-auto mt-8 max-w-3xl text-center"><p class="sf-eyebrow">{{ $currentStore->name }}</p><h1 class="sf-page-title mt-2">{{ $page->title }}</h1></header>
    <div class="mx-auto mt-10 max-w-3xl"><x-storefront.rich-text :html="$page->body_html" /></div>
</article>
