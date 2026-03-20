<?php

// Suite 5: Admin Discount Management - Listing, creation, editing

it('shows seeded discount codes', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Discounts")')
        ->assertSee('WELCOME10')
        ->assertSee('FLAT5')
        ->assertSee('FREESHIP')
        ->assertNoJavaScriptErrors();
});

it('can create a new percentage discount code', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Discounts")')
        ->click('New discount')
        ->fill('code', 'E2ETEST25')
        ->fill('value_amount', '25')
        ->click('Create discount')
        ->assertSee('Discount created')
        ->assertNoJavaScriptErrors();
});

it('can create a fixed amount discount code', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Discounts")')
        ->click('New discount')
        ->fill('code', 'E2EFLAT10')
        ->fill('value_amount', '1000')
        ->click('Create discount')
        ->assertSee('Discount created')
        ->assertNoJavaScriptErrors();
});

it('can create a free shipping discount code', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Discounts")')
        ->click('New discount')
        ->fill('code', 'E2EFREESHIP')
        ->fill('value_amount', '0')
        ->click('Create discount')
        ->assertSee('Discount created')
        ->assertNoJavaScriptErrors();
});

it('can edit a discount', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Discounts")')
        ->click('WELCOME10')
        ->clear('value_amount')
        ->fill('value_amount', '15')
        ->click('Save changes')
        ->assertSee('Discount updated')
        ->assertNoJavaScriptErrors();
});

it('shows discount status indicators', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Discounts")')
        ->assertSee('Active')
        ->assertNoJavaScriptErrors();
});
