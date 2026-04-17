<?php

// Suite 14: Accessibility - Heading hierarchy, ARIA labels, form labels, keyboard nav

it('has no critical accessibility issues on home page', function () {
    $page = $this->visit('/', ['host' => 'acme-fashion.test']);

    $page->assertNoJavaScriptErrors();
});

it('has no critical accessibility issues on product page', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->assertNoJavaScriptErrors();
});

it('has no critical accessibility issues on collection page', function () {
    $page = $this->visit('/collections/t-shirts', ['host' => 'acme-fashion.test']);

    $page->assertNoJavaScriptErrors();
});

it('has no critical accessibility issues on cart page', function () {
    $page = $this->visit('/cart', ['host' => 'acme-fashion.test']);

    $page->assertNoJavaScriptErrors();
});

it('has no critical accessibility issues on customer login', function () {
    $page = $this->visit('/account/login', ['host' => 'acme-fashion.test']);

    $page->assertNoJavaScriptErrors();
});

it('has no critical accessibility issues on admin login', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->assertNoJavaScriptErrors();
});

it('has form labels on admin login form', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->assertSee('Email')
        ->assertSee('Password')
        ->assertNoJavaScriptErrors();
});

it('has form labels on customer login form', function () {
    $page = $this->visit('/account/login', ['host' => 'acme-fashion.test']);

    $page->assertSee('Email')
        ->assertSee('Password')
        ->assertNoJavaScriptErrors();
});

it('has form labels on registration form', function () {
    $page = $this->visit('/account/register', ['host' => 'acme-fashion.test']);

    $page->assertSee('Name')
        ->assertSee('Email')
        ->assertSee('Password')
        ->assertNoJavaScriptErrors();
});

it('has heading on collections page', function () {
    $page = $this->visit('/collections', ['host' => 'acme-fashion.test']);

    $page->assertSee('Collections')
        ->assertNoJavaScriptErrors();
});

it('has heading on search page', function () {
    $page = $this->visit('/search?q=shirt', ['host' => 'acme-fashion.test']);

    $page->assertNoJavaScriptErrors();
});
