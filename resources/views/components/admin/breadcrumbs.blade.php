@props(['items' => []])

{{--
    Admin breadcrumb trail. The "Home" item is prepended automatically and links
    to the dashboard. Pass intermediate/current items as
    [['label' => 'Products', 'href' => route(...)], ['label' => 'Blue Shirt']].
    The final item (no href) renders as the current page.
--}}
<flux:breadcrumbs {{ $attributes->merge(['class' => 'mb-6']) }}>
    <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" wire:navigate />

    @foreach ($items as $item)
        @if (! empty($item['href']))
            <flux:breadcrumbs.item :href="$item['href']" wire:navigate>{{ $item['label'] }}</flux:breadcrumbs.item>
        @else
            <flux:breadcrumbs.item>{{ $item['label'] }}</flux:breadcrumbs.item>
        @endif
    @endforeach
</flux:breadcrumbs>
