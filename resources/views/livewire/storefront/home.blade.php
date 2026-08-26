<div class="bg-white dark:bg-zinc-950">
    @foreach ($this->sections as $section)
        @include('storefront.sections.'.$section, [
            'settings' => $this->settings,
            'hero' => $this->hero,
            'featuredCollections' => $this->featuredCollections,
            'featuredProducts' => $this->featuredProducts,
        ])
    @endforeach
</div>
