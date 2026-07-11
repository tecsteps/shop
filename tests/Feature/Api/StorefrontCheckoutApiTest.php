<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\TaxSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()->forgetInstance('current_store');
    $this->store = Store::factory()->create(['default_currency' => 'EUR']);
    StoreDomain::factory()->for($this->store)->create(['hostname' => 'acme-fashion.test']);
    app()->instance('current_store', $this->store);
    $product = Product::factory()->for($this->store)->create();
    $this->variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    $this->variant->inventoryItem->update(['quantity_on_hand' => 10]);
    $zone = ShippingZone::factory()->for($this->store)->create(['countries_json' => ['DE']]);
    $this->rate = ShippingRate::factory()->for($zone, 'zone')->create(['config_json' => ['amount' => 499]]);
    TaxSettings::factory()->for($this->store)->create(['prices_include_tax' => false, 'config_json' => ['default_rate_bps' => 1900]]);
    app()->forgetInstance('current_store');
    $this->withServerVariables(['HTTP_HOST' => 'acme-fashion.test']);
});

it('completes the storefront checkout API flow', function () {
    $cartId = $this->postJson('/api/storefront/v1/carts')->json('data.id');
    $this->postJson("/api/storefront/v1/carts/{$cartId}/lines", ['variant_id' => $this->variant->id, 'quantity' => 2])->assertSuccessful();
    $checkoutId = $this->postJson('/api/storefront/v1/checkouts', ['cart_id' => $cartId])->assertCreated()->json('data.id');

    $this->putJson("/api/storefront/v1/checkouts/{$checkoutId}/address", ['email' => 'buyer@example.com', 'shipping_address' => ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'address1' => 'Main Street 1', 'city' => 'Berlin', 'country' => 'DE', 'country_code' => 'DE', 'postal_code' => '10115']])
        ->assertSuccessful()->assertJsonPath('data.status', 'addressed');
    $this->putJson("/api/storefront/v1/checkouts/{$checkoutId}/shipping-method", ['shipping_rate_id' => $this->rate->id])
        ->assertSuccessful()->assertJsonPath('data.status', 'shipping_selected');
    $this->putJson("/api/storefront/v1/checkouts/{$checkoutId}/payment-method", ['payment_method' => 'credit_card'])
        ->assertSuccessful()->assertJsonPath('data.status', 'payment_selected');
    $this->postJson("/api/storefront/v1/checkouts/{$checkoutId}/pay", ['card_number' => '4242424242424242'])
        ->assertSuccessful()->assertJsonPath('data.financial_status', 'paid');
});

it('returns validation errors for incomplete addresses and declined cards', function () {
    $cartId = $this->postJson('/api/storefront/v1/carts')->json('data.id');
    $this->postJson("/api/storefront/v1/carts/{$cartId}/lines", ['variant_id' => $this->variant->id, 'quantity' => 1]);
    $checkoutId = $this->postJson('/api/storefront/v1/checkouts', ['cart_id' => $cartId])->json('data.id');

    $this->putJson("/api/storefront/v1/checkouts/{$checkoutId}/address", ['email' => 'buyer@example.com', 'shipping_address' => []])
        ->assertUnprocessable()->assertJsonValidationErrors(['shipping_address.first_name', 'shipping_address.city']);
});
