<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Create a minimal order with a single order line referencing the given
 * product/variant. Orders are Phase 5, so rows are inserted directly.
 */
function createOrderLineFor(App\Models\Store $store, App\Models\Product $product, ?App\Models\ProductVariant $variant = null): void
{
    $orderId = Illuminate\Support\Facades\DB::table('orders')->insertGetId([
        'store_id' => $store->id,
        'order_number' => 'ORD-'.Illuminate\Support\Str::random(10),
        'payment_method' => 'credit_card',
    ]);

    Illuminate\Support\Facades\DB::table('order_lines')->insert([
        'order_id' => $orderId,
        'product_id' => $product->id,
        'variant_id' => $variant?->id,
        'title_snapshot' => $product->title,
        'sku_snapshot' => $variant?->sku,
        'quantity' => 1,
        'unit_price_amount' => $variant?->price_amount ?? 0,
        'total_amount' => $variant?->price_amount ?? 0,
    ]);
}
