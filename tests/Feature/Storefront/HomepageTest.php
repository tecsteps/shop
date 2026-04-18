<?php

use App\Models\Theme;
use App\Models\ThemeSettings;

it('renders the homepage with store name', function (): void {
    $context = $this->createStoreContext(['hostname' => 'home-store.test']);

    $response = $this->get('http://home-store.test/');

    $response->assertOk();
    $response->assertSee($context['store']->name);
});

it('renders hero heading from theme settings when available', function (): void {
    $context = $this->createStoreContext(['hostname' => 'hero-store.test']);

    $theme = Theme::factory()->published()->create([
        'store_id' => $context['store']->id,
    ]);

    ThemeSettings::query()->create([
        'theme_id' => $theme->id,
        'settings_json' => [
            'hero' => [
                'heading' => 'Curated finds for spring',
                'subheading' => 'Only the good stuff.',
                'cta_label' => 'Shop now',
                'cta_url' => '/collections',
            ],
        ],
        'updated_at' => now(),
    ]);

    $response = $this->get('http://hero-store.test/');

    $response->assertOk();
    $response->assertSee('Curated finds for spring');
});
