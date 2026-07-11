<?php

beforeEach(function (): void {
    seedBrowserShop($this);
});

it('loads the main public surfaces without browser errors', function (): void {
    $pages = visit([
        '/',
        '/collections/t-shirts',
        '/products/classic-cotton-t-shirt',
        '/cart',
        '/search?q=shirt',
        '/pages/about',
        '/account/login',
        '/admin/login',
    ]);

    $pages->assertNoJavaScriptErrors();

    $pages[0]->assertSee('Acme Fashion');
    $pages[1]->assertSee('T-Shirts');
    $pages[2]->assertSee('Classic Cotton T-Shirt')->assertSee('24.99');
    $pages[3]->assertSee('Your cart');
    $pages[4]->assertSee('Search');
    $pages[5]->assertSee('About');
    $pages[6]->assertSee('Sign in');
    $pages[7]->assertSee('Access your store administration.');
});
