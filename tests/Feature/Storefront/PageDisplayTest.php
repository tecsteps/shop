<?php

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Theme;
use App\Models\ThemeSettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();

    // Create a published theme with settings for the layout
    $theme = Theme::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => [
            'hero_heading' => 'Test Store',
            'show_announcement_bar' => false,
        ],
    ]);
});

it('displays a published page', function () {
    $page = Page::factory()->create([
        'store_id' => $this->context['store']->id,
        'title' => 'About Us',
        'handle' => 'about',
        'body_html' => '<p>This is our about page.</p>',
        'status' => PageStatus::Published,
    ]);

    Livewire::test(\App\Livewire\Storefront\Pages\Show::class, ['handle' => 'about'])
        ->assertSee('About Us')
        ->assertSee('This is our about page.')
        ->assertStatus(200);
});

it('returns 404 for draft pages', function () {
    Page::factory()->draft()->create([
        'store_id' => $this->context['store']->id,
        'handle' => 'draft-page',
    ]);

    Livewire::test(\App\Livewire\Storefront\Pages\Show::class, ['handle' => 'draft-page']);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('returns 404 for nonexistent pages', function () {
    Livewire::test(\App\Livewire\Storefront\Pages\Show::class, ['handle' => 'nonexistent']);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
