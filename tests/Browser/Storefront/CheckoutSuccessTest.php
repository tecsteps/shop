<?php

use App\Models\WebhookSubscription;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    WebhookSubscription::query()->withoutGlobalScopes()->delete();
    Http::fake();
});

it('completes a checkout with the magic success card', function (): void {
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

    $page->fill('card_number', '4242424242424242')
        ->click('Place order')
        ->wait(3);

    $page->assertPathBeginsWith('/checkout/success')
        ->assertSee('Order')
        ->assertNoJavaScriptErrors();
});
