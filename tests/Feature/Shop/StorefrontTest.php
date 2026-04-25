<?php

use App\Models\Product;
use App\Models\SearchQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
    $this->withServerVariables(['HTTP_HOST' => 'shop.test']);
});

test('storefront pages render active catalog content and hide draft products', function (): void {
    $this->get('/')->assertOk()->assertSee('Acme Fashion')->assertSee('Classic Cotton T-Shirt');
    $this->get('/collections/t-shirts')->assertOk()->assertSee('Classic Cotton T-Shirt')->assertDontSee('Draft Rain Coat');
    $this->get('/products/classic-cotton-t-shirt')->assertOk()->assertSee('Medium')->assertSee('Black')->assertSee('In stock');
    $this->get('/products/sold-out-sneakers')->assertOk()->assertSee('Out of stock');
    $this->get('/products/wool-beanie')->assertOk()->assertSee('Backorder available');
    $this->get('/pages/about')->assertOk()->assertSee('About Acme Fashion');
});

test('search is scoped and logs search queries', function (): void {
    $this->get('/search?q=shirt')
        ->assertOk()
        ->assertSee('Classic Cotton T-Shirt')
        ->assertDontSee('Wireless Headphones');

    expect(SearchQuery::query()->where('query', 'shirt')->exists())->toBeTrue();
});

test('cart accepts products, updates quantities, validates discounts, and starts checkout', function (): void {
    $variant = Product::withoutGlobalScopes()
        ->where('handle', 'classic-cotton-t-shirt')
        ->firstOrFail()
        ->defaultVariant()
        ->firstOrFail();

    $this->post('/cart/lines', ['variant_id' => $variant->id, 'quantity' => 1])->assertRedirect('/cart');
    $this->get('/cart')->assertOk()->assertSee('Classic Cotton T-Shirt')->assertSee('24.99 EUR');

    $this->post('/cart/discount', ['discount_code' => 'WELCOME10'])->assertRedirect();
    $this->get('/cart')->assertSee('WELCOME10')->assertSee('Discount');

    $this->post('/cart/discount', ['discount_code' => 'EXPIRED'])->assertSessionHasErrors('discount_code');
    $this->post('/cart/checkout')->assertRedirectContains('/checkout/');
});
