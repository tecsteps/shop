<?php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

it('allows an owner to sign in and reach the admin dashboard', function (): void {
    $owner = User::query()->where('email', 'owner@shop.test')->firstOrFail();
    $store = Store::query()->first();

    $page = visit('/admin/login')
        ->assertSee('Admin sign in')
        ->fill('email', 'owner@shop.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->wait(2);

    // Select the store after login (admin dashboard requires current_store_id)
    $page->navigate('/admin/switch-store/'.$store->getKey())
        ->wait(1);

    $page->assertPathIs('/admin')
        ->assertNoJavaScriptErrors();
});
