<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Home extends Component
{
    use InteractsWithStore;

    public string $newsletterEmail = '';

    public ?string $newsletterMessage = null;

    /**
     * Subscribe an email address to the newsletter.
     */
    public function subscribe(): void
    {
        $this->validate([
            'newsletterEmail' => ['required', 'email', 'max:255'],
        ]);

        $this->newsletterMessage = 'Thanks for subscribing!';
        $this->newsletterEmail = '';
    }

    #[Computed]
    public function settings(): array
    {
        return $this->themeSettings();
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function sections(): array
    {
        $sections = $this->settings['home_sections'] ?? [];

        return array_values(array_filter(
            array_map(fn (string $section) => str_replace('_', '-', $section), $sections),
            fn (string $section) => $section !== 'newsletter' || ($this->settings['newsletter_enabled'] ?? true),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function hero(): array
    {
        $settings = $this->settings;

        return [
            'heading' => $settings['hero_heading'] ?? null,
            'subheading' => $settings['hero_subheading'] ?? null,
            'ctaText' => $settings['hero_cta_text'] ?? null,
            'ctaLink' => $settings['hero_cta_link'] ?? null,
            'image' => $settings['hero_image'] ?? null,
        ];
    }

    #[Computed]
    public function featuredCollections(): SupportCollection
    {
        $handles = array_values(array_filter($this->settings['featured_collection_handles'] ?? []));

        if ($handles === []) {
            return collect();
        }

        return Collection::whereIn('handle', $handles)
            ->where('status', 'active')
            ->get()
            ->sortBy(fn (Collection $collection) => array_search($collection->handle, $handles, true))
            ->values();
    }

    #[Computed]
    public function featuredProducts(): SupportCollection
    {
        $limit = max(1, (int) ($this->settings['featured_products_count'] ?? 8));
        $handle = $this->settings['featured_products_collection'] ?? null;

        $with = [
            'variants.inventoryItem',
            'variants.optionValues',
            'media' => fn ($query) => $query->where('status', 'ready')->orderBy('position'),
        ];

        if ($handle) {
            $collection = Collection::where('handle', $handle)->where('status', 'active')->first();

            if ($collection) {
                return $collection->products()
                    ->where('status', 'active')
                    ->whereNotNull('published_at')
                    ->with($with)
                    ->orderByPivot('position')
                    ->limit($limit)
                    ->get();
            }
        }

        return Product::query()
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->with($with)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
