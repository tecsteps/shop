<flux:breadcrumbs>
    @foreach ($this->items as $item)
        <flux:breadcrumbs.item :href="$item['href'] ?? null">
            {{ $item['label'] }}
        </flux:breadcrumbs.item>
    @endforeach
</flux:breadcrumbs>
