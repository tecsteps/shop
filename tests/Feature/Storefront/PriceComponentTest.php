<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('price component formats cents to dollars with currency code', function () {
    $view = $this->blade(
        '<x-storefront.price :amount="2499" currency="EUR" />'
    );

    $view->assertSee('24.99 EUR');
});

test('price component formats zero amount', function () {
    $view = $this->blade(
        '<x-storefront.price :amount="0" currency="EUR" />'
    );

    $view->assertSee('0.00 EUR');
});

test('price component formats large amounts with thousands separator', function () {
    $view = $this->blade(
        '<x-storefront.price :amount="149900" currency="EUR" />'
    );

    $view->assertSee('1,499.00 EUR');
});

test('price component shows compare at price with strikethrough', function () {
    $view = $this->blade(
        '<x-storefront.price :amount="1999" currency="USD" :compare-at-amount="2999" />'
    );

    $view->assertSee('19.99 USD')
        ->assertSee('29.99 USD')
        ->assertSee('line-through');
});

test('price component does not show compare at when equal to price', function () {
    $view = $this->blade(
        '<x-storefront.price :amount="1999" currency="USD" :compare-at-amount="1999" />'
    );

    $view->assertSee('19.99 USD')
        ->assertDontSee('line-through');
});
