<?php

namespace App\Livewire\Storefront;

use App\Services\ThemeSettingsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Home extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $heroSettings = [];

    /**
     * @var array<int, string>
     */
    public array $sections = [];

    public function mount(): void
    {
        $themeSettings = app(ThemeSettingsService::class);

        $this->sections = $themeSettings->get('home_sections', [
            'hero',
            'featured_collections',
            'featured_products',
            'newsletter',
            'rich_text',
        ]);

        $this->heroSettings = $themeSettings->get('hero', [
            'heading' => 'Welcome to Our Store',
            'subheading' => 'Discover our latest collection',
            'cta_text' => 'Shop Now',
            'cta_link' => '/collections',
            'image' => null,
        ]);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.home');
    }
}
