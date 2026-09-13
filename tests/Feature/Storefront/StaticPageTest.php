<?php

use App\Models\Page;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders a published page', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $page = Page::factory()->create([
        'store_id' => $store->id,
        'title' => 'About Us',
        'handle' => 'about-us',
        'body_html' => '<p>Our story begins here.</p>',
        'status' => 'published',
    ]);

    $response = $this->get(storefrontUrl($domain->hostname, '/pages/about-us'));

    $response->assertOk();
    $response->assertSee('About Us');
    $response->assertSee('Our story begins here.');
});

it('returns 404 for draft, missing, or foreign pages', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    Page::factory()->create(['store_id' => $store->id, 'handle' => 'draft-page', 'status' => 'draft']);
    ['store' => $otherStore] = $this->createStoreContext();
    Page::factory()->create(['store_id' => $otherStore->id, 'handle' => 'foreign-page', 'status' => 'published']);

    $this->get(storefrontUrl($domain->hostname, '/pages/nope'))->assertNotFound();
    $this->get(storefrontUrl($domain->hostname, '/pages/draft-page'))->assertNotFound();
    $this->get(storefrontUrl($domain->hostname, '/pages/foreign-page'))->assertNotFound();
});
