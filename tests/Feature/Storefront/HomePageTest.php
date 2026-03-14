<?php

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Enums\ThemeStatus;
use App\Enums\VariantStatus;
use App\Livewire\Storefront\Home;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Theme;
use App\Models\ThemeSettings;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('renders the home page with store name', function () {
    $hostname = $this->ctx['domain']->hostname;

    $response = $this->call('GET', 'http://'.$hostname.'/');

    $response->assertOk()
        ->assertSee($this->ctx['store']->name);
});

it('shows hero section from theme settings', function () {
    $theme = Theme::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Test Theme',
        'status' => ThemeStatus::Published,
        'is_active' => true,
    ]);

    ThemeSettings::create([
        'theme_id' => $theme->id,
        'settings_json' => [
            'hero_heading' => 'Test Hero Heading',
            'hero_subheading' => 'Test subheading text',
            'hero_cta_text' => 'Browse Collection',
            'hero_cta_link' => '/collections/test',
        ],
    ]);

    Livewire::test(Home::class)
        ->assertSee('Test Hero Heading')
        ->assertSee('Test subheading text')
        ->assertSee('Browse Collection');
});

it('shows featured collections', function () {
    Collection::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Summer Sale',
        'handle' => 'summer-sale',
        'status' => CollectionStatus::Active,
        'published_at' => now(),
    ]);

    Collection::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Winter Warmers',
        'handle' => 'winter-warmers',
        'status' => CollectionStatus::Active,
        'published_at' => now(),
    ]);

    Livewire::test(Home::class)
        ->assertSee('Summer Sale')
        ->assertSee('Winter Warmers');
});

it('shows featured products only active ones', function () {
    $activeProduct = Product::factory()->active()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Active T-Shirt',
    ]);
    ProductVariant::factory()->create([
        'product_id' => $activeProduct->id,
        'status' => VariantStatus::Active,
        'price_amount' => 2500,
    ]);

    $draftProduct = Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Draft Hoodie',
        'status' => ProductStatus::Draft,
    ]);
    ProductVariant::factory()->create([
        'product_id' => $draftProduct->id,
        'status' => VariantStatus::Active,
        'price_amount' => 3500,
    ]);

    Livewire::test(Home::class)
        ->assertSee('Active T-Shirt')
        ->assertDontSee('Draft Hoodie');
});
