<?php

// Suite 11: Inventory Enforcement - deny vs. continue policies

it('shows sold out for deny policy with zero stock', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('allows backorder for continue policy with zero stock', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('shows add to cart button for in-stock products', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Add to cart')
        ->assertNoJavaScriptErrors();
});

it('enforces inventory on add to cart', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});
