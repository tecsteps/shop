<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Playwright::setHost('shop.test');

    $this->seed(DatabaseSeeder::class);
});

afterEach(function (): void {
    Playwright::setHost(null);
});

function storefrontResponsiveHost(): array
{
    return ['host' => 'shop.test'];
}

function storefrontResponsiveMobileVisit(string $path): mixed
{
    return visit($path, storefrontResponsiveHost())
        ->resize(375, 812)
        ->wait(1);
}

function storefrontResponsiveTabletVisit(string $path): mixed
{
    return visit($path, storefrontResponsiveHost())
        ->resize(768, 1024)
        ->wait(1);
}

function storefrontResponsiveAssertNoHorizontalScroll(mixed $page): mixed
{
    return $page->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth');
}

function storefrontResponsiveAddClassicToCartOnMobile(): mixed
{
    return storefrontResponsiveMobileVisit('/products/classic-cotton-t-shirt')
        ->click('M')
        ->wait(1)
        ->click('Black')
        ->wait(1)
        ->click('Add to cart')
        ->wait(1);
}

function storefrontResponsiveCartWithClassicOnMobile(): mixed
{
    return storefrontResponsiveAddClassicToCartOnMobile()
        ->navigate('/cart')
        ->wait(1)
        ->assertSee('Your Cart')
        ->assertSee('Classic Cotton T-Shirt');
}

function storefrontResponsiveFillAddress(mixed $page): mixed
{
    return $page
        ->fill('input[wire\\:model="email"]', 'mobile-buyer@example.com')
        ->fill('input[wire\\:model="shippingAddress.first_name"]', 'Mobile')
        ->fill('input[wire\\:model="shippingAddress.last_name"]', 'Buyer')
        ->fill('input[wire\\:model="shippingAddress.address1"]', 'Responsive Strasse 1')
        ->fill('input[wire\\:model="shippingAddress.city"]', 'Berlin')
        ->fill('input[wire\\:model="shippingAddress.postal_code"]', '10115')
        ->select('select[wire\\:model="shippingAddress.country"]', 'DE');
}

function storefrontResponsiveAdminLogin(): mixed
{
    return storefrontResponsiveTabletVisit('/admin/login')
        ->fill('input[type=email]', 'admin@acme.test')
        ->fill('input[type=password]', 'password')
        ->click('@admin-login-button')
        ->wait(1)
        ->assertPathIs('/admin')
        ->assertSee('Dashboard');
}

test('mobile home shows hamburger navigation without horizontal overflow', function (): void {
    $page = storefrontResponsiveMobileVisit('/')
        ->assertSee('Acme Fashion')
        ->assertVisible('@mobile-menu-button')
        ->assertScript('document.querySelector("nav[aria-label=\"Main navigation\"]").offsetParent === null');

    storefrontResponsiveAssertNoHorizontalScroll($page)
        ->click('@mobile-menu-button')
        ->wait(1)
        ->assertVisible('nav[aria-label="Mobile navigation"]')
        ->assertSee('Collections')
        ->assertNoJavaScriptErrors();
});

test('mobile product detail is stacked and keeps purchase controls usable', function (): void {
    $page = storefrontResponsiveMobileVisit('/products/classic-cotton-t-shirt')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertSee('Add to cart')
        ->assertButtonEnabled('button:has-text("Add to cart")')
        ->assertScript(<<<'JS'
function() {
    const columns = Array.from(document.querySelectorAll('main section > div.mt-8.grid > div'));

    if (columns.length < 2) {
        return false;
    }

    const media = columns[0].getBoundingClientRect();
    const details = columns[1].getBoundingClientRect();

    return Math.abs(media.left - details.left) <= 2 && details.top > media.bottom;
}
JS);

    storefrontResponsiveAssertNoHorizontalScroll($page)
        ->assertNoJavaScriptErrors();
});

test('mobile shoppers can add a product to the cart', function (): void {
    storefrontResponsiveAddClassicToCartOnMobile()
        ->navigate('/cart')
        ->wait(1)
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertNoJavaScriptErrors();
});

test('mobile cart keeps checkout available', function (): void {
    $page = storefrontResponsiveCartWithClassicOnMobile()
        ->assertSee('Summary')
        ->assertVisible('main button:has-text("Checkout")');

    storefrontResponsiveAssertNoHorizontalScroll($page)
        ->assertNoJavaScriptErrors();
});

test('mobile checkout reaches shipping methods after address entry', function (): void {
    $page = storefrontResponsiveCartWithClassicOnMobile()
        ->click('main button:has-text("Checkout")')
        ->wait(1)
        ->assertPathIs('/checkout')
        ->assertSee('Checkout');

    storefrontResponsiveFillAddress($page)
        ->click('form[wire\\:submit="saveAddress"] button[type="submit"]')
        ->wait(1)
        ->assertSee('Standard Shipping')
        ->assertNoJavaScriptErrors();

    storefrontResponsiveAssertNoHorizontalScroll($page);
});

test('tablet admin login works at responsive width', function (): void {
    storefrontResponsiveAdminLogin()
        ->assertNoJavaScriptErrors();
});

test('tablet admin sidebar navigation reaches products and orders', function (): void {
    $page = storefrontResponsiveAdminLogin()
        ->click('button[aria-label="Toggle sidebar"]')
        ->wait(1)
        ->click('a[href$="/admin/products"]')
        ->wait(1)
        ->assertPathIs('/admin/products')
        ->assertSee('Products');

    $page->click('button[aria-label="Toggle sidebar"]')
        ->wait(1)
        ->click('a[href$="/admin/orders"]')
        ->wait(1)
        ->assertPathIs('/admin/orders')
        ->assertSee('Orders')
        ->assertNoJavaScriptErrors();
});

test('mobile collection keeps filters accessible', function (): void {
    $page = storefrontResponsiveMobileVisit('/collections/t-shirts')
        ->assertSee('T-Shirts')
        ->assertSee('Filters')
        ->assertSee('Classic Cotton T-Shirt')
        ->fill('input[aria-label="Maximum price"]', '10')
        ->wait(1)
        ->assertSee('No products found');

    storefrontResponsiveAssertNoHorizontalScroll($page)
        ->assertNoJavaScriptErrors();
});
