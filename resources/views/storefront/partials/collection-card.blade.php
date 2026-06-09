@php
    use App\Enums\MediaStatus;
    use Illuminate\Support\Facades\Storage;

    /** @var \App\Models\Collection $collection */
    $cardMedia = $collection->products->first()?->media->firstWhere('status', MediaStatus::Ready)
        ?? $collection->products->first()?->media->first();
    $cardImageUrl = $cardMedia !== null ? Storage::disk('public')->url($cardMedia->storage_key) : null;
@endphp

<a
    href="{{ route('storefront.collections.show', $collection->handle) }}"
    class="group relative block overflow-hidden rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-950"
>
    <div class="aspect-[3/4] w-full bg-zinc-100 dark:bg-zinc-800">
        @if ($cardImageUrl !== null)
            <img
                src="{{ $cardImageUrl }}"
                alt="{{ $collection->title }}"
                loading="lazy"
                class="size-full object-cover transition duration-300 group-hover:scale-105"
            />
        @else
            <div
                class="size-full transition duration-300 group-hover:scale-105"
                style="background-image: linear-gradient(160deg, var(--sf-primary, #1d4ed8), var(--sf-secondary, #3b82f6));"
                aria-hidden="true"
            ></div>
        @endif
    </div>
    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-zinc-950/70 via-zinc-950/30 to-transparent p-4 pt-12">
        <h3 class="text-base font-semibold text-white sm:text-lg">{{ $collection->title }}</h3>
        <span class="mt-0.5 inline-block text-xs text-white/80 underline underline-offset-2 transition group-hover:text-white sm:text-sm">
            {{ __('Shop now') }} &rarr;
        </span>
    </div>
</a>
