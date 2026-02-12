<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Exceptions\DiscountValidationException;
use App\Models\Discount;
use App\Models\Store;
use App\Services\CartService;
use App\Services\DiscountService;

it('validates an active discount code and returns qualifying lines', function () {
    $store = Store::factory()->create();
    $variant = createSellableVariant($store, variantAttributes: ['price_amount' => 2000]);

    $cartService = app(CartService::class);
    $cart = $cartService->create($store);
    $cart = $cartService->addLine($cart, $variant->id, 2);

    $discount = Discount::factory()
        ->for($store)
        ->state([
            'code' => 'SAVE10',
            'status' => DiscountStatus::Active,
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ])
        ->create();

    $result = app(DiscountService::class)->validateCode($cart, ' save10 ');

    expect($result['discount']->id)->toBe($discount->id)
        ->and($result['qualifying_lines'])->toHaveCount(1)
        ->and($result['qualifying_lines']->first()?->cart_id)->toBe($cart->id);
});

it('rejects unknown and expired discount codes', function () {
    $store = Store::factory()->create();
    $variant = createSellableVariant($store);

    $cartService = app(CartService::class);
    $cart = $cartService->create($store);
    $cart = $cartService->addLine($cart, $variant->id, 1);

    $service = app(DiscountService::class);

    try {
        $service->validateCode($cart, 'DOES-NOT-EXIST');

        $this->fail('Expected unknown code validation to fail.');
    } catch (DiscountValidationException $exception) {
        expect($exception->errorCode)->toBe('discount_not_found');
    }

    Discount::factory()
        ->for($store)
        ->state([
            'code' => 'EXPIRED',
            'status' => DiscountStatus::Expired,
            'value_type' => DiscountValueType::Fixed,
            'value_amount' => 500,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
        ])
        ->create();

    try {
        $service->validateCode($cart, 'EXPIRED');

        $this->fail('Expected expired code validation to fail.');
    } catch (DiscountValidationException $exception) {
        expect($exception->errorCode)->toBe('discount_expired');
    }
});
