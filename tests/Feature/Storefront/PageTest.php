<?php

use App\Enums\PageStatus;
use App\Models\Page;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('shows published page content', function () {
    Page::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'About Us',
        'handle' => 'about',
        'content_html' => '<p>We are a great company.</p>',
        'status' => PageStatus::Published,
        'published_at' => now(),
    ]);

    $hostname = $this->ctx['domain']->hostname;

    $response = $this->call('GET', 'http://'.$hostname.'/pages/about');

    $response->assertOk()
        ->assertSee('About Us')
        ->assertSee('We are a great company.');
});

it('returns 404 for draft page', function () {
    Page::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Terms of Service',
        'handle' => 'terms',
        'content_html' => '<p>Draft terms.</p>',
        'status' => PageStatus::Draft,
    ]);

    $hostname = $this->ctx['domain']->hostname;

    $response = $this->call('GET', 'http://'.$hostname.'/pages/terms');

    $response->assertNotFound();
});

it('returns 404 for nonexistent handle', function () {
    $hostname = $this->ctx['domain']->hostname;

    $response = $this->call('GET', 'http://'.$hostname.'/pages/does-not-exist');

    $response->assertNotFound();
});
