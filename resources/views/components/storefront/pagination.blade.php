{{-- This component wraps Laravel's default pagination with storefront styling --}}
@props(['paginator' => null])

@if ($paginator && $paginator->hasPages())
    <nav {{ $attributes->merge(['class' => 'flex items-center justify-center space-x-1']) }} aria-label="Pagination">
        {{ $paginator->links() }}
    </nav>
@endif
