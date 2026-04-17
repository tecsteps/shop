<?php

use App\Models\WebhookSubscription;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    WebhookSubscription::query()->withoutGlobalScopes()->delete();
});

it('rejects a checkout when using the decline magic card', function (): void {
    $page = visit('/products/classic-tee')
        ->click('Add to Cart')
        ->wait(1);

    $page->navigate('/checkout')
        ->assertSee('Checkout')
        ->fill('email', 'customer@example.com')
        ->fill('first_name', 'Jane')
        ->fill('last_name', 'Doe')
        ->fill('address1', '123 Main St')
        ->fill('city', 'Springfield')
        ->fill('province_code', 'IL')
        ->fill('country_code', 'US')
        ->fill('postal_code', '62701')
        ->click('Save address')
        ->wait(1);

    $page->click('Standard')
        ->wait(1);

    $page->fill('card_number', '4000000000000002')
        ->click('Place order')
        ->wait(2);

    $page->assertPathIs('/checkout')
        ->assertSee('card_declined')
        ->assertNoJavaScriptErrors();
});
