<?php

use App\Models\Product;

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('lists products page', function () {
    Product::factory()->count(3)->for($this->ctx['store'])->create();

    $response = adminRequest($this->ctx)->get('/admin/products');

    $response->assertStatus(200);
});

it('renders product create page', function () {
    $response = adminRequest($this->ctx)->get('/admin/products/create');

    $response->assertStatus(200);
});

it('renders product edit page', function () {
    $product = Product::factory()->for($this->ctx['store'])->create();

    $response = adminRequest($this->ctx)->get("/admin/products/{$product->id}/edit");

    $response->assertStatus(200);
});
