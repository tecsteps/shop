<?php

// Suite 13: Responsive / Mobile - Mobile and tablet viewport rendering

use Pest\Browser\Enums\Device;

it('renders home page on mobile', function () {
    $page = $this->visit('/', ['host' => 'acme-fashion.test', 'device' => Device::IPHONE_14_PRO]);

    $page->assertSee('Acme Fashion')
        ->assertNoJavaScriptErrors();
});

it('renders product page on mobile', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test', 'device' => Device::IPHONE_14_PRO]);

    $page->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('renders collection page on mobile', function () {
    $page = $this->visit('/collections/t-shirts', ['host' => 'acme-fashion.test', 'device' => Device::IPHONE_14_PRO]);

    $page->assertSee('T-Shirts')
        ->assertNoJavaScriptErrors();
});

it('renders cart page on mobile', function () {
    $page = $this->visit('/cart', ['host' => 'acme-fashion.test', 'device' => Device::IPHONE_14_PRO]);

    $page->assertSee('Your Cart')
        ->assertNoJavaScriptErrors();
});

it('renders admin login on mobile', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test', 'device' => Device::IPHONE_14_PRO]);

    $page->assertSee('Admin Login')
        ->assertNoJavaScriptErrors();
});

it('renders home page on tablet', function () {
    $page = $this->visit('/', ['host' => 'acme-fashion.test', 'device' => Device::IPAD_PRO]);

    $page->assertSee('Acme Fashion')
        ->assertNoJavaScriptErrors();
});

it('renders customer login on mobile', function () {
    $page = $this->visit('/account/login', ['host' => 'acme-fashion.test', 'device' => Device::IPHONE_14_PRO]);

    $page->assertSee('Log in')
        ->assertNoJavaScriptErrors();
});

it('renders search page on mobile', function () {
    $page = $this->visit('/search?q=shirt', ['host' => 'acme-fashion.test', 'device' => Device::IPHONE_14_PRO]);

    $page->assertSee('shirt')
        ->assertNoJavaScriptErrors();
});
