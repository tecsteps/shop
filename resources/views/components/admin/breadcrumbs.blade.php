@props(['items' => []])

{{-- Dynamic breadcrumb trail (spec 03 section 19.5): Home > parent > current. --}}
<flux:breadcrumbs {{ $attributes }}>
    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate>{{ __('Home') }}</flux:breadcrumbs.item>

    @foreach ($items as $item)
        @if (! empty($item['href']) && ! $loop->last)
            <flux:breadcrumbs.item :href="$item['href']" wire:navigate>{{ $item['label'] }}</flux:breadcrumbs.item>
        @else
            <flux:breadcrumbs.item>{{ $item['label'] }}</flux:breadcrumbs.item>
        @endif
    @endforeach
</flux:breadcrumbs>
