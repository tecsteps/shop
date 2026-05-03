<?php

use App\Actions\SanitizeHtml;
use App\Livewire\Admin\Pages\Form as PageForm;
use App\Models\Page;
use App\Models\Store;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->user = User::query()->where('email', 'admin@example.com')->firstOrFail();
});

test('html sanitizer strips unsafe elements attributes and protocols', function (): void {
    $html = app(SanitizeHtml::class)(
        '<p onclick="alert(1)">Safe <strong>copy</strong><script>alert(1)</script></p>'.
        '<a href="javascript:alert(1)" target="_blank">bad</a>'.
        '<a href="https://example.com" class="link">good</a>'.
        '<img src="data:text/html;base64,abc" onerror="alert(1)" alt="bad">'.
        '<img src="/storage/product.jpg" alt="Product" width="100">'.
        '<section><em>kept text</em></section><span></span>',
    );

    expect($html)->toContain('<p>Safe <strong>copy</strong></p>')
        ->and($html)->toContain('<a>bad</a>')
        ->and($html)->toContain('<a href="https://example.com">good</a>')
        ->and($html)->toContain('<img alt="bad">')
        ->and($html)->toContain('<img src="/storage/product.jpg" alt="Product">')
        ->and($html)->toContain('<em>kept text</em>')
        ->and($html)->not->toContain('script')
        ->and($html)->not->toContain('onclick')
        ->and($html)->not->toContain('javascript:')
        ->and($html)->not->toContain('target=')
        ->and($html)->not->toContain('<span></span>');
});

test('product service persists sanitized product descriptions', function (): void {
    $product = app(ProductService::class)->create($this->store, [
        'title' => 'Sanitized Product',
        'status' => 'draft',
        'description_html' => '<p>Details</p><script>alert(1)</script><a href="https://example.com" style="color:red">Read</a>',
        'price_amount' => 1200,
    ]);

    expect($product->description_html)->toBe('<p>Details</p><a href="https://example.com">Read</a>');

    $updated = app(ProductService::class)->update($product, [
        'description_html' => '<div><img src="javascript:alert(1)" alt="x"><strong>Updated</strong></div>',
    ]);

    expect($updated->description_html)->toBe('<div><img alt="x"><strong>Updated</strong></div>');
});

test('admin page form persists sanitized page body html', function (): void {
    $this->actingAs($this->user);
    session(['current_store_id' => $this->store->id]);
    app()->instance('current_store', $this->store);

    Livewire::test(PageForm::class)
        ->set('title', 'Sanitized Page')
        ->set('handle', 'sanitized-page')
        ->set('bodyHtml', '<h2>About</h2><iframe src="https://example.com"></iframe><a href="mailto:hello@example.com" class="x">Email</a>')
        ->set('status', 'published')
        ->call('save')
        ->assertHasNoErrors();

    $page = Page::query()->where('handle', 'sanitized-page')->firstOrFail();

    expect($page->body_html)->toBe('<h2>About</h2><a href="mailto:hello@example.com">Email</a>');
});
