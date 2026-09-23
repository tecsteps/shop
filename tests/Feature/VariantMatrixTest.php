<?php

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Services\VariantMatrixService;
use Illuminate\Validation\ValidationException;

function variantMatrixMakeOption(Product $product, string $name, array $values): array
{
    $option = $product->options()->create(['name' => $name, 'position' => $product->options()->count()]);
    $optionValues = [];

    foreach ($values as $position => $value) {
        $optionValues[$value] = $option->values()->create(['value' => $value, 'position' => $position]);
    }

    return ['option' => $option, 'values' => $optionValues];
}

function variantMatrixMakeVariant(Product $product, array $valueIds, array $attributes = []): ProductVariant
{
    $position = $product->variants()->count();

    $variant = $product->variants()->create(array_merge([
        'sku' => 'MATRIX-'.fake()->unique()->numerify('#####'),
        'price_amount' => 4200,
        'compare_at_amount' => 5000,
        'currency' => 'EUR',
        'weight_g' => 250,
        'requires_shipping' => true,
        'is_default' => $position === 0,
        'position' => $position,
        'status' => 'active',
    ], $attributes));

    $variant->optionValues()->sync($valueIds);
    $variant->inventoryItem()->create([
        'store_id' => $product->store_id,
        'quantity_on_hand' => 8,
        'quantity_reserved' => 2,
        'policy' => 'continue',
    ]);

    return $variant;
}

it('rebuilds the cartesian matrix while preserving matching variant data and creating inventory for new variants', function () {
    $store = shopStore();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $size = variantMatrixMakeOption($product, 'Size', ['Small', 'Medium']);
    $color = variantMatrixMakeOption($product, 'Color', ['Black', 'White']);
    $preserved = variantMatrixMakeVariant($product, [$size['values']['Small']->id, $color['values']['Black']->id], [
        'sku' => 'SHIRT-S-BLACK',
        'price_amount' => 6100,
        'compare_at_amount' => 7200,
        'weight_g' => 310,
    ]);
    $preservedInventory = $preserved->inventoryItem()->firstOrFail();

    $variants = app(VariantMatrixService::class)->rebuild($product, [
        ['name' => 'Garment size', 'values' => ['Small', 'Medium']],
        ['name' => 'Color', 'values' => ['Black', 'White']],
    ]);

    expect($variants)->toHaveCount(4)
        ->and($variants->pluck('id'))->toContain($preserved->id)
        ->and($variants->firstWhere('id', $preserved->id)->sku)->toBe('SHIRT-S-BLACK')
        ->and($variants->firstWhere('id', $preserved->id)->price_amount)->toBe(6100)
        ->and($variants->firstWhere('id', $preserved->id)->compare_at_amount)->toBe(7200)
        ->and($variants->firstWhere('id', $preserved->id)->inventoryItem->id)->toBe($preservedInventory->id)
        ->and($variants->firstWhere('id', $preserved->id)->inventoryItem->quantity_on_hand)->toBe(8)
        ->and($variants->firstWhere('id', $preserved->id)->inventoryItem->quantity_reserved)->toBe(2);

    expect(ProductOption::query()->where('product_id', $product->id)->whereKey($size['option']->id)->value('name'))
        ->toBe('Garment size');

    $newVariants = $variants->where('id', '!=', $preserved->id);

    expect($newVariants)->toHaveCount(3);

    foreach ($newVariants as $variant) {
        expect($variant->price_amount)->toBe(6100)
            ->and($variant->compare_at_amount)->toBe(7200)
            ->and($variant->currency)->toBe('EUR')
            ->and($variant->weight_g)->toBe(310)
            ->and($variant->sku)->toBeNull()
            ->and($variant->inventoryItem)->not->toBeNull()
            ->and($variant->inventoryItem->quantity_on_hand)->toBe(0)
            ->and($variant->inventoryItem->quantity_reserved)->toBe(0)
            ->and($variant->inventoryItem->policy)->toBe('continue');
    }
});

it('archives removed variants with order history and deletes removed variants without it', function () {
    $store = shopStore();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $size = variantMatrixMakeOption($product, 'Size', ['Small', 'Medium', 'Large']);
    $small = variantMatrixMakeVariant($product, [$size['values']['Small']->id]);
    $medium = variantMatrixMakeVariant($product, [$size['values']['Medium']->id]);
    $large = variantMatrixMakeVariant($product, [$size['values']['Large']->id]);
    $order = Order::query()->create([
        'store_id' => $store->id,
        'order_number' => 'MATRIX-ORDER-1',
        'payment_method' => 'mock',
    ]);
    OrderLine::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $medium->id,
        'title_snapshot' => $product->title,
        'quantity' => 1,
        'unit_price_amount' => 4200,
        'total_amount' => 4200,
    ]);

    $variants = app(VariantMatrixService::class)->rebuild($product, [
        ['name' => 'Size', 'values' => ['Small', 'Extra Large']],
    ]);

    expect($variants)->toHaveCount(2)
        ->and(ProductVariant::query()->findOrFail($small->id)->status)->toBe('active')
        ->and(ProductVariant::query()->findOrFail($medium->id)->status)->toBe('archived')
        ->and(ProductVariant::query()->find($large->id))->toBeNull()
        ->and($variants->firstWhere('id', $medium->id))->toBeNull();
});

it('creates one default variant and inventory item for a product with no options or existing variants', function () {
    $store = shopStore(currency: 'CHF');
    $product = Product::factory()->create(['store_id' => $store->id]);

    $variants = app(VariantMatrixService::class)->rebuild($product, []);

    expect($variants)->toHaveCount(1)
        ->and($variants->first()->is_default)->toBeTrue()
        ->and($variants->first()->price_amount)->toBe(0)
        ->and($variants->first()->currency)->toBe('CHF')
        ->and($variants->first()->inventoryItem)->not->toBeNull()
        ->and($variants->first()->inventoryItem->quantity_on_hand)->toBe(0);
});

it('rejects more than three options, empty values, and blank option values without changing the product', function () {
    $store = shopStore();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $service = app(VariantMatrixService::class);

    expect(fn () => $service->rebuild($product, [
        ['name' => 'Size', 'values' => ['Small']],
        ['name' => 'Color', 'values' => ['Black']],
        ['name' => 'Material', 'values' => ['Cotton']],
        ['name' => 'Fit', 'values' => ['Regular']],
    ]))->toThrow(ValidationException::class);

    expect(fn () => $service->rebuild($product, [
        ['name' => 'Size', 'values' => []],
    ]))->toThrow(ValidationException::class);

    expect(fn () => $service->rebuild($product, [
        ['name' => 'Size', 'values' => ['  ']],
    ]))->toThrow(ValidationException::class);

    expect(ProductOption::query()->where('product_id', $product->id)->count())->toBe(0)
        ->and(ProductVariant::query()->where('product_id', $product->id)->count())->toBe(0)
        ->and(ProductOptionValue::query()->count())->toBe(0);
});
