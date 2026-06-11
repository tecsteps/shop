<div>
    @foreach ($sections as $section)
        @switch($section)
            @case('hero')
                @include('storefront.sections.hero')

                @break
            @case('featured-collections')
                @include('storefront.sections.featured-collections')

                @break
            @case('featured-products')
                @include('storefront.sections.featured-products')

                @break
            @case('newsletter')
                @include('storefront.sections.newsletter')

                @break
            @case('rich-text')
                @include('storefront.sections.rich-text')

                @break
        @endswitch
    @endforeach
</div>
