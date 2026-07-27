<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();

    actingAsAdmin(User::query()->where('email', 'admin@acme.test')->sole());
});

test('shows the customer list', function () {
    visit('/admin/customers')
        ->assertSee('customer@acme.test')
        ->assertSee('John Doe')
        ->assertNoJavascriptErrors();
});

test('shows customer detail with order history', function () {
    visit('/admin/customers')
        ->click('John Doe')
        ->wait(1)
        ->assertSee('John Doe')
        ->assertSee('customer@acme.test')
        ->assertSee('#1001')
        ->assertNoJavascriptErrors();
});

test('shows customer addresses', function () {
    visit('/admin/customers')
        ->click('John Doe')
        ->wait(1)
        ->assertSee('Addresses')
        ->assertSee('Hauptstrasse 1')
        ->assertNoJavascriptErrors();
});
