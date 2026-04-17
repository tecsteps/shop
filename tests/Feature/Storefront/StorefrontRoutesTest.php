<?php

use App\Enums\PageStatus;
use App\Models\Page;

it('renders the home page', function () {
    $ctx = createStoreContext('sf-home.test');

    $response = $this->get('http://sf-home.test/');

    $response->assertOk();
});

it('renders the collections index page', function () {
    $ctx = createStoreContext('sf-collections.test');

    $response = $this->get('http://sf-collections.test/collections');

    $response->assertOk();
});

it('renders the cart page', function () {
    $ctx = createStoreContext('sf-cart.test');

    $response = $this->get('http://sf-cart.test/cart');

    $response->assertOk();
});

it('renders the search page', function () {
    $ctx = createStoreContext('sf-search.test');

    $response = $this->get('http://sf-search.test/search');

    $response->assertOk();
});

it('renders a published page by handle', function () {
    $ctx = createStoreContext('sf-page.test');

    Page::factory()->create([
        'store_id' => $ctx['store']->id,
        'handle' => 'about-us',
        'title' => 'About Us',
        'status' => PageStatus::Published,
    ]);

    $response = $this->get('http://sf-page.test/pages/about-us');

    $response->assertOk()
        ->assertSee('About Us');
});

it('returns 404 for a non-existent page', function () {
    $ctx = createStoreContext('sf-page-404.test');

    $response = $this->get('http://sf-page-404.test/pages/nonexistent');

    $response->assertNotFound();
});

it('returns 404 for a draft page', function () {
    $ctx = createStoreContext('sf-page-draft.test');

    Page::factory()->draft()->create([
        'store_id' => $ctx['store']->id,
        'handle' => 'draft-page',
    ]);

    $response = $this->get('http://sf-page-draft.test/pages/draft-page');

    $response->assertNotFound();
});
