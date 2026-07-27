@if (count($trail) > 1)
    <flux:breadcrumbs class="mb-4">
        @foreach ($trail as $crumb)
            @if ($crumb['url'] !== null)
                <flux:breadcrumbs.item :href="$crumb['url']" wire:navigate>{{ $crumb['label'] }}</flux:breadcrumbs.item>
            @else
                <flux:breadcrumbs.item>{{ $crumb['label'] }}</flux:breadcrumbs.item>
            @endif
        @endforeach
    </flux:breadcrumbs>
@endif
