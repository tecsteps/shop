<?php

use App\Http\Requests\Api\Admin\StoreProductRequest;
use App\Http\Requests\Api\Storefront\AddCartLineRequest;
use App\Http\Requests\Api\Storefront\SetCheckoutAddressRequest;
use App\Http\Requests\Api\Storefront\UpdateCartLineRequest;
use Illuminate\Support\Facades\Validator;

it('validates AddCartLineRequest', function (array $payload, bool $expectValid): void {
    $rules = (new AddCartLineRequest)->rules();
    $passes = Validator::make($payload, $rules)->passes();

    expect($passes)->toBe($expectValid);
})->with([
    'missing variant_id' => [['quantity' => 1], false],
    'zero quantity' => [['variant_id' => 1, 'quantity' => 0], false],
    'negative quantity' => [['variant_id' => 1, 'quantity' => -1], false],
    'valid payload' => [['variant_id' => 42, 'quantity' => 2], true],
]);

it('validates UpdateCartLineRequest quantity', function (array $payload, bool $expectValid): void {
    $rules = (new UpdateCartLineRequest)->rules();
    $passes = Validator::make($payload, $rules)->passes();

    expect($passes)->toBe($expectValid);
})->with([
    'missing quantity' => [[], false],
    'negative quantity' => [['quantity' => -1], false],
    'zero quantity allowed for removal' => [['quantity' => 0], true],
    'valid quantity' => [['quantity' => 3], true],
]);

it('validates SetCheckoutAddressRequest', function (array $payload, bool $expectValid): void {
    $rules = (new SetCheckoutAddressRequest)->rules();
    $passes = Validator::make($payload, $rules)->passes();

    expect($passes)->toBe($expectValid);
})->with([
    'missing email' => [[
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '1 Main',
            'city' => 'LA',
            'country_code' => 'US',
            'postal_code' => '90001',
        ],
    ], false],
    'invalid country code length' => [[
        'email' => 'a@b.com',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '1 Main',
            'city' => 'LA',
            'country_code' => 'USA',
            'postal_code' => '90001',
        ],
    ], false],
    'valid address' => [[
        'email' => 'buyer@example.com',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '1 Main',
            'city' => 'LA',
            'country_code' => 'US',
            'postal_code' => '90001',
        ],
    ], true],
]);

it('validates StoreProductRequest', function (array $payload, bool $expectValid): void {
    $rules = (new StoreProductRequest)->rules();
    $passes = Validator::make($payload, $rules)->passes();

    expect($passes)->toBe($expectValid);
})->with([
    'missing title' => [['status' => 'active'], false],
    'invalid status' => [['title' => 'Test', 'status' => 'unknown'], false],
    'tag too long' => [['title' => 'Test', 'tags' => [str_repeat('x', 65)]], false],
    'valid payload' => [['title' => 'Test', 'status' => 'active', 'tags' => ['a', 'b']], true],
]);
