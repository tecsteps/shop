<?php

use App\Models\Discount;

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('lists discounts page', function () {
    Discount::factory()->count(3)->for($this->ctx['store'])->create();

    $response = adminRequest($this->ctx)->get('/admin/discounts');

    $response->assertStatus(200);
});

it('renders discount create page', function () {
    $response = adminRequest($this->ctx)->get('/admin/discounts/create');

    $response->assertStatus(200);
});

it('renders discount edit page', function () {
    $discount = Discount::factory()->for($this->ctx['store'])->create();

    $response = adminRequest($this->ctx)->get("/admin/discounts/{$discount->id}/edit");

    $response->assertStatus(200);
});
