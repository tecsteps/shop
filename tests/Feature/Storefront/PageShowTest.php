<?php

use App\Enums\PageStatus;
use App\Models\Page;

it('renders a published CMS page', function (): void {
    $context = $this->createStoreContext(['hostname' => 'cms-store.test']);

    Page::query()->create([
        'store_id' => $context['store']->id,
        'title' => 'About Us',
        'handle' => 'about',
        'body_html' => '<p>Our story begins here.</p>',
        'status' => PageStatus::Published,
        'published_at' => now(),
    ]);

    $response = $this->get('http://cms-store.test/pages/about');

    $response->assertOk();
    $response->assertSee('About Us');
    $response->assertSee('Our story begins here.', false);
});

it('returns 404 for a draft page', function (): void {
    $context = $this->createStoreContext(['hostname' => 'draft-store.test']);

    Page::query()->create([
        'store_id' => $context['store']->id,
        'title' => 'Secret',
        'handle' => 'secret',
        'body_html' => '<p>hidden</p>',
        'status' => PageStatus::Draft,
    ]);

    $this->get('http://draft-store.test/pages/secret')->assertNotFound();
});

it('returns 404 for missing page', function (): void {
    $this->createStoreContext(['hostname' => 'missing-store.test']);

    $this->get('http://missing-store.test/pages/nope')->assertNotFound();
});
