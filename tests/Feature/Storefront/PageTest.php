<?php

use App\Enums\PageStatus;
use App\Models\Page;

it('renders a published page', function () {
    $context = createStoreContext();

    $page = Page::factory()->published()->create([
        'store_id' => $context['store']->id,
        'title' => 'About Us',
        'handle' => 'about-us',
        'body_html' => '<p>Welcome to our store.</p>',
    ]);

    $response = $this->get(
        'http://'.$context['domain']->hostname.'/pages/about-us'
    );

    $response->assertSuccessful()
        ->assertSee('About Us')
        ->assertSee('Welcome to our store.');
});

it('returns 404 for draft page', function () {
    $context = createStoreContext();

    Page::factory()->create([
        'store_id' => $context['store']->id,
        'handle' => 'draft-page',
        'status' => PageStatus::Draft,
    ]);

    $response = $this->get(
        'http://'.$context['domain']->hostname.'/pages/draft-page'
    );

    $response->assertNotFound();
});

it('returns 404 for nonexistent page', function () {
    $context = createStoreContext();

    $response = $this->get(
        'http://'.$context['domain']->hostname.'/pages/nonexistent'
    );

    $response->assertNotFound();
});
